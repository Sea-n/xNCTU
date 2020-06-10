<?php
if (!isset($argv))
	exit('Please run from command line.');

require_once('utils.php');
require_once('database.php');
require_once('config.php');
require_once('telegram-bot/class.php');
$db = new MyDB();
$TG = new Telegram();

$cmd = $argv[1] ?? '';
if ($cmd == 'update') {
	if (!isset($argv[2]))
		exit('No ID.');

	$post = $db->getPostById($argv[2]);

	update_telegram($post);
	exit;
}


/* Check unfinished post */
$posts = $db->getPosts(1);
if (isset($posts[0]) && $posts[0]['status'] == 4)
	$post = $posts[0];

/* Get all pending submissions, oldest first */
if (!isset($post)) {
	$submissions = $db->getSubmissions(0, false);

	foreach ($submissions as $item) {
		if (checkEligible($item)) {
			$post = $db->setPostId($item['uid']);
			break;
		}
	}
}

if (!isset($post))
	exit;


/* Prepare post content */
assert(isset($post['id']));
$uid = $post['uid'];

$created = strtotime($post['created_at']);
$time = date("Y 年 m 月 d 日 H:i", $created);
$dt = floor(time() / 60) - floor($created / 60);  // Use what user see (without seconds)

$link = "https://$DOMAIN/post/{$post['id']}";

/* Send post to every SNS */
$sns = [
	'Telegram' => 'telegram',
	'Plurk' => 'plurk',
	'Facebook' => 'facebook',
	'Twitter' => 'twitter',
	'Instagram' => 'instagram',
];
foreach ($sns as $name => $key) {
	try {
		$func = "send_$key";
		if (isset($post["{$key}_id"]) && ($post["{$key}_id"] > 0 || strlen($post["{$key}_id"]) > 1))
			continue;

		$pid = $func($post);

		if ($pid <= 0) { // Retry limit exceed
			$dtP = time() - strtotime($post['posted_at']);
			if ($dtP > 3*5*60) // Total 3 times
				$pid = 1;
		}

		if ($pid > 0)
			$db->updatePostSns($post['id'], $key, $pid);
		$post["{$key}_id"] = $pid;
	} catch (Exception $e) {
		echo "Send $name Error " . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n\n";
	}
}

/* Update with link to other SNS */
$sns = [
	'Telegram' => 'telegram',
	'Plurk' => 'plurk',
	'Facebook' => 'facebook',
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
	$TG->deleteMsg($item['chat_id'], $item['msg_id']);
	$db->deleteTgMsg($uid, $item['chat_id']);
}


function checkEligible(array $post): bool {
	/* Prevent publish demo post */
	if ($post['status'] != 3)
		return false;

	$dt = floor(time() / 60) - floor(strtotime($post['created_at']) / 60);
	$vote = $post['approvals'] - $post['rejects'];

	/* Rule for Logged-in users */
	if (!empty($post['author_id'])) {
		/* Less than 2 min */
		if ($dt < 2)
			return false;

		/* No reject: 3 votes */
		if ($post['rejects'] == 0 && $vote >= 3)
			return true;

		/* More than 10 min */
		if ($dt < 10)
			return false;

		if ($vote < 0)
			return false;

		return true;
	}

	/* Rule for NCTU IP address */
	if ($post['author_name'] == '匿名, 交大') {
		/* Night mode: 02:00 - 07:59 */
		if (2 <= idate('H') && idate('H') <= 7) {
			if ($vote < 3)
				return false;
		}

		/* Less than 3 min */
		if ($dt < 3)
			return false;

		/* No reject: 5 votes */
		if ($post['rejects'] == 0 && $vote >= 5)
			return true;

		/* Less than 10 min */
		if ($dt < 10)
			return false;

		/* 10 min - 1 hour */
		if ($dt < 60 && $vote < 2)
			return false;

		/* More than 1 hour */
		if ($vote < 0)
			return false;

		return true;
	}

	/* Rule for Taiwan IP address */
	if (strpos($post['author_name'], '境外') === false) {
		/* Less than 5 min */
		if ($dt < 5)
			return false;

		/* No reject: 7 votes */
		if ($post['rejects'] == 0 && $vote >= 7)
				return true;

		/* Less than 10 min */
		if ($dt < 10)
			return false;

		/* 10 min - 1 hour */
		if ($dt < 60 && $vote < 5)
			return false;

		/* More than 1 hour */
		if ($vote < 3)
			return false;

		return true;
	}

	/* Rule for Foreign IP address */
	if (true) {
		/* 10 min - 1 hour */
		if ($dt < 10)
			return false;

		if ($vote < 10)
			return false;

		return true;
	}
}

