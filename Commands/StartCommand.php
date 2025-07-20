<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'شروع کار با ربات';
    protected $usage = '/start';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $chat_id = $this->getMessage()->getChat()->getId();
        $text    = "سلام! به ربات برنامه‌ریز خوش اومدی 🌟\nاز منوی زیر استفاده کن:";

        $keyboard = [
            ['➕ ایجاد برنامه جدید'],
            ['📅 برنامه‌های من'],
            ['📊 گزارش عملکرد'],
        ];

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
            'reply_markup' => [
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'one_time_keyboard' => false,
            ]
        ]);
    }
}
