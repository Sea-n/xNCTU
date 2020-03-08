<?php
require_once('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');

function sendReview(string $uid) {
	$db = new MyDB();

	$post = $db->getPostByUid($uid);

	$status = $post['status'];
	assert($status == 1);

	$msg = $post['body'];
	$msg .= "\n\n投稿人：{$post['author_name']}";

	$has_img = $post['has_img'];

	$USERS = $db->getTgUsers();

	$db->updateSubmissionStatus($uid, 2);

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
						'url' => "https://x.nctu.app/login-tg?r=%2Freview%3Fuid%3D$uid"
					]
				]
			]
		]
	];

	/* Time period 00:00 - 09:59 */
	$dnd = (substr(date('H'), 0, 1) != '0');

	if (!$has_img)
		return sendMsg([
			'bot' => 'xNCTU',
			'chat_id' => $id,
			'text' => $msg,
			'reply_markup' => $keyboard,
			'disable_notification' => $dnd
		]);
	else
		return getTelegram('sendPhoto', [
			'bot' => 'xNCTU',
			'chat_id' => $id,
			'photo' => "https://x.nctu.app/img/$uid.jpg",
			'caption' => $msg,
			'reply_markup' => $keyboard,
			'disable_notification' => $dnd
		]);
}
