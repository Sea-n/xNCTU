<?php
require_once('config.php');

function send_telegram() {
	require('/root/site/telegram/function.php');
	require('/root/site/telegram/config.php');
	$msg = enHTML("$hn_title\n" .
	"🕓 $hn_during\n");
	if (!empty($hn_range))
		$msg .= enHTML("🎯 $hn_range\n");
	$msg .= enHTML("\n$hn_description\n") .
	enHTML($link);

	if ($debug)
		echo "Telegram Message:\n$msg\n\n";

	$result = sendMsg([
		'bot' => 'Sean',
		'chat_id' => '@HiNetNotify',
		'text' => $msg,
		'parse_mode' => 'HTML',
		'disable_web_page_preview' => true
	]);

	return $result['result']['message_id'];
}


function send_twitter() {
	$nonce     = md5(time());
	$timestamp = time();

	$msg = "$hn_title\n";
	$msg .= "🕓 $hn_during\n";
	if (!empty($hn_range))
		$msg .= "🎯 $hn_range\n";
	$msg .= "\n$hn_description";

	$msgS = twitterSubstr($msg);
	if (strlen($msg) != strlen($msgS))
		$msg = "$msgS...";
	$msg .= "\n$link";
	if ($debug)
		echo "Twitter Message:\n$msg\n\n";

	$URL = 'https://api.twitter.com/1.1/statuses/update.json?' . http_build_query([
		'status' => $msg
	]);

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

function send_plurk() {
	$nonce     = md5(time());
	$timestamp = time();

	$msg = "$hn_title\n\n" .
	"🕓 $hn_during\n\n";
	if (!empty($hn_range))
		$msg .= "🎯 $hn_range";

	/* Add Plurk */
	$URL = 'https://www.plurk.com/APP/Timeline/plurkAdd?' . http_build_query([
		'content' => $msg,
		'qualifier' => 'shares',
		'lang' => 'tr_ch',
	]);

	echo "Plurk URL: $URL\n\n";

	$oauth = new OAuth(PLURK_CONSUMER_KEY, PLURK_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
	$oauth->enableDebug();
	$oauth->setToken(PLURK_TOKEN, PLURK_TOKEN_SECRET);
	$oauth->setNonce($nonce);
	$oauth->setTimestamp($timestamp);
	$signature = $oauth->generateSignature('POST', $URL);

	echo "Plurk Signature: $signature\n\n";

	$oauth->fetch($URL);
	$result = $oauth->getLastResponse();

	echo "Plurk Result: $result\n\n";

	$result = json_decode($result, true);
	$plurk_id = $result['plurk_id'];

	echo "Plurk ID: $plurk_id\n";

	/* Add Comment */
	$nonce     = md5(time());
	$timestamp = time();

	$msg = "$hn_description\n\n" .
	"$link (link)";

	$URL = 'https://www.plurk.com/APP/Responses/responseAdd?' . http_build_query([
		'plurk_id' => $plurk_id,
		'content' => $msg,
		'qualifier' => 'freestyle',
	]);

	$oauth = new OAuth(PLURK_CONSUMER_KEY, PLURK_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1);
	$oauth->enableDebug();
	$oauth->setToken(PLURK_TOKEN, PLURK_TOKEN_SECRET);
	$oauth->setNonce($nonce);
	$oauth->setTimestamp($timestamp);
	$signature = $oauth->generateSignature('POST', $URL);

	$oauth->fetch($URL);
	$result = $oauth->getLastResponse();

	return $plurk_id;
}

function send_facebook() {
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => 'https://graph.facebook.com/v2.6/' . FB_PAGES_ID . '/feed?access_token=' . FB_ACCESS_TOKEN,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true
	]);

	if (preg_match('/(海纜|緊急|國際)/', $hn_title)) {
		$msg = "$hn_title\n\n" .
			"- - - - -\n\n" .
			"✳️ 推薦使用 Telegram 及 Twitter 接收即時通知\n" .
			"避免重要資訊漏接\n\n" .
			"🔹 Telegram: https://t.me/HiNetNotify/{$tg_post_id}\n" .
			"🔹 Twitter: https://twitter.com/HiNetNotify/status/{$twitter_post_id}\n";

		curl_setopt($curl, CURLOPT_POSTFIELDS, [
			'message' => $msg,
			'link' => "https://t.me/HiNetNotify/{$tg_post_id}"
		]);
	} else if (preg_match('/(骨幹|DNS)/', $hn_title)) {
		$msg = "$hn_title\n\n" .
			"$hn_description\n\n" .
			"= = =  你知道嗎?  = = =\n" .
			"此服務亦支援 Twitter 及 Telegram 喔！\n" .
			"Twitter: https://twitter.com/HiNetNotify/status/{$twitter_post_id}\n" .
			"Telegram: https://t.me/HiNetNotify/{$tg_post_id}\n" .
			"趕緊加入吧！ 訊息不再漏接！\n\n";

		curl_setopt($curl, CURLOPT_POSTFIELDS, [
			'message' => $msg,
			'link' => "https://twitter.com/HiNetNotify/status/{$twitter_post_id}"
		]);
	} else {
		$msg = "$hn_title\n\n" .
			"$hn_description\n\n" .
			"$link";

		curl_setopt($curl, CURLOPT_POSTFIELDS, [
			'message' => $msg,
		]);
	}
	if ($debug)
		echo "Facebook Message:\n$msg\n\n";

	curl_exec($curl);
	curl_close($curl);


	if (preg_match('/(海纜|緊急|國際)/', $hn_title)) {
		getTelegram('forwardMessage', [
			'from_chat_id' => '@HiNetNotify',
			'message_id' => $tg_post_id,
			'chat_id' => -1001343575015   // 網通人
		]);
	}
}


/*
 * Functions
 */


/*
 * Shorten URL
 * @param string $longUrl
 * @return string $shortUrl Shortened URL
 */
function shortenUrl(string $longUrl): string {
	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_URL => 'https://tg.pe/api',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => [
			'token' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
			'url' => $longUrl,
			'prefix' => 'hn',
			'len' => 4
		]
	]);

	$result = curl_exec($curl);
	curl_close($curl);
	$json = json_decode($result, true);
	return 'https://tg.pe/' . $json['shortLink'];
}

/*
 * Substr to meet Twitter 280 chars limit
 * @param string $origin
 * @return string $str Length 256 (or less)
 */
function twitterSubstr(string $ori): string {
	$len = 253; // 280 - "..."(3) - newline(1) - link(23) = 253
	$str = "";
	do {
		$chr = mb_substr($ori, 0, 1);
		$ori = mb_substr($ori, 1);
		$clen = (strlen($chr) > 1) ? 2 : 1;
		$len -= $clen;
		if ($len > 0)
			$str .= $chr;
	} while ($len > 0);
	return $str;
}
