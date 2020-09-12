<?php
session_start(['read_and_close' => true]);
require_once('utils.php');
require_once('database.php');
require_once('send-review.php');
require_once('telegram-bot/class.php');
$db = new MyDB();
$TG = new Telegram();

if (($_SERVER['HTTP_CONTENT_TYPE'] ?? '') == 'application/json')
	$_POST = json_decode(file_get_contents('php://input'), true);
else if (count($_POST) == 0)
	parse_str(file_get_contents('php://input'), $_POST);

header('Content-Type: application/json');

/* HTTP Method: GET */
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$ACTION = $_GET['action'] ?? 'x';

	if ($ACTION == 'posts') {
		$offset = (int) ($_GET['offset'] ?? 0);
		$limit = (int) ($_GET['limit'] ?? 0);
		$likes = (int) ($_GET['likes'] ?? 0);
		if ($limit < 1)
			$limit = 50;
		if ($limit > 5000)
			$limit = 5000;

		$posts = $db->getPostsByLikes($likes, $limit, $offset);
		$result = [];

		/*
		$pinned = $db->getPostById(4290);
		if ($offset == 0)
			array_unshift($posts, $pinned);
		 */

		foreach ($posts as $i => $post) {
			if (!empty($post['author_id'])) {
				$ip_masked = false;
				$author = $db->getUserByStuid($post['author_id']);
				$dep = idToDep($post['author_id']);
				$author_name = $dep . ' ' . $author['name'];
				if (!empty($author['tg_photo']))
					$author_photo = "/img/tg/{$author['tg_id']}-x64.jpg";
				else
					$author_photo = genPic($post['author_id']);
			} else {
				$author_name = $post['author_name'];
				$ip_masked = ip_mask($post['ip_addr']);
				if (strpos($author_name, '境外') !== false)
					$ip_masked = $post['ip_addr'];

				if (!isset($_SESSION['stuid']))
					$ip_masked = ip_mask_anon($ip_masked);

				$author_photo = genPic($ip_masked);
			}

			$result[] = [
				'id' => $post['id'],
				'uid' => $post['uid'],
				'body' => $post['body'],
				'has_img' => $post['has_img'] ? true : false,
				'ip_masked' => $ip_masked,
				'author_name' => $author_name,
				'author_photo' => $author_photo,
				'approvals' => $post['approvals'],
				'rejects' => $post['rejects'],
				'time' => strtotime($post['created_at']),
			];
		}

		echo json_encode($result, JSON_PRETTY_PRINT);
	} else if ($ACTION == 'votes') {
		$uid = $_GET['uid'] ?? '';
		if (strlen($uid) != 4)
			err('uid invalid. 投稿編號無效');

		$result = ['ok' => true];

		$post = $db->getPostByUid($uid);
		$result['approvals'] = (int) $post['approvals'];
		$result['rejects'] = (int) $post['rejects'];

		/* Redirect if posted to all platforms */
		if ($post['status'] == 5)
			$result['id'] = $post['id'];

		if ($post['status'] < 0)
			$result['reload'] = true;

		$result['votes'] = [];
		if (isset($_SESSION['stuid']) || $post['status'] == 10) {
			$votes = $db->getVotesByUid($uid);

			foreach ($votes as $item) {
				$id = $item['stuid'];
				$user = $db->getUserByStuid($id);

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
	} else {
		err('Unknown GET action. 未知的操作');
	}
}

/* HTTP Method: POST */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$ACTION = $_GET['action'] ?? 'x';

	if ($ACTION == 'submission') {
		/* Prepare post content */
		$body = $_POST['body'] ?? '';
		$body = str_replace("\r", "", $body);
		$body = preg_replace("#\n\s+\n#", "\n\n", $body);
		$body = preg_replace("#[&?](fbclid|igshid|utm_[a-z]+)=[a-zA-Z0-9_-]+#", "", $body);
		$body = trim($body);

		$has_img = (isset($_FILES['img']) && $_FILES['img']['size']);

		/* Check POST data */
		$error = checkSubmitData($body, $has_img);
		if (!empty($error))
			err($error);

		/*
		 * Generate UID in base58 space
		 *
		 * If the uid is already in use, it will pick another one.
		 */
		do {
			$uid = rand58(4);
		} while ($db->getPostByUid($uid));

		/* Upload Image */
		if ($has_img) {
			$error = uploadImage($uid);
			if (!empty($error))
				err($error);
		}

		$ip_addr = $_SERVER['REMOTE_ADDR'];

		/* Get Author Name */
		if (isset($_SESSION['stuid']) && !isset($_POST['anon'])) {
			$USER = $db->getUserByStuid($_SESSION['stuid']);
			$author_id = $USER['stuid'];
			$author_name = $USER['name'];
			$author_photo = $USER['tg_photo'] ?? '';
		} else {
			$ip_from = ip_from($ip_addr);
			$author_id = '';
			$author_name = "匿名, $ip_from";
			$author_photo = '';
		}

		/* Insert record */
		$error = $db->insertSubmission($uid, $body, $has_img, $ip_addr, $author_id, $author_name, $author_photo);
		if ($error[0] != '00000')
			err("Database error {$error[0]}, {$error[1]}, {$error[2]}. 資料庫發生錯誤");

		/* Check rate limit */
		if (empty($author_id)) {
			if (strpos($author_name, '境外') !== false) {
				$posts = $db->getPostsByIp($ip_addr, 2);
				if (count($posts) == 2) {
					$last = strtotime($posts[1]['created_at']);
					if (time() - $last < 24*60*60) {
						$db->updatePostStatus($uid, -12);
						err('Please retry afetr 24 hours. 境外 IP 限制 24 小時內僅能發 1 篇文');
					}
				}
			} else if ($author_name != '匿名, 交大') {
				$posts = $db->getPostsByIp($ip_addr, 6);
				if (count($posts) == 6) {
					$last = strtotime($posts[5]['created_at']);
					if (time() - $last < 3*60*60) {
						$db->updatePostStatus($uid, -12);
						err('Please retry afetr 3 hours. 校外 IP 限制 3 小時內僅能發 5 篇文');
					}
				}
			} else {
				$posts = $db->getPostsByIp($ip_addr, 6);
				if (count($posts) == 6) {
					$last = strtotime($posts[5]['created_at']);
					if (time() - $last < 10*60) {
						$db->updatePostStatus($uid, -12);
						err('Please retry afetr 10 minutes. 校內匿名發文限制 10 分鐘內僅能發 5 篇文');
					}
				}
			}

			/* Global rate limit for un-loggined users */
			$posts = $db->getSubmissions(6);
			if (count($posts) == 6) {
				$last = strtotime($posts[2]['created_at']);
				if (time() - $last < 60) {
					$db->updatePostStatus($uid, -12, 'Global rate limit');
					err('Please retry afetr 1 minutes. 系統全域限制 1 分鐘內僅能發 5 篇文');
				}
			}
		}


		/* Success, return post data */
		$ip_masked = ip_mask($ip_addr);
		if (strpos($author_name, '境外') !== false)
			$ip_masked = $ip_addr;
		if (empty($author_photo))
			$author_photo = genPic($ip_masked);

		echo json_encode([
			'ok' => true,
			'uid' => $uid,
			'body' => $body,
			'has_img' => $has_img,
			'ip_masked' => $ip_masked,
			'author_name' => $author_name,
			'author_photo' => $author_photo,
		], JSON_PRETTY_PRINT);
	} else if ($ACTION == 'vote') {
		if (!isset($_SESSION['stuid']))
			err('請先登入');

		$uid = $_POST['uid'] ?? '';
		if (strlen($uid) != 4)
			err('uid invalid. 投稿編號無效');

		$stuid = $_SESSION['stuid'];

		$vote = $_POST['vote'] ?? 0;
		if ($vote != 1 && $vote != -1)
			err('vote invalid. 投票類型無效');

		$reason = $_POST['reason'] ?? '';
		$reason = trim($reason);
		if (mb_strlen($reason) < 1)
			err('附註請勿留空');
		if (mb_strlen($reason) > 100)
			err('附註請輸入 100 個字以內');

		$result = $db->voteSubmissions($uid, $stuid, $vote, $reason);
		echo json_encode($result, JSON_PRETTY_PRINT);

		if ($result['ok'])
			system("php jobs.php vote $uid $stuid > /dev/null &");
	} else {
		err('Unknown POST action. 未知的操作');
	}
}

