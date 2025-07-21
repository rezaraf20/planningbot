<?php
require_once __DIR__ . '/vendor/autoload.php';
use Longman\TelegramBot\Telegram;

$bot_api_key  = 'YOUR_BOT_API_TOKEN';
$bot_username = 'YOUR_BOT_USERNAME';
$db_host = 'localhost';
$db_name = '*********';
$db_user = '*********';
$db_pass = '*********';

try {
    $telegram = new Telegram($bot_api_key, $bot_username);
    
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $telegram->enableExternalMySql($pdo);
    $telegram->addCommandsPaths([__DIR__ . '/Commands']);
  
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    file_put_contents('bot-error.log', $e->getMessage(), FILE_APPEND);
}
