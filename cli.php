<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('utils.php');
require('database.php');
require_once('telegram-bot/class.php');
$db = new MyDB();
$TG = new Telegram();


switch ($argv[1]) {
case 'dump':
	$data = [];
	$posts = [];

	$tables = ['posts', 'votes', 'users', 'tg_msg'];
	foreach ($tables as $table) {
		$sql = "SELECT * FROM $table ORDER BY created_at DESC";
		$stmt = $db->pdo->prepare($sql);
		$stmt->execute();
		$data[$table] = [];
		while ($item = $stmt->fetch()) {
			if (isset($item['stuid']))
				$item['stuid'] = idToDep($item['stuid']) . ' ' . $item['stuid'];

			if ($table == 'posts')
				$posts[ $item['uid'] ] = $item;

			if ($table == 'votes') {
				$item['uid'] .= ' ' . mb_substr($posts[ $item['uid'] ]['body'], 0, 20) . '..';

				$item['voter'] = idToDep($item['voter']) . ' ' . $item['voter'];

				$item['vote'] = ($item['vote'] == '1' ? '✅ 通過' : '❌ 駁回');
			}

			$data[$table][] = $item;
		}
	}

	echo json_encode($data, JSON_PRETTY_PRINT);
	break;

case 'reject':
	$posts = $db->getSubmissions(0);
	foreach ($posts as $post) {
		/* Prevent reject demo post */
		if ($post['status'] != 3)
			continue;

		$uid = $post['uid'];
		$dt = time() - strtotime($post['created_at']);

		if (strpos($post['author_name'], '境外') !== false) {
			if ($post['rejects'] < 2)
				continue;
		} else {
			/* Before 2 hour */
			if ($dt < 2*60*60)
				if ($post['rejects'] < 5)
					continue;

			/* 2 hour - 12 hour*/
			if ($dt < 12*60*60)
				if ($post['rejects'] < 3)
					continue;
		}

		$db->deleteSubmission($uid, -2, '已駁回');

		/* Remove vote keyboard in Telegram */
		$msgs = $db->getTgMsgsByUid($uid);
		foreach ($msgs as $item) {
			$TG->deleteMsg($item['chat_id'], $item['msg_id']);
			$db->deleteTgMsg($uid, $item['chat_id']);
		}
	}

	$sql = "SELECT * FROM posts WHERE status = 0";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();
	while ($post = $stmt->fetch()) {
		$dt = time() - strtotime($post['created_at']);

		/* Not within 3 - 4 min */
		if ($dt < 3*60 || $dt > 4*60)
			continue;

		$uid = $post['uid'];

		$msg = "<未確認投稿>\n\n";
		$msg .= $post['body'];

		$keyboard = [
			'inline_keyboard' => [
				[
					[
						'text' => '✅ 確認投稿',
						'callback_data' => "confirm_$uid"
					],
					[
						'text' => '❌ 刪除投稿',
						'callback_data' => "delete_$uid"
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

		if (!$post['has_img'])
			$TG->sendMsg([
				'chat_id' => -1001489855993,
				'text' => $msg,
				'reply_markup' => $keyboard,
			]);
		else
			$TG->sendPhoto([
				'chat_id' => -1001489855993,
				'photo' => 'https://' . DOMAIN . "/img/$uid.jpg",
				'caption' => $msg,
				'reply_markup' => $keyboard,
			]);
	}

	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
