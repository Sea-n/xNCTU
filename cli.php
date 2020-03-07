<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('utils.php');
require('database.php');
require_once('/usr/share/nginx/sean.taipei/telegram/function.php');
$db = new MyDB();


switch ($argv[1]) {
case 'dump':
	$data = [];

	$tables = ['posts', 'votes', 'users', 'tg_msg'];
	foreach ($tables as $table) {
		$sql = "SELECT * FROM $table ORDER BY created_at DESC";
		$stmt = $db->pdo->prepare($sql);
		$stmt->execute();
		$data[$table] = [];
		while ($item = $stmt->fetch()) {
			if (isset($item['nctu_id']))
				$item['nctu_id'] = idToDep($item['nctu_id']) . ' ' . $item['nctu_id'];

			if (isset($item['voter']))
				$item['voter'] = idToDep($item['voter']) . ' ' . $item['voter'];

			$data[$table][] = $item;
		}
	}

	echo json_encode($data, JSON_PRETTY_PRINT);
	break;

case 'reject':
	$posts = $db->getSubmissions(0);
	foreach ($posts as $post) {
		$uid = $post['uid'];
		$dt = time() - strtotime($post['created_at']);
		$vote = $post['approvals'] - $post['rejects'];

		/* Before 24 hour */
		if ($dt < 1*24*60*60)
			if ($vote > -20)
				continue;

		/* 1day - 2day */
		if ($dt < 2*24*60*60)
			if ($vote > -10)
				continue;

		/* 2day - 3day */
		if ($dt < 3*24*60*60)
			if ($vote > 0)
				continue;

		$db->deleteSubmission($uid, -2, '已駁回');

		/* Remove vote keyboard in Telegram */
		$msgs = $db->getTgMsgsByUid($uid);
		foreach ($msgs as $item) {
			getTelegram('editMessageReplyMarkup', [
				'bot' => 'xNCTU',
				'chat_id' => $item['chat_id'],
				'message_id' => $item['msg_id'],
				'reply_markup' => [
					'inline_keyboard' => [
						[
							[
								'text' => '開啟審核頁面',
								'login_url' => [
									'url' => "https://x.nctu.app/login-tg?r=%2Freview%3Fuid%3D$uid"
								]
							]
						]
					]
				]
			]);
			$db->deleteTgMsg($uid, $item['chat_id']);
		}
	}
	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
