<?php
require_once('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');

function sendReview(string $uid, string $body, string $img) {
	$db = new MyDB();
	$USERS = $db->getTgUsers();

	foreach ($USERS as $user) {
		sendPost($uid, $body, $img, $user['tg_id']);
	}
}

function sendPost(string $uid, string $body, string $img, int $id) {
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

	if (empty($img))
		sendMsg([
			'bot' => 'xNCTU',
			'chat_id' => $id,
			'text' => $body,
			'reply_markup' => $keyboard
		]);
	else
		getTelegram('sendPhoto', [
			'bot' => 'xNCTU',
			'chat_id' => $id,
			'photo' => "https://x.nctu.app/img/$img.jpg",
			'caption' => $body,
			'reply_markup' => $keyboard
		]);
}
