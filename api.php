<?php
require('utils.php');
require('database.php');
$db = new MyDB();

if ($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json')
	$_POST = json_decode(file_get_contents('php://input'), true);

$action = $_POST['action'] ?? 'x';
switch ($action) {
	case 'vote':
		$uid = $_POST['uid'] ?? '';

		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		$voter = $ip;
		
		$vote = $_POST['vote'] ?? 0;
		if ($vote != 1 && $vote != -1)
			exit(json_encode([
				'ok' => false,
				'msg' => 'vote invalid.'
			]));

		$reason = $_POST['reason'] ?? '';

		$result = $db->voteSubmissions($uid, $voter, $vote, $reason);
		echo json_encode($result);
		break;
}
