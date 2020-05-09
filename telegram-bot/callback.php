<?php
require_once(__DIR__ . '/../database.php');
require_once(__DIR__ . '/../send-review.php');
$db = new MyDB();

if (!isset($TG->data['callback_query']['data'])) {
	$TG->sendMsg([
		'text' => 'Error: No callback data.'
	]);
	exit;
}

$USER = $db->getUserByTg($TG->FromID);
if (!$USER) {
	$TG->getTelegram('answerCallbackQuery', [
		'callback_query_id' => $TG->data['callback_query']['id'],
		'show_alert' => true,
		'text' => "您尚未綁定 NCTU 帳號，請至$SITENAME 網站登入"
	]);
	exit;
}

$arg = $TG->data['callback_query']['data'];
$args = explode('_', $arg);
switch ($args[0]) {
	case 'approve':
	case 'reject':
		$type = $args[0];
		$uid = $args[1];

		$check = $db->canVote($uid, $USER['stuid']);
		if (!$check['ok']) {
			$TG->getTelegram('answerCallbackQuery', [
				'callback_query_id' => $TG->data['callback_query']['id'],
				'text' => $check['msg'],
				'show_alert' => true
			]);

			$TG->getTelegram('editMessageReplyMarkup', [
				'chat_id' => $TG->data['callback_query']['message']['chat']['id'],
				'message_id' => $TG->data['callback_query']['message']['message_id'],
				'reply_markup' => [
					'inline_keyboard' => [
						[
							[
								'text' => '開啟審核頁面',
								'login_url' => [
									'url' => "https://$DOMAIN/login-tg?r=%2Freview%2F$uid"
								]
							]
						]
					]
				]
			]);

			break;
		}

		$TG->sendMsg([
			'reply_to_message_id' => $TG->MsgID,
			'text' => "[$type/$uid] 請輸入 1 - 100 字理由\n\n" .
				"將會顯示於貼文頁面中，所有已登入的交大人都能看到您的具名投票",
			'reply_markup' => [
				'force_reply' => true,
			]
		]);

		$TG->getTelegram('answerCallbackQuery', [
			'callback_query_id' => $TG->data['callback_query']['id']
		]);

		break;

	case 'confirm':
	case 'delete':
		if (!in_array($TG->data['callback_query']['from']['id'], [
			109780439,  # Sean
			351382660,  # Eugene
			859018590,  # s960194d
		])) {
			$TG->getTelegram('answerCallbackQuery', [
				'callback_query_id' => $TG->data['callback_query']['id'],
				'text' => '401 Unauthorized.',
				'show_alert' => true,
			]);
			exit;
		}

		$TG->editMarkup([
			'chat_id' => $TG->data['callback_query']['message']['chat']['id'],
			'message_id' => $TG->data['callback_query']['message']['message_id'],
			'reply_markup' => [
				'inline_keyboard' => []
			],
		]);

		$uid = $args[1];
		$post = $db->getPostByUid($uid);

		if ($post['status'] != 0) {
			$TG->getTelegram('answerCallbackQuery', [
				'callback_query_id' => $TG->data['callback_query']['id'],
				'text' => "Status {$post['status']} invalid.",
				'show_alert' => true,
			]);
			exit;
		}

		if ($args[0] == 'confirm')
			$db->updateSubmissionStatus($uid, 1);
		else
			$db->deleteSubmission($uid, -13, '逾期未確認');

		$TG->getTelegram('answerCallbackQuery', [
			'callback_query_id' => $TG->data['callback_query']['id'],
			'text' => 'Done.',
			'show_alert' => true,
		]);

		if ($args[0] == 'confirm')
			sendReview($uid);

		break;

	default:
		$TG->sendMsg([
			'text' => "Unknown callback data: {$arg}"
		]);
		break;
}
