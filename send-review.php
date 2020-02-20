<?php
require_once('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');

if (isset($argv)) {
	$uid = "TEST";
	$body = "學生計算機年會（Students’ Information Technology Conference）自 2013 年發起，以學生為本、由學生自發舉辦，長期投身學生資訊教育與推廣開源精神，希望引領更多學子踏入資訊的殿堂，更冀望所有對資訊有興趣的學生，能夠在年會裏齊聚一堂，彼此激盪、傳承、啟發，達到「學以致用、教學相長」的實際展現。";
	$img = "4ZSf.png";

	sendReview($uid, $body, $img);
}


function sendReview(string $uid, string $body, string $img) {
	$db = new MyDB();
	$USERS = $db->getTgUsers();

	if ($uid == 'TEST')
		$USERS = [['tg_id' => 109780439]];

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
					'url' => "https://x.nctu.app/review?uid=$uid"
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
			'photo' => "https://x.nctu.app/img/$img",
			'caption' => $body,
			'reply_markup' => $keyboard
		]);
}
