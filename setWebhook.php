<?php
require_once __DIR__ . '/vendor/autoload.php'; 
use Longman\TelegramBot\Telegram;

$bot_api_key  = 'YOUR_BOT_API_TOKEN';
$bot_username = 'YOUR_BOT_USERNAME'; 
$hook_url     = 'https://yourdomain.com/PHPTelegramBot/hook.php';

try {
    $telegram = new Telegram($bot_api_key, $bot_username);
    $result = $telegram->setWebhook($hook_url);

    if ($result->isOk()) {
        echo $result->getDescription();
    } else {
        echo 'Webhook setting failed.';
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
