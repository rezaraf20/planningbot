<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\Update;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Handle general messages';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText(true));

        $state = $this->getUserState($chat_id);

        switch ($state['step']) {
            case 'awaiting_task_title':
                $this->saveToTemp($chat_id, 'title', $text);
                $this->setUserStep($chat_id, 'awaiting_task_date');

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'تاریخ برنامه رو وارد کن (مثلاً 1403/05/25):',
                ]);

            case 'awaiting_task_date':
                $this->saveToTemp($chat_id, 'date', $text);
                $this->setUserStep($chat_id, 'awaiting_task_time');

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'ساعت برنامه رو وارد کن (مثلاً 14:30):',
                ]);

            case 'awaiting_task_time':
                $this->saveToTemp($chat_id, 'time', $text);
                $this->setUserStep($chat_id, 'awaiting_task_repeat');

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "برنامه‌ت قراره تکرار بشه؟\nیک گزینه وارد کن:\n- روزانه\n- هفتگی\n- ماهانه\n- بدون تکرار",
                ]);

            case 'awaiting_task_repeat':
                $this->saveToTemp($chat_id, 'repeat', $text);
                $this->setUserStep($chat_id, null);

                // ذخیره نهایی
                $final = $this->finalizeTask($chat_id);

                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "برنامه با موفقیت ثبت شد ✅\n\n$final",
                ]);

            default:
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'برای ساخت برنامه جدید از دکمه "📌 ایجاد برنامه" استفاده کن.',
                ]);
        }
    }

    private function getUserState($chat_id)
    {
        $file = __DIR__ . '/../UserStates/' . $chat_id . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return ['step' => null];
    }

    private function setUserStep($chat_id, $step)
    {
        $file = __DIR__ . '/../UserStates/' . $chat_id . '.json';
        $data = $this->getUserState($chat_id);
        $data['step'] = $step;
        file_put_contents($file, json_encode($data));
    }

    private function saveToTemp($chat_id, $key, $value)
    {
        $file = __DIR__ . '/../UserStates/' . $chat_id . '.json';
        $data = $this->getUserState($chat_id);
        $data[$key] = $value;
        file_put_contents($file, json_encode($data));
    }

    private function finalizeTask($chat_id)
    {
        $file = __DIR__ . '/../UserStates/' . $chat_id . '.json';
        $data = $this->getUserState($chat_id);

        $task_dir = __DIR__ . '/../UserTasks';
        if (!is_dir($task_dir)) {
            mkdir($task_dir, 0777, true);
        }

        $task_file = $task_dir . '/' . $chat_id . '.json';
        $all_tasks = file_exists($task_file) ? json_decode(file_get_contents($task_file), true) : [];
        $all_tasks[] = [
            'title' => $data['title'],
            'date' => $data['date'],
            'time' => $data['time'],
            'repeat' => $data['repeat'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        file_put_contents($task_file, json_encode($all_tasks));

        unlink($file);

        return "📝 عنوان: {$data['title']}\n📅 تاریخ: {$data['date']}\n⏰ ساعت: {$data['time']}\n🔁 تکرار: {$data['repeat']}";
    }
}