/* HTTP Method: PATCH */
if ($_SERVER['REQUEST_METHOD'] == 'PATCH') {
	$ACTION = $_GET['action'] ?? 'x';

	if ($ACTION == 'submission') {
		$uid = $_POST['uid'] ?? 'x';
		if (strlen($uid) != 4)
			err("uid ($uid) invalid. 投稿編號無效");

		if ($_POST['status'] == 'confirmed') {
			$post = $db->getPostByUid($uid);
			if (!$post)
				err('找不到該篇投稿');

			if ($post['status'] == 3 || $post['status'] == 5) {
				echo json_encode([
					'ok' => true,
					'msg' => '投稿已送出'
				], JSON_PRETTY_PRINT);
				exit;
			}

			if (time() - strtotime($post['created_at']) > 15*60)
				err('Timeout. 已超出時限，請重新投稿');

			if ($post['status'] != 0)
				err("Submission $uid status {$post['status']} is not eligible to be confirmed. 此投稿狀態不允許確認");

			if ($_SERVER['REMOTE_ADDR'] !== $post['ip_addr'])
				err('無法驗證身份：IP 位址不相符');

			$db->updatePostStatus($uid, 1);
			echo json_encode([
				'ok' => true,
				'msg' => '投稿已送出'
			], JSON_PRETTY_PRINT);

			fastcgi_finish_request();

			sendReview($uid);
		}
	} else {
		err('Unknown PATCH action. 未知的操作');
	}
}

