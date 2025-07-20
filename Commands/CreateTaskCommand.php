<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class CreateTaskCommand extends UserCommand
{
    protected $name = 'createtask';
    protected $description = 'شروع ساخت برنامه جدید';
    protected $usage = '/createtask';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        // ✅ ذخیره وضعیت فعلی کاربر (مرحله وارد کردن عنوان)
        $this->setUserStep($chat_id, 'awaiting_task_title');

        // 📝 از کاربر بخواه عنوان تسک رو وارد کنه
        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => 'لطفاً عنوان برنامه‌ای که می‌خوای بسازی رو وارد کن:',
        ]);
    }

    // ✅ تابع ذخیره مرحله کاربر در فایل
    private function setUserStep($chat_id, $step)
    {
        $dir = __DIR__ . '/../UserStates';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . "/$chat_id.json";
        file_put_contents($file, json_encode(['step' => $step]));
    }
}
