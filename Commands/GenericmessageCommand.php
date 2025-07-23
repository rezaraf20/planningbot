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
        ini_set("error_log", __DIR__ . '/../bot-log/debug.log');  
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
            error_log('➡️ Forwarding to createtask (active state: ' . $state['step'] . ')');
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
