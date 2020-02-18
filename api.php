<?php
session_start();
require('utils.php');
require('database.php');
$db = new MyDB();

if ($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json')
	$_POST = json_decode(file_get_contents('php://input'), true);

$action = $_POST['action'] ?? 'x';
switch ($action) {
	case 'vote':
		if (!isset($_SESSION['nctu_id']))
			exit(json_encode([
				'ok' => false,
				'msg' => 'Please login.'
			]));
		
		$uid = $_POST['uid'] ?? '';

		$voter = $_SESSION['nctu_id'];
		
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
