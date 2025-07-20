<?php
require_once __DIR__ . '/PHPTelegramBot/vendor/autoload.php';
require_once __DIR__ . '/db.php'; 

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;

$telegram = new Telegram('YOUR_BOT_TOKEN', 'YourBotUsername');

date_default_timezone_set('Asia/Tehran');
$now = date('Y-m-d H:i');

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE datetime <= :now");
$stmt->execute(['now' => $now]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as $task) {
    $text = "⏰ <b>یادآوری برنامه:</b>\n";
    $text .= "📌 عنوان: <b>{$task['title']}</b>\n";
    if (!empty($task['description'])) {
        $text .= "📝 توضیح: {$task['description']}\n";
    }
    $text .= "🗓 زمان: " . date('Y-m-d H:i', strtotime($task['datetime']));

    Request::sendMessage([
        'chat_id' => $task['chat_id'],
        'text'    => $text,
        'parse_mode' => 'HTML',
    ]);

    switch ($task['repeat_type']) {
        case 'daily':
            $next_time = date('Y-m-d H:i:s', strtotime('+1 day', strtotime($task['datetime'])));
            $stmt_up = $pdo->prepare("UPDATE tasks SET datetime = :dt WHERE id = :id");
            $stmt_up->execute(['dt' => $next_time, 'id' => $task['id']]);
            break;
        case 'weekly':
            $next_time = date('Y-m-d H:i:s', strtotime('+1 week', strtotime($task['datetime'])));
            $stmt_up = $pdo->prepare("UPDATE tasks SET datetime = :dt WHERE id = :id");
            $stmt_up->execute(['dt' => $next_time, 'id' => $task['id']]);
            break;
        default:
            $stmt_del = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt_del->execute(['id' => $task['id']]);
            break;
    }
}