function send_telegram(array $post): int {
	global $TG, $link;

	/* Check latest line */
	$lines = explode("\n", $post['body']);
	$end = end($lines);
	$is_url = filter_var($end, FILTER_VALIDATE_URL);
	if (!$post['has_img'] && $is_url)
		$msg = "<a href='$end'>#</a><a href='$link'>靠交{$post['id']}</a>";
	else
		$msg = "<a href='$link'>#靠交{$post['id']}</a>";

	$msg .= "\n\n" . enHTML($post['body']);


	/* Send to @xNCTU */
	if (!$post['has_img'])
		$result = $TG->sendMsg([
			'chat_id' => '@xNCTU',
			'text' => $msg,
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => !$is_url
		]);
	else
		$result = $TG->sendPhoto([
			'chat_id' => '@xNCTU',
			'photo' => 'https://' . DOMAIN . "/img/{$post['uid']}.jpg",
			'caption' => $msg,
			'parse_mode' => 'HTML',
		]);

	$tg_id = $result['result']['message_id'];

	return $tg_id;
}

function send_twitter(array $post): int {
	global $link;

	$msg = "#靠交{$post['id']}\n\n{$post['body']}";
	if (strlen($msg) > 250)
		$msg = mb_substr($msg, 0, 120) . '...';
	$msg .= "\n\n✅ $link .";

	if ($post['has_img']) {
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

		$file = ['media' => curl_file_create(__DIR__ . "/img/{$post['uid']}.jpg")];

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
	if (!isset($result['id_str'])) {
		echo "Twitter error: ";
		var_dump($result);

		if ($result['errors']['message'] == "We can't complete this request because this link has been identified by Twitter or our partners as being potentially harmful. Visit our Help Center to learn more.")
			return 1;

		return 0;
	}

	return $result['id_str'];
}

function send_plurk(array $post): int {
	global $link;

	$msg = $post['has_img'] ? ('https://' . DOMAIN . "/img/{$post['uid']}.jpg\n") : '';
	$msg .= "#靠交{$post['id']}\n{$post['body']}";

	if (mb_strlen($msg) > 290)
		$msg = mb_substr($msg, 0, 290) . '...';

	$msg .= "\n\n✅ $link ($link)";

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
		return 0;
	}
}

