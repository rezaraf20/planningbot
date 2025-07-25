<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use PDO;

require_once __DIR__ . '/../helpers.php';

class CreatetaskCommand extends UserCommand
{
    protected $name = 'createtask';
    protected $description = 'ایجاد یک تسک جدید';
    protected $usage = '/createtask';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText());

        $pdo = DB::getPdo();

        // گرفتن وضعیت فعلی کاربر
        $stmt = $pdo->prepare("SELECT * FROM user_states WHERE chat_id = :chat_id LIMIT 1");
        $stmt->execute(['chat_id' => $chat_id]);
        $state = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$state) {
            $stmt = $pdo->prepare("INSERT INTO user_states (chat_id, step, updated_at) VALUES (:chat_id, 'title', NOW())");
            $stmt->execute(['chat_id' => $chat_id]);

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'لطفاً عنوان تسک را وارد کنید:',
            ]);
        }

        $step = $state['step'];

        switch ($step) {
            case 'title':
                $stmt = $pdo->prepare("UPDATE user_states SET current_task_title = :val, step = 'description', updated_at = NOW() WHERE chat_id = :chat_id");
                $stmt->execute([
                    'val' => $text,
                    'chat_id' => $chat_id,
                ]);
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => 'توضیح مربوط به تسک را وارد کنید:',
                ]);

            case 'description':
                $stmt = $pdo->prepare("UPDATE user_states SET current_task_description = :val, step = 'date', updated_at = NOW() WHERE chat_id = :chat_id");
                $stmt->execute([
                    'val' => $text,
                    'chat_id' => $chat_id,
                ]);
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => 'تاریخ تسک را وارد کنید (مثلاً 31-05-1404):',
                ]);

            case 'date':
                $date_val = shamsiToGregorian($text);
                file_put_contents(__DIR__ . '/../shamsi_debug.log', "called with: $text → $date_val\n", FILE_APPEND);

                if (!$date_val) {
                    return Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => '❌ فرمت تاریخ اشتباه است. لطفاً به‌صورت 31-05-1404 وارد کنید.',
                    ]);
                }

                $stmt = $pdo->prepare("UPDATE user_states SET current_task_date = :val, step = 'time', updated_at = NOW() WHERE chat_id = :chat_id");
                $stmt->execute([
                    'val' => $date_val,
                    'chat_id' => $chat_id,
                ]);
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => 'ساعت انجام تسک را وارد کنید (مثلاً 14:30):',
                ]);

            case 'time':
                $stmt = $pdo->prepare("UPDATE user_states SET current_task_time = :val, step = 'repeat', updated_at = NOW() WHERE chat_id = :chat_id");
                $stmt->execute([
                    'val' => $text,
                    'chat_id' => $chat_id,
                ]);

                $keyboard = new Keyboard(['بدون تکرار', 'روزانه'], ['هفتگی', 'ماهانه']);
                $keyboard->setResizeKeyboard(true)->setOneTimeKeyboard(true);

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => 'نوع تکرار را انتخاب کنید:',
                    'reply_markup' => $keyboard,
                ]);

            case 'repeat':
                $stmt = $pdo->prepare("UPDATE user_states SET current_task_repeat = :val, step = 'confirm', updated_at = NOW() WHERE chat_id = :chat_id");
                $stmt->execute([
                    'val' => $text,
                    'chat_id' => $chat_id,
                ]);

                $stmt = $pdo->prepare("SELECT * FROM user_states WHERE chat_id = :chat_id");
                $stmt->execute(['chat_id' => $chat_id]);
                $state = $stmt->fetch(PDO::FETCH_ASSOC);

                $summary = "📝 اطلاعات تسک شما:\n\n";
                $summary .= "عنوان: {$state['current_task_title']}\n";
                $summary .= "توضیح: {$state['current_task_description']}\n";
                $summary .= "تاریخ: {$state['current_task_date']}\n";
                $summary .= "ساعت: {$state['current_task_time']}\n";
                $summary .= "تکرار: {$state['current_task_repeat']}\n\n";
                $summary .= "لطفاً یکی از گزینه‌ها را انتخاب کنید:";

                $keyboard = new Keyboard(['ثبت', 'لغو']);
                $keyboard->setResizeKeyboard(true)->setOneTimeKeyboard(true);

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => $summary,
                    'reply_markup' => $keyboard,
                ]);

            case 'confirm':
                $text = trim($text);
                if (in_array($text, ['ثبت', 'ثبت نهایی'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO tasks (chat_id, title, description, date, time, repeat_type, created_at)
                        VALUES (:chat_id, :title, :desc, :date, :time, :repeat, NOW())
                    ");
                    $stmt->execute([
                        'chat_id' => $chat_id,
                        'title'   => $state['current_task_title'],
                        'desc'    => $state['current_task_description'],
                        'date'    => $state['current_task_date'],
                        'time'    => $state['current_task_time'],
                        'repeat'  => $state['current_task_repeat'],
                    ]);

                    $pdo->prepare("DELETE FROM user_states WHERE chat_id = :chat_id")->execute(['chat_id' => $chat_id]);

                                        return Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => '✅ تسک شما با موفقیت ثبت شد!',
                        'reply_markup' => new \Longman\TelegramBot\Entities\Keyboard(
                            ['➕ ایجاد برنامه جدید'],
                            ['📅 برنامه‌های من'],
                            ['📊 گزارش عملکرد']
                        )->setResizeKeyboard(true)->setOneTimeKeyboard(false),
                    ]);

                } elseif ($text === 'لغو') {
                    $pdo->prepare("DELETE FROM user_states WHERE chat_id = :chat_id")->execute(['chat_id' => $chat_id]);

                    return Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => '❌ فرآیند ایجاد تسک لغو شد.',
                        'reply_markup' => ['remove_keyboard' => true],
                    ]);
                } else {
                    return Request::sendMessage([
                        'chat_id' => $chat_id,
                        'text'    => 'لطفاً فقط یکی از گزینه‌های "ثبت" یا "لغو" را انتخاب کنید.',
                    ]);
                }

            default:
                $pdo->prepare("DELETE FROM user_states WHERE chat_id = :chat_id")->execute(['chat_id' => $chat_id]);

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text'    => '⚠️ مشکلی پیش آمد. لطفاً دوباره با /createtask شروع کنید.',
                ]);
        }
    }
}
