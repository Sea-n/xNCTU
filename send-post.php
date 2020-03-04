<?php
if (!isset($argv))
	exit('Please run from command line.');

require_once('utils.php');
require_once('database.php');
require_once('config.php');
require_once('/root/site/telegram/function.php');
$db = new MyDB();

if (!($post = $db->getPostReady()))
	exit;

/* Prepare post content */
$id = $post['id'];
$uid = $post['uid'];
$body = $post['body'];

/* img cannot be URL, Twitter required local file upload */
$img = $post['has_img'] ? $uid : '';

$time = strtotime($post['created_at']);
$time = date("Y 年 m 月 d 日 H:i", $time);
$link = "https://x.nctu.app/post/$id";

/* Send post to every SNS */
$sns = [
	'Telegram' => 'telegram',
	'Plurk' => 'plurk',
	'Twitter' => 'twitter',
	'Facebook' => 'facebook',
];
foreach ($sns as $name => $key) {
	try {
		$func = "send_$key";
		if (isset($post["{$key}_id"]) && $post["{$key}_id"] > 0)
			continue;

		$pid = $func($id, $body, $img);

		if ($pid > 0)
			$db->updatePostSns($id, $key, $pid);
		$post["{$key}_id"] = $pid;
	} catch (Exception $e) {
		echo "Send $name Error " . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n\n";
	}
}

/* Update with link to other SNS */
$sns = [
	'Telegram' => 'telegram',
];
foreach ($sns as $name => $key) {
	try {
		$func = "update_$key";
		if (!isset($post["{$key}_id"]) || $post["{$key}_id"] < 0)
			continue;  // not posted, could be be edit

		$func($post);
	} catch (Exception $e) {
		echo "Edit $name Error " . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n\n";
	}
}

/* Remove vote keyboard in Telegram */
$msgs = $db->getTgMsgsByUid($uid);
foreach ($msgs as $item) {
	getTelegram('editMessageReplyMarkup', [
		'bot' => 'xNCTU',
		'chat_id' => $item['chat_id'],
		'message_id' => $item['msg_id'],
		'reply_markup' => [
			'inline_keyboard' => [
				[
					[
						'text' => '開啟審核頁面',
						'login_url' => [
							'url' => "https://x.nctu.app/login-tg?r=%2Freview%3Fuid%3D$uid"
						]
					]
				]
			]
		]
	]);
	$db->deleteTgMsg($uid, $item['chat_id']);
}


function send_telegram(int $id, string $body, string $img = ''): int {
	global $link;

	/* Send to @xNCTU */
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
			'photo' => "https://x.nctu.app/img/{$img}.jpg",
			'caption' => $msg,
			'parse_mode' => 'HTML',
		]);

	$tg_id = $result['result']['message_id'];

	return $tg_id;
}

function send_twitter(int $id, string $body, string $img = ''): int {
	global $link;
	$msg = "#靠交$id\n\n$body";
	if (strlen($msg) > 250)
		$msg = mb_substr($msg, 0, 120) . '...';
	$msg .= "\n\n$link";

	if (!empty($img)) {
		$nonce     = md5(time());
		$timestamp = time();

		$URL = 'https://upload.twitter.com/1.1/media/upload.json?media_category=tweet_image';

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

		$file = ['media' => curl_file_create(__DIR__ . "/img/$img.jpg")];

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
	global $link;

	$msg = empty($img) ? '' : "https://x.nctu.app/img/$img.jpg\n";
	$msg .= "#靠交$id\n$body";

	if (mb_strlen($msg) > 320)
		$msg = mb_substr($msg, 0, 320) . '...';

	$msg .= "\n\n$link (全平台連結)";

	$nonce     = md5(time());
	$timestamp = time();

	/* Add Plurk */
	$URL = 'https://www.plurk.com/APP/Timeline/plurkAdd?' . http_build_query([
		'content' => $msg,
		'qualifier' => 'says',
		'lang' => 'tr_ch',
	]);

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
		echo "Plurk Message: $msg\n\n";
		echo 'Error ' . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n";
		return 1;  # ignore error
	}
}

function send_facebook_mbasic(int $id, string $body, string $img = ''): int {
	global $link, $time;
	$msg = "#靠交$id\n\n";
	$msg .= "$body\n\n";
	$msg .= "投稿時間：$time\n\n";
	if (!empty($img))
		$msg .= "$link";

	$header = [
		'Cookie: ' . FB_COOKIE,
		'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:74.0) Gecko/20100101 Firefox/74.0'
	];

	$curl = curl_init('https://mbasic.facebook.com/xNCTU/');
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_HEADER, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$data = curl_exec($curl);
	if (!preg_match('#<input type="hidden" name="fb_dtsg" value="([^"]+)"#', $data, $matches)) {
		echo "No dstg tag:\n";
		var_dump($data);
		return 0;
	}
	$dtsg = $matches[1];

	$data = [
		'fb_dtsg' => $dtsg,
		'xc_message' => $msg,
		'jazoest' => rand(21000, 23000),
		'r2a' => 1,
		'target' => FB_PAGES_ID,
		'c_src' => 'page_self',
		'cwevent' => 'composer_entry',
		'referrer' => 'pages_feed',
		'ctype' => 'inline',
		'cver' => 'amber',
		'rst_icv' => '',
		'view_post' => 'Post'
	];

	$curl = curl_init('https://mbasic.facebook.com/composer/mbasic/?av=' . FB_PAGES_ID);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
	curl_setopt($curl, CURLOPT_HEADER, true);
	$data = curl_exec($curl);
	if (strpos($data, 'Location: https://mbasic.facebook.com/xNCTU/?v=feed&_rdr') === false)
		return 0;

	$curl = curl_init('https://mbasic.facebook.com/xNCTU/');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	$data = curl_exec($curl);
	if (preg_match("!#靠交$id.*? id=\"like_(\d+)\"!", $data, $matches)) {
		$pid = (int) $matches[1];
		return $pid;
	} else
		return 1;
}

function send_facebook(int $id, string $body, string $img = ''): int {
	global $link, $time;
	$msg = "#靠交$id\n\n";
	$msg .= "$body\n\n";
	$msg .= "投稿時間：$time\n";
	$msg .= "✅ $link";

	$URL = 'https://graph.facebook.com/v6.0/' . FB_PAGES_ID . (empty($img) ? '/feed' : '/photos');
   
	$data = ['access_token' => FB_ACCESS_TOKEN];
	if (empty($img))
		$data['message'] = $msg;
	else {
		$data['url'] = "https://x.nctu.app/img/$img.jpg";
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

	$fb_id = $result['post_id'] ?? $result['id'] ?? '0_0';
	$post_id = (int) explode('_', $result['id'])[0];

	if ($post_id == 0) {
		echo "Facebook result error:";
		var_dump($result);
	}

	return $post_id;
}

function update_telegram(array $post) {
	$plurk = base_convert($post['plurk_id'], 10, 36);
	getTelegram('editMessageReplyMarkup', [
		'chat_id' => '@xNCTU',
		'message_id' => $post['telegram_id'],
		'reply_markup' => [
			'inline_keyboard' => [
				[
					[
						'text' => 'Facebook',
						'url' => "https://www.facebook.com/xNCTU/posts/{$post['facebook_id']}"
					],
					[
						'text' => 'Plurk',
						'url' => "https://www.plurk.com/p/$plurk"
					],
					[
						'text' => 'Twitter',
						'url' => "https://twitter.com/x_NCTU/status/{$post['twitter_id']}"
					],
				]
			]
		]
	]);
}
