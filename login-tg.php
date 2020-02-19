<?php
session_start();
require('config.php');
require('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');
$db = new MyDB();

if (!isset($_SESSION['nctu_id']))
	exit('You must login NCTU first.');

try {
	$auth_data = checkTelegramAuthorization($_GET);
} catch (Exception $e) {
	exit($e->getMessage());
}


$_SESSION['tg_data'] = $auth_data;
$_SESSION['tg_id'] = $auth_data['id'];
if (isset($auth_data['photo_url']))
	$_SESSION['photo'] = $auth_data['photo_url'];

$_SESSION['name'] = $auth_data['first_name'];
if (isset($auth_data['last_name']))
	$_SESSION['name'] .= ' ' . $auth_data['last_name'];
if (strlen($_SESSION['name']) > 20)
	$_SESSION['name'] = substr($_SESSION['name'], 0, 15) . '...';

$db->insertUserTg($_SESSION['nctu_id'], $auth_data);

$msg = "🎉 連結成功！\n\n將來有新投稿時，您將會收到推播，並可用 Telegram 內的按鈕審核貼文。";
sendMsg([
	'bot' => 'xNCTU',
	'chat_id' => $auth_data['id'],
	'text' => $msg
]);

echo 'Login success.';
header('Location: /');


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
