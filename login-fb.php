<?php
require_once('config.php');

if (!isset($_GET['code'])) {
	$url = 'https://www.facebook.com/v8.0/dialog/oauth'
		. '?client_id=' . FB_APP_ID
		. '&redirect_uri=' . urlencode('https://x.nctu.app/login-fb')
		. '&response_type=code'
		. '&scope=pages_show_list,pages_read_engagement,pages_manage_metadata,pages_read_user_content,pages_manage_posts,pages_manage_engagement,public_profile';
	header("Location: $url");
	exit;
}

$url = 'https://graph.facebook.com/v8.0/oauth/access_token'
	. '?client_id=' . FB_APP_ID
	. '&redirect_uri=' . urlencode('https://x.nctu.app/login-fb')
	. '&client_secret=' . FB_APP_SECRET
	. '&code=' . urlencode($_GET['code']);
$data = file_get_contents($url);
$data = json_decode($data, true);

if ($data['error']['code'] ?? 0 == 100)
	header('Location: /login-fb');

if (!isset($data['access_token']))
	exit('No access token');

$user_token = $data['access_token'];

$me = file_get_contents("https://graph.facebook.com/v8.0/me?access_token=$user_token");
$me = json_decode($me, true);
$user_id = $me['id'];

$accounts = file_get_contents("https://graph.facebook.com/$user_id/accounts?access_token=$user_token");
$accounts = json_decode($accounts, true);

$page_tokens = [];
foreach ($accounts['data'] as $item)
	$page_tokens[ $item['id'] ] = $item['access_token'];

$short_token = $page_tokens[FB_PAGES_ID];

$url = 'https://graph.facebook.com/v8.0/oauth/access_token'
	. '?client_id=' . FB_APP_ID
	. '&client_secret=' . FB_APP_SECRET
	. '&fb_exchange_token=' . $short_token
	. '&grant_type=fb_exchange_token';
$data = file_get_contents($url);
$data = json_decode($data, true);

$long_token = $data['access_token'];
echo $long_token;
