<?php
require_once('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');

function sendReview(string $uid) {
	$db = new MyDB();

	$post = $db->getSubmissionByUid($uid);
	$body = $post['body'];
	$has_img = $post['has_img'];

	$USERS = $db->getTgUsers();

	foreach ($USERS as $user) {
		if (!isset($user['tg_name']))
			continue;

		$result = sendPost($uid, $body, $has_img, $user['tg_id']);

		if (!$result['ok']) {
			if ($result['description'] == 'Bad Request: chat not found')
				$db->removeUserTg($user['tg_id']);
			continue;
		}

		$db->setTgMsg($uid, $user['tg_id'], $result['result']['message_id']);
	}
}

function sendPost(string $uid, string $body, bool $has_img, int $id) {
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
			'text' => $body,
			'reply_markup' => $keyboard,
			'disable_notification' => $dnd
		]);
	else
		return getTelegram('sendPhoto', [
			'bot' => 'xNCTU',
			'chat_id' => $id,
			'photo' => "https://x.nctu.app/img/$uid.jpg",
			'caption' => $body,
			'reply_markup' => $keyboard,
			'disable_notification' => $dnd
		]);
}
