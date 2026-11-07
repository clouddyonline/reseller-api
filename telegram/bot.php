<?php
require_once __DIR__."/../api.php";
const TELEGRAM_BOT_TOKEN = "YOUR_TELEGRAM_BOT_TOKEN";
const RESELLER_API_TOKEN = "YOUR_RESELLER_API_TOKEN";
$api = new \ClouDDy\Reseller\Api(RESELLER_API_TOKEN);
$offset = 0;
while ( true )
{
	$updates = telegramRequest(TELEGRAM_BOT_TOKEN, "getUpdates", array("offset" => $offset, "timeout" => 50));
	foreach ( $updates as $update )
	{
		$offset = $update["update_id"] + 1;
		handleUpdate(TELEGRAM_BOT_TOKEN, $api, $update);
	}
}
function handleUpdate($telegram, $api, $update)
{
	if ( isset($update["message"]["text"]) && strpos($update["message"]["text"], "/start") === 0 )
	{
		sendMessage($telegram, $update["message"]["chat"]["id"], "Press the button to create a new test account", array("inline_keyboard" => array(array( array("text" => "Request test account", "callback_data" => "request_test") ))));
		return;
	}
	if ( !isset($update["callback_query"]) || $update["callback_query"]["data"] !== "request_test" )
	{
		return;
	}
	$callback = $update["callback_query"];
	telegramRequest($telegram, "answerCallbackQuery", array("callback_query_id" => $callback["id"], "text" => "Creating test account…"));
	$chat = $callback["message"]["chat"]["id"];
	try
	{
		$message = createTestAccount($api);
	}
	catch ( Throwable $error )
	{
		$message = "Could not create the test account. Please try again";
	}
	sendMessage($telegram, $chat, $message);
}
function createTestAccount($api)
{
	$login = "test_".time()."_".random_int(1000, 9999);
	$created = $api->addUser(array("login" => $login));
	if ( !isset($created["created"], $created["user"]) || $created["created"] !== true )
	{
		throw new RuntimeException("User creation failed");
	}
	$user = $api->getUser($created["user"]);
	if ( !$user )
	{
		throw new RuntimeException("Created user was not returned by the users endpoint");
	}
	return "Test account created\n\nLogin: ".$user["email"]."\nPassword: ".$user["password"]."\nTariff: ".$user["plan"]."\nActive from: ".formatDate($user["start"] ?? null)."\nActive until: ".formatDate($user["end"] ?? null);
}
function formatDate($time)
{
	if ( !is_numeric($time) || $time <= 0 )
	{
		return "not set";
	}
	return date("Y-m-d H:i", $time);
}
function sendMessage($telegram, $chat, $text, $keyboard = null)
{
	$fields = array("chat_id" => $chat, "text" => $text);
	if ( $keyboard !== null )
	{
		$fields["reply_markup"] = $keyboard;
	}
	telegramRequest($telegram, "sendMessage", $fields);
}
function telegramRequest($telegram, $method, $fields)
{
	$curl = curl_init("https://api.telegram.org/bot".$telegram."/".$method);
	curl_setopt_array($curl, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($fields), CURLOPT_HTTPHEADER => array("Content-Type: application/json"), CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 60));
	$body = curl_exec($curl);
	if ( $body === false )
	{
		$error = curl_error($curl);
		curl_close($curl);
		throw new RuntimeException("Telegram request failed: ".$error);
	}
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	curl_close($curl);
	$response = json_decode($body, true);
	if ( json_last_error() !== JSON_ERROR_NONE || $status < 200 || $status >= 300 || empty($response["ok"]) )
	{
		throw new RuntimeException("Telegram request failed");
	}
	return $response["result"];
}
?>