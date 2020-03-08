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
	$msg .= "2. 點擊下方按鈕連結 Telegram 帳號\n";
	$msg .= "3. 系統綁定成功後，將會發送 Telegram 訊息通知您";
	$TG->sendMsg([
		'text' => $msg,
		'reply_markup' => [
			'inline_keyboard' => [
				[
					[
						'text' => '綁定靠交 2.0 網站',
						'login_url' => [
							'url' => "https://x.nctu.app/login-tg?r=/"
						]
					]
				]
			]
		]
	]);
	exit;
}

$text = $TG->data['message']['text'] ?? '';

if (substr($text, 0, 1) == '/') {
	$text = substr($text, 1);
	[$cmd, $arg] = explode(' ', $text, 2);

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
			$body = "學生計算機年會（Students’ Information Technology Conference）自 2013 年發起，以學生為本、由學生自發舉辦，長期投身學生資訊教育與推廣開源精神，希望引領更多學子踏入資訊的殿堂，更冀望所有對資訊有興趣的學生，能夠在年會裏齊聚一堂，彼此激盪、傳承、啟發，達到「學以致用、教學相長」的實際展現。";

			$result = $TG->getTelegram('sendPhoto', [
				'chat_id' => $TG->ChatID,
				'photo' => "https://x.nctu.app/img/TEST.jpg",
				'caption' => $body,
				'reply_markup' => [
					'inline_keyboard' => [
						[
							[
								'text' => '✅ 通過',
								'callback_data' => "approve_TEST"
							],
							[
								'text' => '❌ 駁回',
								'callback_data' => "reject_TEST"
							]
						],
						[
							[
								'text' => '開啟審核頁面',
								'login_url' => [
									'url' => "https://x.nctu.app/login-tg?r=%2Freview%2FTEST"
								]
							]
						]
					]
				]
			]);

			$db->setTgMsg('TEST', $TG->ChatID, $result['result']['message_id']);
			break;

		case 'name':
			$arg = $TG->enHTML(trim($arg));
			if (empty($arg) || mb_strlen($arg) > 10) {
				$TG->sendMsg([
					'text' => "使用方式：`/name 新暱稱`\n\n字數上限：10 個字",
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
								'login_url' => [
									'url' => "https://x.nctu.app/login-tg"
								]
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

	exit;
}

if (preg_match('#^\[(approve|reject)/([a-zA-Z0-9]+)\]#', $TG->data['message']['reply_to_message']['text'] ?? '', $matches)) {
	$vote = $matches[1] == 'approve' ? 1 : -1;
	$uid = $matches[2];

	$type = $vote == 1 ? '✅ 通過' : '❌ 駁回';

	if (empty($text) || mb_strlen($text) > 100) {
		$TG->sendMsg([
			'text' => '請輸入 1 - 100 字投票附註'
		]);

		exit;
	}

	try {
		$result = $db->voteSubmissions($uid, $USER['nctu_id'], $vote, $text);
		if (!$result['ok'])
			$msg = $result['msg'];
		else {
			$msg = "您成功為 #投稿$uid 投下了 $type\n\n";
			$msg .= "目前通過 {$result['approvals']} 票、駁回 {$result['rejects']} 票";
		}
	} catch (Exception $e) {
		$msg = 'Error ' . $e->getCode() . ': ' .$e->getMessage() . "\n";
	}

	$TG->sendMsg([
		'text' => $msg,
	]);

	$msg_id = $db->getTgMsg($uid, $TG->ChatID);
	if ($msg_id) {
		$TG->getTelegram('editMessageReplyMarkup', [
			'chat_id' => $TG->ChatID,
			'message_id' => $msg_id,
			'reply_markup' => [
				'inline_keyboard' => [
					[
						[
							'text' => '開啟審核頁面',
							'login_url' => [
								'url' => "https://x.nctu.app/login-tg?r=%2Freview%2F$uid"
							]
						]
					]
				]
			]
		]);
		$db->deleteTgMsg($uid, $TG->ChatID);
	}

	$TG->getTelegram('deleteMessage', [
		'chat_id' => $TG->ChatID,
		'message_id' => $TG->data['message']['reply_to_message']['message_id'],
	]);

	exit;
}
