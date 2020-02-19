<?php
require_once('utils.php');
require_once('database.php');
require_once('config.php');
require_once('/root/site/telegram/function.php');
$db = new MyDB();

if (!($post = $db->getPostReady()))
	exit;

$id = $post['id'];
$body = $post['body'];
$img = $post['img'];

$tg = send_telegram($id, $body, $img);
$plurk = send_plurk($id, $body, $img);
$twitter = send_twitter($id, $body, $img);
$fb = send_facebook($id, $body, $img);

$db->updatePostSns($id, $tg, $plurk, $twitter, $fb);


function send_telegram(int $id, string $body, string $img = ''): int {
	$link = "https://x.nctu.app/posts?id=$id";
	$msg = "<a href='$link'>#靠交$id</a>\n\n" . enHTML($body);

	if (empty($img))
		$result = sendMsg([
			'bot' => 'xNCTU',
			'chat_id' => '@xNCTU',
			'text' => $msg,
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true
		]);
	else
		$result = getTelegram('sendPhoto', [
			'bot' => 'xNCTU',
			'chat_id' => '@xNCTU',
			'photo' => "https://x.nctu.app/img/{$img}",
			'caption' => $msg,
			'parse_mode' => 'HTML',
		]);

	return $result['result']['message_id'];
}

function send_twitter(int $id, string $body, string $img = ''): int {
	$link = "https://x.nctu.app/posts?id=$id";
	$msg = "#靠交$id\n\n$body";
	if (strlen($msg) > 250)
		$msg = substr($msg, 0, 250) . '...';
	$msg .= "\n\n$link";

	if (!empty($img)) {
		$nonce     = md5(time());
		$timestamp = time();

		$URL = 'https://api.twitter.com/1.1/media/upload.json?media_category=tweet_image';

		$oauth = new OAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
		$oauth->enableDebug();
		$oauth->setToken(TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
		$oauth->setNonce($nonce);
		$oauth->setTimestamp($timestamp);
		$signature = $oauth->generateSignature('POST', $URL);

		$auth = [
			'oauth_consumer_key' => TWITTER_CONSUMER_KEY,
			'oauth_nonce' => $nonce,
			'oauth_signature' => $signature,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => $timestamp,
			'oauth_token' => TWITTER_TOKEN
		];

		$authStr = 'OAuth ';
		foreach ($auth as $key => $val)
			$authStr .= $key . '="' . urlencode($val) . '", ';
		$authStr .= 'oauth_version="1.0"';

		$file = ['media' => curl_file_create(__DIR__ . "/img/$img")];

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $URL,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $file,
			CURLOPT_HTTPHEADER => [
				"Authorization: $authStr"
			]
		]);
		$result = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($result, true);
		if (isset($result['media_id_string']))
			$img_id = $result['media_id_string'];
		else
			echo "Twitter upload error: " . json_encode($result) . "\n";
	}

	$nonce     = md5(time());
	$timestamp = time();

	$query = ['status' => $msg];
	if (!empty($img_id))
		$query['media_ids'] = $img_id;
	$URL = 'https://api.twitter.com/1.1/statuses/update.json?' . http_build_query($query);

	$oauth = new OAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
	$oauth->enableDebug();
	$oauth->setToken(TWITTER_TOKEN, TWITTER_TOKEN_SECRET);
	$oauth->setNonce($nonce);
	$oauth->setTimestamp($timestamp);
	$signature = $oauth->generateSignature('POST', $URL);

	$auth = [
		'oauth_consumer_key' => TWITTER_CONSUMER_KEY,
		'oauth_nonce' => $nonce,
		'oauth_signature' => $signature,
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_timestamp' => $timestamp,
		'oauth_token' => TWITTER_TOKEN
	];

	$authStr = 'OAuth ';
	foreach ($auth as $key => $val)
		$authStr .= $key . '="' . urlencode($val) . '", ';
	$authStr .= 'oauth_version="1.0"';

	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_URL => $URL,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => [
			"Authorization: $authStr"
		]
	]);
	$result = curl_exec($curl);
	curl_close($curl);

	$result = json_decode($result, true);
	return $result['id_str'];
}

function send_plurk(int $id, string $body, string $img = ''): int {
	$link = "https://x.nctu.app/posts?id=$id";

	$msg = empty($img) ? '' : "https://x.nctu.app/img/$img\n";
	$msg .= "#靠交$id\n$body";

	if (mb_strlen($msg) > 320)
		$msg = mb_substr($msg, 0, 320) . '...';

	$msg .= "\n$link";

	$nonce     = md5(time());
	$timestamp = time();

	/* Add Plurk */
	$URL = 'https://www.plurk.com/APP/Timeline/plurkAdd?' . http_build_query([
		'content' => $msg,
		'qualifier' => 'says',
		'lang' => 'tr_ch',
	]);

	var_dump($URL);

	$oauth = new OAuth(PLURK_CONSUMER_KEY, PLURK_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
	$oauth->enableDebug();
	$oauth->setToken(PLURK_TOKEN, PLURK_TOKEN_SECRET);
	$oauth->setNonce($nonce);
	$oauth->setTimestamp($timestamp);
	$signature = $oauth->generateSignature('POST', $URL);

	try {
		$oauth->fetch($URL);
		$result = $oauth->getLastResponse();
		$result = json_decode($result, true);
		return $result['plurk_id'];
	} catch (Exception $e) {
		echo 'Error ' . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n";
		return false;
	}
}

function send_facebook(int $id, string $body, string $img = ''): int {
	$link = "https://x.nctu.app/posts?id=$id";
	$msg = "#靠交$id\n\n$body\n\n$link";

	$URL = 'https://graph.facebook.com/v6.0/' . FB_PAGES_ID . (empty($img) ? '/feed' : '/photos');
   
	$data = ['access_token' => FB_ACCESS_TOKEN];
	if (empty($img))
		$data['message'] = $msg;
	else {
		$data['url'] = "https://x.nctu.app/img/$img";
		$data['caption'] = $msg;
	}

	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => $URL,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => $data
	]);

	$result = curl_exec($curl);
	curl_close($curl);
	$result = json_decode($result, true);

	$id = explode('_', $result['id'])[1];
	return $id;
}
