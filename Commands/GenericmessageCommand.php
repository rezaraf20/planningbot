<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\ServerResponse;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Handle all generic text messages';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        global $pdo;

        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $text = trim($message->getText(true));

        $step = $this->getUserStep($chat_id);

        switch ($step) {
            case 'awaiting_task_title':
                $this->updateTempData($chat_id, 'title', $text);
                $this->setUserStep($chat_id, 'awaiting_task_date');
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'تاریخ برنامه رو وارد کن (مثلاً 1403/05/25):',
                ]);

            case 'awaiting_task_date':
                $this->updateTempData($chat_id, 'date', $text);
                $this->setUserStep($chat_id, 'awaiting_task_time');
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'ساعت برنامه رو وارد کن (مثلاً 14:30):',
                ]);

            case 'awaiting_task_time':
                $this->updateTempData($chat_id, 'time', $text);
                $this->setUserStep($chat_id, 'awaiting_task_repeat');
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "برنامه‌ت قراره تکرار بشه؟\nیک گزینه وارد کن:\n- روزانه\n- هفتگی\n- ماهانه\n- بدون تکرار",
                ]);

            case 'awaiting_task_repeat':
                $this->updateTempData($chat_id, 'repeat', $text);
                $this->saveTask($chat_id);
                $this->clearUserStep($chat_id);
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "✅ برنامه با موفقیت ذخیره شد!",
                ]);

            default:
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'برای ساخت برنامه جدید از /createtask یا دکمه مربوطه استفاده کن.',
                ]);
        }
    }

    private function getUserStep($chat_id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT current_step FROM user_steps WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        return $stmt->fetchColumn() ?: null;
    }

    private function setUserStep($chat_id, $step)
    {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO user_steps (chat_id, current_step) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE current_step = VALUES(current_step)");
        $stmt->execute([$chat_id, $step]);
    }

    private function updateTempData($chat_id, $key, $value)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT temp_data FROM user_steps WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        $temp = json_decode($stmt->fetchColumn() ?: '{}', true);
        $temp[$key] = $value;

        $stmt = $pdo->prepare("UPDATE user_steps SET temp_data = ? WHERE chat_id = ?");
        $stmt->execute([json_encode($temp, JSON_UNESCAPED_UNICODE), $chat_id]);
    }

    private function saveTask($chat_id)
    {
        global $pdo;
        // دریافت temp_data
        $stmt = $pdo->prepare("SELECT temp_data FROM user_steps WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        $temp = json_decode($stmt->fetchColumn(), true);

        if (!$temp || !isset($temp['title'])) return;

        $stmt = $pdo->prepare("SELECT id FROM users WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
        $user_id = $stmt->fetchColumn();

        if (!$user_id) {
            $stmt = $pdo->prepare("INSERT INTO users (chat_id, created_at) VALUES (?, NOW())");
            $stmt->execute([$chat_id]);
            $user_id = $pdo->lastInsertId();
        }

        $datetime = "{$temp['date']} {$temp['time']}";
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, datetime, repeat_type, created_at, status) 
                               VALUES (?, ?, ?, ?, NOW(), 'active')");
        $stmt->execute([$user_id, $temp['title'], $datetime, $temp['repeat']]);
    }

    private function clearUserStep($chat_id)
    {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE user_steps SET current_step = NULL, temp_data = NULL WHERE chat_id = ?");
        $stmt->execute([$chat_id]);
    }
}