function send_facebook(array $post): int {
	$msg = "#靠交{$post['id']}\n\n";
	$msg .= "{$post['body']}";

	$URL = 'https://graph.facebook.com/v6.0/' . FB_PAGES_ID . ($post['has_img'] ? '/photos' : '/feed');

	$data = ['access_token' => FB_ACCESS_TOKEN];
	if (!$post['has_img']) {
		$data['message'] = $msg;

		$lines = explode("\n", $post['body']);
		$end = end($lines);
		if (filter_var($end, FILTER_VALIDATE_URL) && strpos($end, 'facebook') === false)
			$data['link'] = $end;
	} else {
		$data['url'] = 'https://' . DOMAIN . "/img/{$post['uid']}.jpg";
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
	$post_id = (int) explode('_', $fb_id)[1];

	if ($post_id == 0) {
		echo "Facebook result error:";
		var_dump($result);
	}

	return $post_id;
}

function send_instagram(array $post): int {
	if (!$post['has_img'])
		return -1;

	system("(node " . __DIR__ . "/send-ig.js {$post['id']} "
		. ">> /temp/xnctu-ig.log 2>> /temp/xnctu-ig.err; "
		. "php " . __FILE__ . " {$post['id']}) &");

	return 0;
}


function update_telegram(array $post) {
	global $TG;

	$buttons = [];

	if ($post['facebook_id'] > 10)
		$buttons[] = [
			'text' => 'Facebook',
			'url' => "https://www.facebook.com/xNCTU/posts/{$post['facebook_id']}"
		];

	$plurk = base_convert($post['plurk_id'], 10, 36);
	if (strlen($plurk) > 1)
		$buttons[] = [
			'text' => 'Plurk',
			'url' => "https://www.plurk.com/p/$plurk"
		];

	if ($post['twitter_id'] > 10)
		$buttons[] = [
			'text' => 'Twitter',
			'url' => "https://twitter.com/x_NCTU/status/{$post['twitter_id']}"
		];

	if (strlen($post['instagram_id']) > 1)
		$buttons[] = [
			'text' => 'Instagram',
			'url' => "https://www.instagram.com/p/{$post['instagram_id']}"
		];

	$TG->editMarkup([
		'chat_id' => '@xNCTU',
		'message_id' => $post['telegram_id'],
		'reply_markup' => [
			'inline_keyboard' => [
				$buttons
			]
		]
	]);
}

function update_plurk(array $post) {
	global $time, $dt;

	if ($dt <= 60)
		$msg = "🕓 投稿時間：$time ($dt 分鐘前)\n\n";
	else
		$msg = "🕓 投稿時間：$time\n\n";

	if ($post['rejects'])
		$msg .= "審核結果：✅ 通過 {$post['approvals']} 票 / ❌ 駁回 {$post['rejects']} 票\n\n";
	else
		$msg .= "審核結果：✅ 通過 {$post['approvals']} 票\n\n";

	$msg .= "🥙 其他平台：https://www.facebook.com/xNCTU/posts/{$post['facebook_id']} (Facebook)"
		. "、https://twitter.com/x_NCTU/status/{$post['twitter_id']} (Twitter)";
	if (strlen($post['instagram_id']) > 1)
		$msg .= "、https://www.instagram.com/p/{$post['instagram_id']} (Instagram)";
	$msg .= "、https://t.me/xNCTU/{$post['telegram_id']} (Telegram)\n\n";

	$msg .= "👉 立即投稿：https://x.nctu.app/submit (https://x.nctu.app/submit)";

	$nonce     = md5(time());
	$timestamp = time();

	/* Add Plurk */
	$URL = 'https://www.plurk.com/APP/Responses/responseAdd?' . http_build_query([
		'plurk_id' => $post['plurk_id'],
		'content' => $msg,
		'qualifier' => 'freestyle',
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
		$oauth->getLastResponse();
	} catch (Exception $e) {
		echo "Plurk Message: $msg\n\n";
		echo 'Error ' . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n";
	}
}

function update_facebook(array $post) {
	global $time, $dt, $link;

	$tips_all = [
		"投稿時將網址放在最後一行，發文會自動顯示頁面預覽",
		"電腦版投稿可以使用 Ctrl-V 上傳圖片",
		"使用交大網路投稿會自動填入驗證碼",

		"透過自動化審文系統，多數投稿會在 10 分鐘內發出",
		"所有人皆可匿名投稿，全校師生皆可具名審核",
		"靠北交大 2.0 採自助式審文，全校師生皆能登入審核",
		"靠北交大 2.0 有 50% 以上投稿來自交大 IP 位址",
		"登入後可看到 140.113.**.*87 格式的部分 IP 位址",

		"靠北交大 2.0 除了 Facebook 外，還支援 Twitter、Plurk 等平台\nhttps://twitter.com/x_NCTU/",
		"靠北交大 2.0 除了 Facebook 外，還支援 Plurk、Twitter 等平台\nhttps://www.plurk.com/xNCTU",
		"你知道靠交也有 Instagram 帳號嗎？只要投稿圖片就會同步發佈至 IG 喔\nhttps://www.instagram.com/x_nctu/",
		"告白交大 2.0 使用同套系統，在此為大家服務\nhttps://www.facebook.com/CrushNCTU/",

		"審核紀錄公開透明，你可以看到誰以什麼原因通過/駁回了投稿\nhttps://x.nctu.app/posts",
		"覺得審核太慢嗎？你也可以來投票\nhttps://x.nctu.app/review",
		"網站上「已刪投稿」區域可以看到被黑箱的記錄\nhttps://x.nctu.app/deleted",
		"知道都是哪些系的同學在審文嗎？打開排行榜看看吧\nhttps://x.nctu.app/ranking",
	];
	assert(count($tips_all) % 7 != 0);
	$tips = $tips_all[ ($post['id'] * 7) % count($tips_all) ];

	$go_all = [
		"立即投稿",
		"匿名投稿",
		"投稿連結",
		"投稿點我",
		"我要投稿",
	];
	$go = $go_all[ mt_rand(0, count($go_all)-1) ];

	$msg = "\n";  // First line is empty
	if ($dt <= 60)
		$msg .= "🕓 投稿時間：$time ($dt 分鐘前)\n\n";
	else
		$msg .= "🕓 投稿時間：$time\n\n";

	if ($post['rejects'])
		$msg .= "🗳 審核結果：✅ 通過 {$post['approvals']} 票 / ❌ 駁回 {$post['rejects']} 票\n";
	else
		$msg .= "🗳 審核結果：✅ 通過 {$post['approvals']} 票\n";
	$msg .= "$link\n\n";

	$msg .= "---\n\n";
	if ($post['rejects'] == 0 && ($dt <= 9 || $post['approvals'] >= 8))
		$msg .= "💡 $tips\n\n";

	$msg .= "👉 {$go}： https://x.nctu.app/submit";

	$URL = 'https://graph.facebook.com/v6.0/' . FB_PAGES_ID . "_{$post['facebook_id']}/comments";

	$data = [
		'access_token' => FB_ACCESS_TOKEN,
		'message' => $msg,
	];

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
	$post_id = (int) explode('_', $fb_id)[0];

	if ($post_id != $post['facebook_id']) {
		echo "Facebook comment error:";
		var_dump($result);
	}
}
