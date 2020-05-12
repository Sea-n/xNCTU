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
case 'tg_photo':
	$tg_id = $argv[2];
	$USER = $db->getUserByTg($tg_id);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $USER['tg_photo']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$file = curl_exec($curl);
	curl_close($curl);

	file_put_contents("img/tg/{$tg_id}-x320.jpg", $file);
	system("ffmpeg -y -i img/tg/{$tg_id}-x320.jpg -vf scale=64x64 img/tg/{$tg_id}-x64.jpg");

	break;

case 'vote':
	$uid = $argv[2];
	$voter = $argv[3];
	$post = $db->getPostByUid($uid);
	$USER = $db->getUserByStuid($voter);
	$VOTE = $db->getVote($uid, $voter);

	/* Remove vote keyboard in Telegram */
	$chat_id = $USER['tg_id'] ?? 0;
	$msg_id = $db->getTgMsg($uid, $chat_id);

	if ($msg_id) {
		$TG->editMarkup([
			'chat_id' => $chat_id,
			'message_id' => $msg_id,
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
		$db->deleteTgMsg($uid, $chat_id);
	}

	/* Send vote log to group */
	$body = $post['body'];
	$body = preg_replace('/\s+/', '', $body);
	$body = mb_substr($body, 0, 10) . '..';
	$body = enHTML($body);

	$dep = idToDep($USER['stuid']);

	$name = $USER['name'];
	if (is_numeric($name))
		$name = "N$name";
	$name = preg_replace('/[ -\/:-@[-`{-~]/iu', '_', $name);

	$type = ($VOTE['vote'] == 1 ? '✅' : '❌');
	$reason = $VOTE['reason'];

	$msg = "#投稿$uid $body\n" .
		enHTML("$dep #$name\n\n") .
		enHTML("$type $reason");

	$TG->sendMsg([
		'chat_id' => LOG_GROUP,
		'text' => $msg,
		'parse_mode' => 'HTML',
		'disable_web_page_preview' => true,
	]);
	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
