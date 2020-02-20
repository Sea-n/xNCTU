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

$text = $TG->data['message']['text'] ?? '';
if (substr($text, 0, 1) != '/') {
	$TG->sendMsg([
		'text' => "您好 {$USER['name']}，\n\n目前僅支援指令操作，請靜待投稿或使用底下按鈕發送測試貼文",
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
	exit;
}

[$cmd, $arg] = explode(' ', substr($text,1), 2);
switch($cmd) {
	case 'start':
	case 'help':
		$msg = "歡迎使用靠北交大 2.0 機器人\n\n";
		$msg .= "目前支援的指令：\n";
		$msg .= "/name 更改網站上的暱稱\n";
		$msg .= "/send 發送測試貼文\n";
		$msg .= "/delete 刪除貼文\n";

		$TG->sendMsg([
			'text' => $msg
		]);
		break;

	case 'send':
		$TG->sendMsg([
			'text' => '點擊下方按鈕以發送測試貼文',
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
		break;

	case 'name':
		$arg = $TG->enHTML(trim($arg));
		if (empty($arg) || strlen($arg) > 32) {
			$TG->sendMsg([
				'text' => "使用方式：`/name [新暱稱]`",
				'parse_mode' => 'Markdown'
			]);
			break;
		}

		$db->updateUserNameTg($TG->FromID, $arg);

		$TG->sendMsg([
			'text' => '修改成功！',
			'reply_markup' => [
				'inline_keyboard' => [
					[
						[
							'text' => '開啟網站',
							'url' => 'https://x.nctu.app/'
						]
					]
				]
			]
		]);
		break;

	case 'delete':
		$TG->sendMsg([
			'text' => "此功能僅限管理員使用\n\n" .
				"如果您有興趣為靠交 2.0 盡一份心力的話，歡迎聯絡開發團隊 🙃"
		]);
		break;

	default:
		$TG->sendMsg([
			'text' => "未知的指令\n\n如需查看使用說明請使用 /help 功能"
		]);
		break;
}
