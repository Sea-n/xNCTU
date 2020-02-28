<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$action = $_GET['action'] ?? 'x';
	switch ($action) {
		case 'votes':
			$uid = $_GET['uid'] ?? '';
			if (strlen($uid) != 4)
				exit(json_encode([
					'ok' => false,
					'msg' => 'uid invalid. 投稿編號無效'
				], JSON_PRETTY_PRINT));

			$result = ['ok' => true];

			$post = $db->getSubmissionByUid($uid);
			$result['approvals'] = (int) $post['approvals'];
			$result['rejects'] = (int) $post['rejects'];

			$result['votes'] = [];
			if (true || isset($_SESSION['nctu_id'])) {
				$votes = $db->getVotesByUid($uid);

				foreach ($votes as $item) {
					$id = $item['voter'];
					$user = $db->getUserByNctu($id);

					$result['votes'][] = [
						'vote' => (int) $item['vote'],
						'id' => $id,
						'dep' => idToDep($id),
						'name' => $user['name'],
						'reason' => $item['reason'],
					];
				}
			}

			echo json_encode($result, JSON_PRETTY_PRINT);

			break;

		default:
			exit(json_encode([
				'ok' => false,
				'msg' => 'Unknown action.'
			], JSON_PRETTY_PRINT));
			break;
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (($_SERVER['HTTP_CONTENT_TYPE'] ?? '') == 'application/json')
		$_POST = json_decode(file_get_contents('php://input'), true);

	$action = $_POST['action'] ?? 'x';
	switch ($action) {
		case 'vote':
			if (!isset($_SESSION['nctu_id']))
				exit(json_encode([
					'ok' => false,
					'msg' => '請先登入'
				], JSON_PRETTY_PRINT));

			$uid = $_POST['uid'] ?? '';
			if (strlen($uid) != 4)
				exit(json_encode([
					'ok' => false,
					'msg' => 'uid invalid. 投稿編號無效'
				], JSON_PRETTY_PRINT));

			$voter = $_SESSION['nctu_id'];

			$vote = $_POST['vote'] ?? 0;
			if ($vote != 1 && $vote != -1)
				exit(json_encode([
					'ok' => false,
					'msg' => 'vote invalid. 投票類型無效'
				], JSON_PRETTY_PRINT));

			$reason = $_POST['reason'] ?? '';
			$reason = trim($reason);
			if (mb_strlen($reason) < 5)
				exit(json_encode([
					'ok' => false,
					'msg' => '附註請輸入 5 個字以上'
				], JSON_PRETTY_PRINT));
			if (mb_strlen($reason) > 100)
				exit(json_encode([
					'ok' => false,
					'msg' => '附註請輸入 100 個字以內'
				], JSON_PRETTY_PRINT));

			$result = $db->voteSubmissions($uid, $voter, $vote, $reason);
			echo json_encode($result, JSON_PRETTY_PRINT);
			break;

		case 'delete':
			$uid = $_POST['uid'] ?? '';
			if (strlen($uid) != 4)
				exit(json_encode([
					'ok' => false,
					'msg' => 'uid invalid. 投稿編號無效'
				], JSON_PRETTY_PRINT));

			$post = $db->getSubmissionByUid($uid);
			if (!$post)
				exit(json_encode([
					'ok' => false,
					'msg' => '找不到該篇投稿'
				], JSON_PRETTY_PRINT));

			$ts = strtotime($post['created_at']);
			$dt = time() - $ts;
			if ($dt > 5*60)
				exit(json_encode([
					'ok' => false,
					'msg' => '已超出刪除期限，請來信聯絡開發團隊'
				], JSON_PRETTY_PRINT));

			if ($_SERVER['REMOTE_ADDR'] !== $post['ip_addr'])
				exit(json_encode([
					'ok' => false,
					'msg' => '無法驗證身份：IP 位址不相符'
				], JSON_PRETTY_PRINT));

			$reason = $_POST['reason'] ?? '';
			$reason = trim($reason);
			if (mb_strlen($reason) < 5)
				exit(json_encode([
					'ok' => false,
					'msg' => '附註請輸入 5 個字以上'
				], JSON_PRETTY_PRINT));
			if (mb_strlen($reason) > 100)
				exit(json_encode([
					'ok' => false,
					'msg' => '附註請輸入 100 個字以內'
				], JSON_PRETTY_PRINT));

			try {
				$db->deleteSubmission($uid, "自刪 $reason");
				echo json_encode([
					'ok' => true,
					'msg' => '刪除成功！'
				], JSON_PRETTY_PRINT);
			} catch (Exception $e) {
				echo json_encode([
					'ok' => 'false',
					'msg' => ' Database Error ' . $e->getCode() . ': ' . $e->getMessage() . "\n" . $e->lastResponse
				], JSON_PRETTY_PRINT);
			}

			break;

		default:
			exit(json_encode([
				'ok' => false,
				'msg' => 'Unknown action.'
			], JSON_PRETTY_PRINT));
			break;
	}
}
