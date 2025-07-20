<?php
require_once __DIR__ . '/vendor/autoload.php';
use Longman\TelegramBot\Telegram;

$bot_api_key  = 'YOUR_BOT_API_TOKEN';
$bot_username = 'YOUR_BOT_USERNAME';

try {
    $telegram = new Telegram($bot_api_key, $bot_username);

    $telegram->addCommandsPaths([__DIR__ . '/Commands']);

    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // لاگ خطا
    file_put_contents('bot-error.log', $e->getMessage(), FILE_APPEND);
}
