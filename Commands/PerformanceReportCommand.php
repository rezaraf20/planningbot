<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use PDO;

class PerformanceReportCommand extends UserCommand
{
    protected $name = 'performancereport';
    protected $description = 'گزارش عملکرد';
    protected $usage = '/performancereport';
    protected $version = '1.0.0';

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        $pdo = new PDO("mysql:host=localhost;dbname=rezarafi_plbt;charset=utf8mb4", 'rezarafi_plbt', 'qxJsMgCnH9HHRkVYtjfA');

        $stmt = $pdo->prepare("SELECT COUNT(*) as total_tasks, SUM(CASE WHEN done = 1 THEN 1 ELSE 0 END) as done_tasks FROM tasks WHERE chat_id = :chat_id AND task_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute(['chat_id' => $chat_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $text = "📈 گزارش عملکرد ۳۰ روز اخیر:\n";
        $text .= "🔢 مجموع برنامه‌ها: " . $result['total_tasks'] . "\n";
        $text .= "✅ انجام شده: " . $result['done_tasks'] . "\n";
        $text .= "❌ انجام نشده: " . ($result['total_tasks'] - $result['done_tasks']) . "\n";

        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => $text,
        ]);
    }
}
