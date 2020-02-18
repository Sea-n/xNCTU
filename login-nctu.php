<?php
session_start();
require('config.php');

if (!isset($_GET['code']))
	fail('Please login', 0);

$curl = curl_init('https://id.nctu.edu.tw/o/token/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, [
	'grant_type' => 'authorization_code',
	'code' => $_GET['code'],
	'client_id' => NCTU_OAUTH_ID,
	'client_secret' => NCTU_OAUTH_SECRET,
	'redirect_uri' => 'https://x.nctu.app/login-nctu'
]);
$data = curl_exec($curl);
curl_close($curl);
$data = json_decode($data, true);
if (isset($data['error']))
	fail($data['error']);

if (!isset($data['access_token']))
	fail('No access token.');

$token = $data['access_token'];
$header = ["Authorization: Bearer $token"];

$curl = curl_init('https://id.nctu.edu.tw/api/profile/');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
$data = curl_exec($curl);
$data = json_decode($data, true);

if (!isset($data['username']))
	fail('No username from NCTU.');
$_SESSION['nctu_id'] = $data['username'];

$_SESSION['name'] = $data['username'];

if (!isset($data['email']))
	fail('No email from NCTU.');
$_SESSION['nctu_mail'] = $data['email'];

echo "Login success!";
header('Location: /');


function fail(string $msg = '', int $time = 0) {
	$url = 'https://id.nctu.edu.tw/o/authorize/?' . http_build_query([
		'client_id' => NCTU_OAUTH_ID,
		'response_type' => 'code',
		'scope' => 'profile name'
	]);

	echo "<meta http-equiv='refresh' content='$time; url=$url' />";

	if (!empty($msg))
		echo "<h1>$msg</h1>";

	echo "Redirect in $time seconds. <a href='$url'>Click me</a>";
	exit;
}
