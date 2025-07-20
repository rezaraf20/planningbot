<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

class MyTasksCommand extends UserCommand
{
    protected $name = 'mytasks';
    protected $description = 'مشاهده لیست برنامه‌های ثبت‌شده شما';
    protected $usage = '/mytasks';
    protected $version = '1.0.0';

    public function execute(): \Longman\TelegramBot\Entities\ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $pdo = new \PDO('mysql:host=localhost;dbname=telegrambot;charset=utf8mb4', 'your_db_user', 'your_db_password');

        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE chat_id = :chat_id ORDER BY task_time ASC LIMIT 10");
        $stmt->execute(['chat_id' => $chat_id]);
        $tasks = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!$tasks) {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text' => '📭 شما هیچ برنامه‌ای ثبت نکرده‌اید.',
            ]);
        }

        $text = "📋 لیست برنامه‌های شما:\n\n";
        foreach ($tasks as $index => $task) {
            $text .= ($index + 1) . ". " . $task['title'] . " - " . $task['task_time'] . "\n";
        }

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => $text,
        ]);
    }
}
