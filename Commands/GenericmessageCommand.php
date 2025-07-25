<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Handles generic messages';
    protected $version = '1.1.0';

    public function execute(): ServerResponse
    {
        ini_set("log_errors", 1);
        ini_set("error_log", __DIR__ . '/../bot-log/debug.log');  // تنظیم محل لاگ
        error_log('📌 genericmessageCommand: started');

        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text    = trim($message->getText());
        error_log('📨 received text: ' . $text);

         $pdo = DB::getPdo();
        $stmt = $pdo->prepare("SELECT step FROM user_states WHERE chat_id = :chat_id AND is_completed = FALSE");
        $stmt->execute(['chat_id' => $chat_id]);
        $state = $stmt->fetch();

        if ($state) {
    $step = $state['step'];
    error_log('➡️ Forwarding to createtask (active state: ' . $step . ')');

    if ($step === 'confirm') {
        if ($text === 'ثبت') {
            $stmt_insert = $pdo->prepare("
                INSERT INTO tasks (chat_id, title, description, date, time, repeat_type, created_at)
                SELECT chat_id, current_task_title, current_task_description, current_task_date, current_task_time, current_task_repeat, NOW()
                FROM user_states
                WHERE chat_id = :chat_id
            ");
            $stmt_insert->execute(['chat_id' => $chat_id]);

            $pdo->prepare("DELETE FROM user_states WHERE chat_id = :chat_id")->execute(['chat_id' => $chat_id]);

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => '✅ تسک شما با موفقیت ثبت شد!',
                'reply_markup' => ['remove_keyboard' => true],
            ]);
        }

        if ($text === 'لغو') {
            $pdo->prepare("DELETE FROM user_states WHERE chat_id = :chat_id")->execute(['chat_id' => $chat_id]);

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => '❌ فرآیند ایجاد تسک لغو شد.',
                'reply_markup' => ['remove_keyboard' => true],
            ]);
        }

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => 'لطفاً یکی از گزینه‌های "ثبت" یا "لغو" را انتخاب کنید.',
        ]);
    }
    return $this->telegram->executeCommand('createtask');
}


        if ($text === '➕ ایجاد برنامه جدید') {
            error_log('➡️ User selected create task from main menu');
            return $this->telegram->executeCommand('createtask');

        }

        $keyboard = new Keyboard(
            ['➕ ایجاد برنامه جدید'],
            ['📅 برنامه‌های من'],
            ['📊 گزارش عملکرد']
        );
        $keyboard->setResizeKeyboard(true)->setOneTimeKeyboard(false);

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => "متوجه نشدم چی نوشتی 😅 لطفاً از دکمه‌های زیر استفاده کن:",
            'reply_markup' => $keyboard,
        ]);
    }
}
