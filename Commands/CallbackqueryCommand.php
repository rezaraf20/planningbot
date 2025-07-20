<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\CallbackQuery;

class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'Handle callback queries';
    protected $version = '1.0.0';

    public function execute()
    {
        $callback_query = $this->getCallbackQuery();
        $callback_data = $callback_query->getData();
        $chat_id = $callback_query->getMessage()->getChat()->getId();

        switch ($callback_data) {
            case 'start_task_create':
                // مراحل ساخت تسک رو استارت کن
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'لطفاً عنوان برنامه را وارد کنید:',
                ]);
            case 'view_tasks':
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'برای دیدن برنامه‌ها دستور /mytasks را وارد کنید.',
                ]);
            case 'view_report':
                return Request::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => 'برای دریافت گزارش عملکرد دستور /performancereport را وارد کنید.',
                ]);
            default:
                return Request::answerCallbackQuery([
                    'callback_query_id' => $callback_query->getId(),
                    'text' => 'دستور ناشناخته!',
                    'show_alert' => true,
                ]);
        }
    }
}
