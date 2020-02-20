<?php
require(__DIR__.'/../database.php');
$db = new MyDB();

if ($TG->ChatID < 0) {
	$TG->sendMsg([
		'text' => '目前尚未支援群組功能',
		'reply_markup' => [
			'inline_keyboard' => [
				[
					[
						'text' => '📢 靠交 2.0 頻道',
						'url' => 'https://t.me/xNCTU'
					]
				]
			]
		]
	]);
	exit;
}

$USER = $db->getUserByTg($TG->FromID);
if (!$USER) {
	$msg = "您尚未綁定 NCTU 帳號，請至靠北交大 2.0 網站登入\n\n";
	$msg .= "操作步驟：\n";
	$msg .= "1. 登入 NCTU OAuth 帳號\n";
	$msg .= "2. 於靠交 2.0 首頁登入 Telegram 帳號\n";
	$msg .= "3. 系統綁定成功後，將會發送 Telegram 訊息通知您";
	$TG->sendMsg([
		'text' => $msg,
		'reply_markup' => [
			'inline_keyboard' => [
				[
					[
						'text' => '登入靠交 2.0 網站',
						'url' => 'https://x.nctu.app/login-nctu'
					]
				]
			]
		]
	]);
	exit;
}

$TG->sendMsg([
	'text' => "您好 {$USER['name']}，\n\n目前尚未支援指令操作，請靜待投稿或使用底下按鈕發送測試貼文",
	'reply_markup' => [
		'inline_keyboard' => [
			[
				[
					'text' => '發送測試貼文',
					'callback_data' => 'test_send'
				]
			]
		]
	]
]);
