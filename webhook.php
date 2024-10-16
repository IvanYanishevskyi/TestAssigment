<?php
require 'vendor/autoload.php';
use TelegramBot\Api\BotApi;

$db = "pgsql:host=localhost;dbname=postgres;user=ivan;password=pass";
$trelloWebHook = "https://6c69-94-125-83-226.ngrok-free.app/webhook_trello.php";
try {
    $pdo = new PDO($db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception){
    echo $exception->getMessage();
}
$telegram = new BotApi('7153766927:AAHIi5BYBCuEn7JZYafNV5wyl5EumXu4I6I');



$updates = json_decode(file_get_contents("php://input"));
if (isset($updates->message) && ($updates->message->text == "/start")) {
    $chatId = $updates->message->chat->id;
    $username = $updates->message->from->username ?? 'unknown';
    $first_name = $updates->message->from->first_name ?? 'unknown';
    $last_name = $updates->message->from->last_name ?? 'unknown';
    if ($last_name == 'unknown'){
        $last_name = "";
    }
    if (userExists($pdo, $username)) {
        $telegram->sendMessage($chatId,$first_name . " your data is already inserted!");
    }else{
        insertUser($pdo, $chatId, $username, $first_name, $last_name);
        $telegram->sendMessage($chatId,"Hi, " . $first_name . " " . $last_name . "! Your data is saved" . "!\n");
    }
}
if (isset($updates->message) && $updates->message->text == "/link_trello") {
    $chatId = $updates->message->chat->id;
    $token="3fb515e2eb3c8d8fb857b5c75f23f383";
    $autorizationURL = generateTrelloLink($token);
    $telegram->sendMessage($chatId,"Click on link to connect your telegram with Trello " . $autorizationURL . " ");

}
if (isset($updates->message) && str_starts_with($updates->message->text, "trello_token=")) {
    $chatId = $updates->message->chat->id;
    $text = $updates->message->text;
    $username = $updates->message->from->username;
    $token = str_replace("trello_token=", "", $text);
    SaveTrelloToken($pdo,$chatId, $username, $token);
    $telegram->sendMessage($chatId,"Trello token has been saved!");
}
if (isset($updates->message) && $updates->message->text == "/report") {
    $chatId = $updates->message->chat->id;
    makeReport($telegram,$pdo, $chatId);
}
function insertUser($pdo, $chatId, $username, $first_name, $last_name)
{
    $pg = "INSERT INTO telegram_users (chat_id, username, first_name, last_name) 
        VALUES (:chat_id, :username, :first_name, :last_name) 
        ON CONFLICT (chat_id) DO NOTHING";
 $stmt = $pdo->prepare($pg);
 $stmt->bindParam(':chat_id', $chatId);
 $stmt->bindParam(':username', $username);
 $stmt->bindParam(':first_name', $first_name);
 $stmt->bindParam(':last_name', $last_name);
 return $stmt->execute();
}

function userExists($pdo, $username)
{
    $pg = "SELECT * FROM telegram_users WHERE username = :username";
    $stmt = $pdo->prepare($pg);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        return true;
    }else{
        return false;
    }
}
function generateTrelloLink($key)
{
    return "https://trello.com/1/authorize?expiration=never&name=TestAssigment&scope=read,write&response_type=token&key={$key}";
}
function SaveTrelloToken($pdo,$chatId,$userId,$trelloToken)
{
$pg = "INSERT INTO telegram_users (chat_id,username, token) VALUES (:chat_id,:username, :token) ON CONFLICT (username) DO UPDATE SET token = :token";
$stmt = $pdo->prepare($pg);
$stmt->execute(['chat_id'=>$chatId,'username' => $userId, 'token' => $trelloToken]);
}
function getTrelloToken($pdo,$username)
{
    $pg = "SELECT token FROM telegram_users WHERE username = :username";
    $stmt = $pdo->prepare($pg);
    $stmt->execute(['username' => $username]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['token'] : null;
}

function makeReport($telegram, $pdo, $chatId)
{
    $members = getUsersWithTrelloToken($pdo);
    $report = "";
    if (empty($members)) {
        $telegram->sendMessage($chatId, "No users connected with Trello.");
        return 0;
    }
    foreach ($members as $member) {
        $username = $member['username'];
        $trelloToken = getTrelloToken($pdo, $username);
        if ($trelloToken==NULL) {
            return "NO token";
            continue;
        }
        $taskInProgressCount = getTasksInProgress($trelloToken);
        if ($taskInProgressCount > 0) {
            $report .= "$username has $taskInProgressCount tasks in progress.\n";
        } else {
            $report .= "$username has no tasks in progress.\n";
        }
    }

    $telegram->sendMessage($chatId, $report);
}function getTasksInProgress($trelloToken) {
    $response = file_get_contents("https://api.trello.com/1/boards/670e3f38ab7c3d1a04c14a5c/cards?key=3fb515e2eb3c8d8fb857b5c75f23f383&token={$trelloToken}");
    $cards = json_decode($response, true);
    $count = 0;
    foreach ($cards as $card) {
            $count++;
        }
    return $count;
}
function getUsersWithTrelloToken($pdo)
{

    $pg = "SELECT username, token FROM telegram_users WHERE token IS NOT NULL";
    $stmt = $pdo->prepare($pg);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

echo "Webhook executed";


