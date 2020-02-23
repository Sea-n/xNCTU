<?php
session_start();
require('config.php');
require('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');
$db = new MyDB();

$redir = '/';  # Default value

try {
	if (isset($_GET['r'])) {
		$redir = $_GET['r'];
		unset($_GET['r']);
	}
	$auth_data = checkTelegramAuthorization($_GET);
} catch (Exception $e) {
	exit($e->getMessage());
}

$USER = $db->getUserByTg($auth_data['id']);

if ($USER) {
	$db->updateUserTgProfile($auth_data);

	if (!isset($_SESSION['nctu_id'])) {
		$_SESSION['nctu_id'] = $USER['nctu_id'];
		redirect('Login via Telegram success.');
	}

	if ($_SESSION['nctu_id'] == $USER['nctu_id'])
		redirect('Already login.');

	$db->insertUserTg($_SESSION['nctu_id'], $auth_data);
	sendMsg([
		'bot' => 'xNCTU',
		'chat_id' => $auth_data['id'],
		'text' => "🎉 連結成功！\n\n" .
		"您成功找出 bug 了，將不同的 NCTU OAuth 帳號連結至同一個 Telegram 帳號，目前還沒想到如何處理比較適當，歡迎提供建議\n\n" .
		"NCTU ID from session: {$_SESSION['nctu_id']}\n" .
		"NCTU ID from database: {$USER['nctu_id']}\n" .
		"Telegram UID: {$auth_data['id']}"
	]);
	redirect('Account linked again.');
}

if (!isset($_SESSION['nctu_id']))
	exit('You must login NCTU first. 請先於首頁右上角登入交大帳號');

$db->insertUserTg($_SESSION['nctu_id'], $auth_data);

$msg = "🎉 連結成功！\n\n將來有新投稿時，您將會收到推播，並可用 Telegram 內的按鈕審核貼文。";
sendMsg([
	'bot' => 'xNCTU',
	'chat_id' => $auth_data['id'],
	'text' => $msg
]);

redirect('Login success.');


function redirect(string $msg) {
	global $redir;
	echo $msg;
	header("Location: $redir");
	exit;
}

function checkTelegramAuthorization($auth_data) {
	if (!isset($auth_data['id']))
		throw new Exception('No User ID.');

	if (!isset($auth_data['username']))
		throw new Exception('No username.');

	if (!isset($auth_data['hash']))
		throw new Exception('No Telegram hash.');

	$check_hash = $auth_data['hash'];
	unset($auth_data['hash']);

	$data_check_arr = [];

	foreach ($auth_data as $key => $value)
		$data_check_arr[] = $key . '=' . $value;

	sort($data_check_arr);
	$data_check_string = implode("\n", $data_check_arr);

	$secret_key = hash('sha256', BOT_TOKEN, true);
	$hash = hash_hmac('sha256', $data_check_string, $secret_key);

	if (!hash_equals($hash, $check_hash))
		throw new Exception('Data is NOT from Telegram.');

	if ((time() - $auth_data['auth_date']) > 365*24*60*60)
		throw new Exception('Session expired.');

	$auth_data['hash'] = $check_hash;
	return $auth_data;
}
