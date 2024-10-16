<?php
require 'vendor/autoload.php';
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['action']['type']) && $input['action']['type'] === 'updateCard'){
    $cardName = $input['action']['data']['card']['name'];
    $oldList = $input['action']['data']['listBefore']['name'];
    $newList = $input['action']['data']['listAfter']['name'];
    $chatId = "-4518627087";
    $token = "7153766927:AAHIi5BYBCuEn7JZYafNV5wyl5EumXu4I6I";
    $message = "Card '$cardName' replaced from '$oldList' to '$newList'";
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($message));
}
