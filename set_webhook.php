<?php
require 'vendor/autoload.php';
use TelegramBot\Api\BotApi;

$telegram = new BotApi('7153766927:AAHIi5BYBCuEn7JZYafNV5wyl5EumXu4I6I');
$telegram->setWebhook('https://6c69-94-125-83-226.ngrok-free.app/webhook.php');
echo "Success";