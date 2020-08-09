<?php
if (!isset($argv))
	exit('Please run from command line.');

require_once('utils.php');
require_once('database.php');
require_once('config.php');
require_once('telegram-bot/class.php');
$db = new MyDB();
$TG = new Telegram();


/* Check unfinished post */
$posts = $db->getPosts(100);
$posts = array_reverse($posts);

foreach ($posts as $item)
	if ($item['facebook_id'] < 5) {
		$post = $item;
		break;
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

/* Send post to Facebook */
try {
	$pid = send_facebook($post);
	if ($pid <= 0)
		$pid = $post['facebook_id'] + 1;

	$db->updatePostSns($post['id'], 'facebook', $pid);
} catch (Exception $e) {
	echo "Send Facebook Error " . $e->getCode() . ': ' .$e->getMessage() . "\n";
	echo $e->lastResponse . "\n\n";
}

/* Update SNS ID */
$post = $db->getPostById($post['id']);

/* Update with link to other SNS */
$sns = [
	'Facebook' => 'facebook',
	'Telegram' => 'telegram',
];
foreach ($sns as $name => $key) {
	try {
		$func = "update_$key";
		if (!isset($post["{$key}_id"]) || $post["{$key}_id"] < 10)
			continue;  // not posted, could be be edit

		$func($post);
	} catch (Exception $e) {
		echo "Edit $name Error " . $e->getCode() . ': ' .$e->getMessage() . "\n";
		echo $e->lastResponse . "\n\n";
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

function update_facebook(array $post) {
	global $time, $dt, $link;

	$tips_all = [
		"投稿時將網址放在最後一行，發文會自動顯示頁面預覽",
		"電腦版投稿可以使用 Ctrl-V 上傳圖片",
		"使用交大網路投稿會自動填入驗證碼",
		"如想投稿 GIF 可上傳至 Giphy，並將連結置於文章末行",

		"透過自動化審文系統，多數投稿會在 10 分鐘內發出",
		"所有人皆可匿名投稿，全校師生皆可具名審核",
		"靠北交大 2.0 採自助式審文，全校師生皆能登入審核",
		"靠北交大 2.0 有 50% 以上投稿來自交大 IP 位址",
		"登入後可看到 140.113.**.*87 格式的部分 IP 位址",

		"靠北交大 2.0 除了 Facebook 外，還支援 Twitter、Plurk 等平台\nhttps://twitter.com/x_NCTU/",
		"靠北交大 2.0 除了 Facebook 外，還支援 Plurk、Twitter 等平台\nhttps://www.plurk.com/xNCTU",
		"加入靠北交大 2.0 Telegram 頻道，第一時間看到所有貼文\nhttps://t.me/xNCTU",
		"你知道靠交也有 Instagram 帳號嗎？只要投稿圖片就會同步發佈至 IG 喔\nhttps://www.instagram.com/x_nctu/",
		"告白交大 2.0 使用同套系統，在此為大家服務\nhttps://www.facebook.com/CrushNCTU/",

		"審核紀錄公開透明，你可以看到誰以什麼原因通過/駁回了投稿\nhttps://x.nctu.app/posts",
		"覺得審核太慢嗎？你也可以來投票\nhttps://x.nctu.app/review",
		"網站上「已刪投稿」區域可以看到被黑箱的記錄\nhttps://x.nctu.app/deleted",
		"知道都是哪些系的同學在審文嗎？打開排行榜看看吧\nhttps://x.nctu.app/ranking",
		"秉持公開透明原則，您可以在透明度報告看到師長同學請求刪文的紀錄\nhttps://x.nctu.app/transparency",
		"靠交 2.0 是交大資工學生自行開發的系統，程式原始碼公開於 GitHub 平台\nhttps://github.com/Sea-n/xNCTU",
	];
	assert(count($tips_all) % 7 != 0);  // current count = 20
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
	$vote2 = $post['approvals'] - $post['rejects']*2;
	if ($dt <= 10 || $vote2 >= 6)
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

	if (strlen($result['id'] ?? '') > 10)
		return;  // Success, id = Comment ID

	$fb_id = $result['post_id'] ?? $result['id'] ?? '0_0';
	$post_id = (int) explode('_', $fb_id)[0];

	if ($post_id != $post['facebook_id']) {
		echo "Facebook comment error:";
		var_dump($result);
	}
}
