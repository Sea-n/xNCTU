<?php
require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/database.php');

if (!isset($TG)) {
	require_once(__DIR__ . '/telegram-bot/class.php');
	$TG = new Telegram();
}

function sendReview(string $uid) {
	global $TG;

	$db = new MyDB();

	$post = $db->getPostByUid($uid);

	$status = $post['status'];
	assert($status == 1);

	$hashtag = "#投稿$uid";

	$author_name = $post['author_name'];
	$hashtag_author = '#' . preg_replace('/[ ,]+/u', '_', $author_name);

	$ip_addr = $post['ip_addr'];

	$ip_masked = ip_mask($ip_addr);
	if (strpos($author_name, '境外') !== false)
		$ip_masked = $ip_addr;

	$ip_masked = preg_replace('/[.:*]/u', '_', $ip_masked);
	$ip_masked = preg_replace('/___+/', '___', $ip_masked);

	if (strpos($ip_addr, ':') !== false)
		$hashtag_ip = "#IPv6_$ip_masked";
	else
		$hashtag_ip = "#IPv4_$ip_masked";

	$msg = $post['body'];
	if (empty($post['author_id'])) {
		$msg .= "\n\n$hashtag | $hashtag_author | $hashtag_ip";
	} else {
		if (preg_match('/^\d+$/', $author_name))
			$hashtag_author = "#N$author_name";
		$msg .= "\n\n$hashtag | $hashtag_author";
	}

	$has_img = $post['has_img'];

	$USERS = $db->getTgUsers();

	$db->updateSubmissionStatus($uid, 2);

	/* Send to Votes Log */
	$keyboard = [
		'inline_keyboard' => [
			[
				[
					'text' => '開啟審核頁面',
					'login_url' => [
						'url' => 'https://' . DOMAIN . "/login-tg?r=%2Freview%2F$uid"
					]
				]
			]
		]
	];
	if (!$has_img)
		$TG->sendMsg([
			'chat_id' => LOG_GROUP,
			'text' => $msg,
			'reply_markup' => $keyboard,
		]);
	else
		$TG->sendPhoto([
			'chat_id' => LOG_GROUP,
			'photo' => 'https://' . DOMAIN . "/img/$uid.jpg",
			'caption' => $msg,
			'reply_markup' => $keyboard,
		]);

	/* Send to Users */
	foreach ($USERS as $user) {
		if (!isset($user['tg_name']))
			continue;

		$result = sendPost($uid, $msg, $has_img, $user['tg_id']);

		if (!$result['ok']) {
			if ($result['description'] == 'Bad Request: chat not found')
				$db->removeUserTg($user['tg_id']);
			continue;
		}

		$db->setTgMsg($uid, $user['tg_id'], $result['result']['message_id']);
	}

	$db->updateSubmissionStatus($uid, 3);
}

function sendPost(string $uid, string $msg, bool $has_img, int $id) {
	global $TG;

	$keyboard = [
		'inline_keyboard' => [
			[
				[
					'text' => '✅ 通過',
					'callback_data' => "approve_$uid"
				],
				[
					'text' => '❌ 駁回',
					'callback_data' => "reject_$uid"
				]
			],
			[
				[
					'text' => '開啟審核頁面',
					'login_url' => [
						'url' => 'https://' . DOMAIN . "/login-tg?r=%2Freview%2F$uid"
					]
				]
			]
		]
	];

	/* Time period 00:00 - 09:59 */
	$dnd = (substr(date('H'), 0, 1) != '0');

	if (!$has_img)
		return $TG->sendMsg([
			'chat_id' => $id,
			'text' => $msg,
			'reply_markup' => $keyboard,
			'disable_notification' => $dnd
		]);
	else
		return $TG->sendPhoto([
			'chat_id' => $id,
			'photo' => 'https://' . DOMAIN . "/img/$uid.jpg",
			'caption' => $msg,
			'reply_markup' => $keyboard,
			'disable_notification' => $dnd
		]);
}
