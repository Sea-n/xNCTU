<?php
session_start();
require_once('utils.php');
require_once('config.php');
require_once('database.php');
$db = new MyDB();

if (isset($_SESSION['stuid'])) {
	$uri = '/';
	if (isset($_SESSION['redir'])) {
		$uri = $_SESSION['redir'];
		unset($_SESSION['redir']);
	}
	header("Location: $uri");
	exit;
}

if (isset($_SESSION['google_sub'])) {
	header('Location: /verify');
	exit;
}


if (!isset($_GET['code'])) {
	if (preg_match('#^/[a-z]*(\?[a-z0-9=&]*)?$#i', $_GET['r'] ?? 'X'))
		$_SESSION['redir'] = $_GET['r'];

	fail('Redirecting...', 0);
}

$curl = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, [
	'grant_type' => 'authorization_code',
	'code' => $_GET['code'],
	'client_id' => GOOGLE_OAUTH_ID,
	'client_secret' => GOOGLE_OAUTH_SECRET,
	'redirect_uri' => "https://$DOMAIN/login-google",
]);
$data = curl_exec($curl);
curl_close($curl);
$data = json_decode($data, true);
if (isset($data['error']))
	fail($data['error'], 5);

if (!isset($data['access_token']))
	fail('No access token.', 5);

$token = $data['id_token'];

$curl = curl_init("https://oauth2.googleapis.com/tokeninfo?id_token=$token");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$data = curl_exec($curl);
$data = json_decode($data, true);


if (!isset($data['name']))
	fail('No name from Google.', 5);

if (!isset($data['email']))
	fail('No email from Google.', 5);

echo json_encode($data, JSON_PRETTY_PRINT);

$GOOGLE = $db->getGoogleBySub($data['sub']);
if (!$GOOGLE)
	$db->insertGoogle($data);

if (!empty($GOOGLE['stuid'])) {
	$_SESSION['stuid'] = $GOOGLE['stuid'];
	echo "Login success!";

	$uri = '/';
	if (isset($_SESSION['redir'])) {
		$uri = $_SESSION['redir'];
		unset($_SESSION['redir']);
	}
	header("Location: $uri");
} else {
	$_SESSION['google_sub'] = $data['sub'];
	echo "Please verify your NCTU account.";

	header("Location: /verify");
}


function fail(string $msg = '', int $time) {
	global $DOMAIN;

	$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
		'client_id' => GOOGLE_OAUTH_ID,
		'redirect_uri' => "https://$DOMAIN/login-google",
		'response_type' => 'code',
		'scope' => 'email profile'
	]);

	if ($time == 0)
		header("Location: $url");

	echo "<meta http-equiv='refresh' content='$time; url=$url' />";

	if (!empty($msg))
		echo "<h1>$msg</h1>";

	echo "Redirect in $time seconds. <a href='$url'>Click me</a>";
	exit;
}