/* HTTP Method: DELETE */
if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
	$ACTION = $_GET['action'] ?? 'x';

	if ($ACTION == 'submission') {
		$uid = $_POST['uid'] ?? '';
		if (strlen($uid) != 4)
			err("uid ($uid) invalid. 投稿編號無效");

		$post = $db->getPostByUid($uid);
		if (!$post)
			err('找不到該篇投稿');

		if ($post['status'] != 0)
			err("目前狀態 {$post['status']} 無法刪除");

		if ($_SERVER['REMOTE_ADDR'] !== $post['ip_addr'])
			err('無法驗證身份：IP 位址不相符');

		$reason = $_POST['reason'] ?? '';
		$reason = trim($reason);
		if (mb_strlen($reason) < 1)
			err('附註請勿留空');
		if (mb_strlen($reason) > 100)
			err('附註請輸入 100 個字以內');

		try {
			$db->deleteSubmission($uid, -3, "自刪 $reason");
			echo json_encode([
				'ok' => true,
				'msg' => '刪除成功！'
			], JSON_PRETTY_PRINT);
		} catch (Exception $e) {
			err('Database Error ' . $e->getCode() . ': ' . $e->getMessage() . "\n" . $e->lastResponse);
		}
	} else {
		err('Unknown DELETE action. 未知的操作');
	}
}


/* Exit with fail and error message. */
function err(string $msg) {
	exit(json_encode([
		'ok' => false,
		'msg' => $msg
	], JSON_PRETTY_PRINT));
}

/* Return error string or empty on success */
function checkSubmitData(string $body, bool $has_img): string {
	/* Check CSRF Token */
	if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']))
		return 'No CSRF Token. 請重新操作';

	if ($_SESSION['csrf_token'] !== $_POST['csrf_token'])
		return 'Invalid CSRF Token. 請重新操作';

	/* Check CAPTCHA */
	$captcha = trim($_POST['captcha'] ?? 'X');
	if ($captcha != '交大竹湖' && $captcha != '交大竹狐') {
		if (mb_strlen($captcha) > 1 && mb_strlen($captcha) < 20)
			error_log("Captcha failed: $captcha.");
		return 'Are you human? 驗證碼錯誤';
	}

	/* Check Body */
	if (mb_strlen($body) < 1)
		return 'Body is empty. 請輸入文章內容';

	if ($has_img && mb_strlen($body) > 1000)
		return 'Body too long (' . mb_strlen($body) . ' chars). 文章過長';

	if (mb_strlen($body) > 4000)
		return 'Body too long (' . mb_strlen($body) . ' chars). 文章過長';

	$lines = explode("\n", $body);
	if (preg_match('#https?://#', $lines[0]))
		return 'First line cannot be URL. 第一行不能為網址';

	return '';
}

/* Return error message or empty */
function uploadImage(string $uid): string {
	$src = $_FILES['img']['tmp_name'];
	if (!file_exists($src) || !is_uploaded_file($src))
		return 'Uploaded file not found. 上傳發生錯誤';

	if ($_FILES['img']['size'] > 50*1000*1000)
		return 'Image too large. 圖片過大';

	/* Check file type */
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	if (!($ext = array_search($finfo->file($src), [
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
		], true)))
		return 'Extension not recognized. 圖片副檔名錯誤';

	$dst = __DIR__ . "/img/$uid";
	if (!move_uploaded_file($src, $dst))
		return 'Failed to move uploaded file. 上傳發生錯誤';

	/* Check image size */
	$size = getimagesize($dst);
	$width = $size[0];
	$height = $size[1];

	if ($width * $height < 160*160)
		$err = 'Image must be at least 160x160.';

	if ($width/8 > $height)
		$err = 'Image must be at least 8:1.';

	if ($width < $height/4)
		$err = 'Image must be at least 1:4.';

	if (isset($err)) {
		unlink($dst);
		return $err;
	}

	/* Fix orientation */
	$orien = shell_exec("exiftool -Orientation -S -n $dst |cut -c14- |tr -d '\\n'");
	switch ($orien) {
	case '1':  # Horizontal (normal)
		$transpose = "";
		break;
	case '2':  # Mirror horizontal
		$transpose = "-vf transpose=0,transpose=1";
		break;
	case '3':  # Rotate 180
		$transpose = "-vf transpose=1,transpose=1";
		break;
	case '4':  # Mirror vertical
		$transpose = "-vf transpose=3,transpose=1";
		break;
	case '5':  # Mirror horizontal and rotate 270 CW
		$transpose = "-vf transpose=0";
		break;
	case '6':  # Rotate 90 CW
		$transpose = "-vf transpose=1";
		break;
	case '7':  # Mirror horizontal and rotate 90 CW
		$transpose = "-vf transpose=3";
		break;
	case '8':  # Rotate 270 CW
		$transpose = "-vf transpose=2";
		break;
	default:
		$transpose = "";
		break;
	}

	/* Convert all file type to jpg */
	shell_exec("ffmpeg -i $dst -q:v 1 $transpose $dst.jpg 2>&1");
	unlink($dst);

	while (filesize("$dst.jpg") > 1*1000*1000) {
		rename("$dst.jpg", "$dst.ori.jpg");
		shell_exec("ffmpeg -i $dst.ori.jpg -q:v 1 -vf scale='(iw/2):(ih/2)' $dst.jpg 2>&1");
		unlink("$dst.ori.jpg");
	}

	return '';
}
