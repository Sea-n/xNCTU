<?php
session_start();
require_once('utils.php');
require_once('config.php');
require_once('database.php');
$db = new MyDB();

if (!isset($_GET['code'])) {
	if (preg_match('#^/[a-z]*(\?[a-z0-9=&]*)?$#i', $_GET['r'] ?? 'X'))
		$_SESSION['redir'] = $_GET['r'];

	fail('Redirecting...', 0);
}

$curl = curl_init('https://id.nctu.edu.tw/o/token/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, [
	'grant_type' => 'authorization_code',
	'code' => $_GET['code'],
	'client_id' => NCTU_OAUTH_ID,
	'client_secret' => NCTU_OAUTH_SECRET,
	'redirect_uri' => "https://$DOMAIN/login-nctu",
]);
$data = curl_exec($curl);
curl_close($curl);
$data = json_decode($data, true);
if (isset($data['error']))
	fail($data['error'], 5);

if (!isset($data['access_token']))
	fail('No access token.', 5);

$token = $data['access_token'];
$header = ["Authorization: Bearer $token"];

$curl = curl_init('https://id.nctu.edu.tw/api/profile/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
$data = curl_exec($curl);
$data = json_decode($data, true);


if (!isset($data['username']))
	fail('No username from NCTU.', 5);

if (!isset($data['email']))
	fail('No email from NCTU.', 5);

$USER = $db->getUserByStuid($data['username']);
if (!$USER)
	$db->insertUserStuid($data['username'], $data['email']);

$_SESSION['stuid'] = $data['username'];

echo "Login success!";
$uri = '/';
if (isset($_SESSION['redir'])) {
	$uri = $_SESSION['redir'];
	unset($_SESSION['redir']);
}
header("Location: $uri");


function fail(string $msg = '', int $time) {
	$url = 'https://id.nctu.edu.tw/o/authorize/?' . http_build_query([
		'client_id' => NCTU_OAUTH_ID,
		'response_type' => 'code',
		'scope' => 'profile name'
	]);

	if ($time == 0)
		header("Location: $url");

	echo "<meta http-equiv='refresh' content='$time; url=$url' />";

	if (!empty($msg))
		echo "<h1>$msg</h1>";

	echo "Redirect in $time seconds. <a href='$url'>Click me</a>";
	exit;
}
