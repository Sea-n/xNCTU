<?php
require_once(__DIR__ . '/utils.php');
require_once(__DIR__ . '/config.php');
class MyDB {
	public $pdo;

	public function __construct() {
		$this->pdo = new PDO('mysql:host=localhost;dbname=xnctu', 'xnctu', MYSQL_PASSWORD);
		$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/* Return error info or ['00000', null, null] on success */
	public function insertSubmission(string $uid, string $body, bool $has_img, string $ip_addr, string $author_id, string $author_name, string $author_photo): array {
		if (strlen($uid) != 4)
			return ['SEAN', 0, 'UID invalid. (should be 4 chars)'];

		if (empty($body))
			return ['SEAN', 0, 'Body cannot be empty.'];

		if (mb_strlen($body) > 4000)
			return ['SEAN', 0, 'Body too long. (max 4000 chars)'];

		if ($has_img && mb_strlen($body) > 1000)
			return ['SEAN', 0, 'Body too long. (max 1000 chars with image)'];

		$sql = "INSERT INTO posts(uid, body, has_img, ip_addr, author_id, author_name, author_photo) VALUES (:uid, :body, :has_img, :ip_addr, :author_id, :author_name, :author_photo)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':body' => $body,
			':has_img' => $has_img ? 1 : 0,
			':ip_addr' => $ip_addr,
			':author_id' => $author_id,
			':author_name' => $author_name,
			':author_photo' => $author_photo,
		]);

		return $stmt->errorInfo();
	}

	public function updateSubmissionStatus(string $uid, int $status) {
		if ($status == 1)
			$sql = "UPDATE posts SET status = :status, created_at = CURRENT_TIMESTAMP WHERE uid = :uid";
		else
			$sql = "UPDATE posts SET status = :status WHERE uid = :uid";

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':status' => $status,
		]);
	}

	public function getPostByUid(string $uid) {
		$sql = "SELECT * FROM posts WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		return $stmt->fetch();
	}

	public function getSubmissions(int $limit, bool $desc = true) {
		if ($limit == 0) $limit = 9487;

		$ORDER = $desc ? 'DESC' : 'ASC';
		$sql = "SELECT * FROM posts WHERE status = 3 OR status = 10 ORDER BY created_at $ORDER";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch()) {
			if (!$limit--)
				break;

			$results[] = $item;
		}

		return $results;
	}

	public function getDeletedSubmissions(int $limit, bool $desc = true) {
		if ($limit == 0) $limit = 9487;

		$ORDER = $desc ? 'DESC' : 'ASC';
		$sql = "SELECT * FROM posts WHERE status < 0 AND status != -3 AND status > -10 ORDER BY deleted_at $ORDER";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch()) {
			if (!$limit--)
				break;

			$results[] = $item;
		}

		return $results;
	}

	public function deleteSubmission(string $uid, int $status = -1, string $reason) {
		$sql = "UPDATE posts SET status = :status, delete_note = :reason, deleted_at = IF(deleted_at IS NULL, CURRENT_TIMESTAMP, deleted_at) WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':status' => $status,
			':reason' => $reason,
		]);
	}

	public function getPostById(string $id) {
		$sql = "SELECT * FROM posts WHERE id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':id' => $id]);
		return $stmt->fetch();
	}

	/* Get posts newest first */
	public function getPosts(int $limit, int $offset = 0) {
		if ($limit == 0) $limit = 9487;

		$sql = "SELECT * FROM posts WHERE status BETWEEN 4 AND 5 ORDER BY posted_at DESC LIMIT :limit OFFSET :offset";
		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	public function getPostsByIp(string $ip, int $limit, int $offset = 0) {
		$sql = "SELECT * FROM posts WHERE ip_addr = :ip AND status != -3 AND status > -10 AND author_id = '' ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
		$stmt = $this->pdo->prepare($sql);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
		$stmt->bindValue(':ip', $ip);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
		return $stmt->fetch();
	}

	public function getPostsByStuid(string $stuid) {
		$sql = "SELECT * FROM posts WHERE author_id = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
		return $stmt->fetch();
	}

	/* Check can user vote for certain submission or not */
	public function canVote(string $uid, string $stuid): array {
		$post = $this->getPostByUid($uid);
		if (!$post)
			return ['ok' => false, 'msg' => 'uid not found. 找不到該投稿'];

		if ($post['status'] == 0)
			return ['ok' => false, 'msg' => 'Submission not confirmed. 請先確認投稿'];

		if ($post['status'] > 3 && $post['status'] != 10)
			return ['ok' => false, 'msg' => 'Already posted. 太晚囉，貼文已發出'];

		if ($post['status'] < 0)
			return [
				'ok' => false,
				'msg' => '投稿已刪除，理由：' . $post['delete_note']
			];

		$sql = "SELECT created_at FROM votes WHERE uid = :uid AND stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':stuid' => $stuid
		]);
		if ($stmt->fetch())
			return ['ok' => false, 'msg' => 'Already voted. 您已投過票'];

		return ['ok' => true];
	}

	public function voteSubmissions(string $uid, string $stuid, int $vote, string $reason = '') {
		if ($vote == 1)
			$type = 'approvals';
		else if ($vote == -1)
			$type = 'rejects';
		else
			return ['ok' => false, 'msg' => 'Unknown vote. 未知的投票類型'];

		if (mb_strlen($reason) > 100)
			return ['ok' => false, 'msg' => 'Reason too long. 附註文字過長'];

		$check = $this->canVote($uid, $stuid);
		if (!$check['ok'])
			return $check;

		$sql = "INSERT INTO votes(uid, vote, reason, stuid) VALUES (:uid, :vote, :reason, :stuid)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':vote' => $vote,
			':reason' => $reason,
			':stuid' => $stuid
		]);

		/* Caution: use string combine in SQL query */
		$sql = "UPDATE posts SET $type = $type + 1 WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);

		$sql = "UPDATE users SET $type = $type + 1 WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);

		/* Calculate vote streak, the users table record is independent from votes table */
		$sql = "SELECT * FROM users WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);
		$USER = $stmt->fetch();

		$lv = strtotime($USER['last_vote']);
		if (date('Ymd', $lv) == date('Ymd'))  // Already voted today
			$sql = "UPDATE users SET last_vote = CURRENT_TIMESTAMP"
				. " WHERE stuid = :stuid";
		else if (date('Ymd', $lv) == date('Ymd', time() - 24*60*60)) {  // Streak from yesterday
			if ($USER['current_vote_streak'] == $USER['highest_vote_streak'])
				$sql = "UPDATE users SET last_vote = CURRENT_TIMESTAMP, "
					. "current_vote_streak = current_vote_streak + 1, "
					. "highest_vote_streak = current_vote_streak "
					. "WHERE stuid = :stuid";
			else  // Streaking but not highest
				$sql = "UPDATE users SET last_vote = CURRENT_TIMESTAMP, "
					. "current_vote_streak = current_vote_streak + 1 "
					. "WHERE stuid = :stuid";
		} else  // New day
			$sql = "UPDATE users SET last_vote = CURRENT_TIMESTAMP, "
				. "current_vote_streak = 1, "
				. "highest_vote_streak = GREATEST(highest_vote_streak, 1) "
				. "WHERE stuid = :stuid";

		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);

		/* Return votes for submission */
		$sql = "SELECT approvals, rejects FROM posts WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$result = $stmt->fetch();

		$result['ok'] = true;
		return $result;
	}

	public function getVotes() {
		$sql = "SELECT * FROM votes";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	public function getVotesByUid(string $uid) {
		$sql = "SELECT * FROM votes WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	public function getVotesByStuid(string $stuid) {
		$sql = "SELECT * FROM votes WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	public function getVote(string $uid, string $stuid) {
		$sql = "SELECT * FROM votes WHERE uid = :uid AND stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':stuid' => $stuid,
		]);

		return $stmt->fetch();
	}

	/*
	 * Given submission uid
	 *
	 * It will change status from 3 to 4 and give it post id
	 *
	 * Return new post array
	 */
	public function setPostId(string $uid): array {
		$post = $this->getPostByUid($uid);
		assert($post['status'] == 3);

		$post['id'] = $this->getLastPostId() + 1;

		$sql = "UPDATE posts SET id = :id, status = 4, posted_at = CURRENT_TIMESTAMP WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $post['uid'],
			':id' => $post['id'],
		]);

		return $post;
	}

	public function getLastPostId(): int {
		$sql = "SELECT id FROM posts ORDER BY id DESC LIMIT 1";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();
		$item = $stmt->fetch();
		return $item['id'] ?? 0;
	}

	public function updatePostBody(string $uid, string $body) {
		$sql = "UPDATE posts SET body = :body WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':body' => $body,
			':uid'  => $uid,
		]);
	}

	/* Update SNS post ID */
	public function updatePostSns(int $id, string $type, int $pid) {
		if (!in_array($type, ['telegram', 'plurk', 'twitter', 'facebook']))
			return false;

		/* Caution: use string combine in SQL query */
		$sql = "UPDATE posts SET {$type}_id = :pid WHERE id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':id' => $id,
			':pid' => $pid,
		]);

		$post = $this->getPostById($id);
		if ($post['telegram_id'] > 0
		 && $post['plurk_id']    > 0
		 && $post['twitter_id']  > 0
		 && $post['facebook_id'] > 0)
			$this->updateSubmissionStatus($post['uid'], 5);
	}

	public function insertUserStuid(string $stuid, string $mail) {
		$sql = "SELECT stuid FROM users WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);

		if ($stmt->fetch())
			return false;

		$sql = "INSERT INTO users(name, stuid, mail) VALUES (:name, :stuid, :mail)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':name' => $stuid,
			':stuid' => $stuid,
			':mail' => $mail
		]);
	}

	public function insertUserTg(string $stuid, array $tg) {
		$sql = "SELECT stuid FROM users WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);

		if (!$stmt->fetch())
			return false;

		$name = $tg['first_name'];
		if (isset($tg['last_name']))
			$name .= ' ' . $tg['last_name'];

		$sql = "UPDATE users SET tg_id = :tg_id, tg_name = :name, tg_username = :username, tg_photo = :photo WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':stuid' => $stuid,
			':tg_id' => $tg['id'],
			':name' => $name,
			':username' => $tg['username'] ?? '',
			':photo' => $tg['photo_url'] ?? '',
		]);
	}

	public function insertUserStuTg(string $stuid, string $tg_id) {
		$sql = "SELECT stuid FROM users WHERE stuid = :stuid OR tg_id = :tg_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':stuid' => $stuid,
			':tg_id' => $tg_id,
		]);

		if ($stmt->fetch())
			return false;

		$sql = "INSERT INTO users(name, stuid, tg_id) VALUES (:stuid, :stuid, :tg_id)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':stuid' => $stuid,
			':tg_id' => $tg_id,
		]);
	}

	public function updateUserTgProfile(array $tg) {
		$name = $tg['first_name'];
		if (isset($tg['last_name']))
			$name .= ' ' . $tg['last_name'];

		$sql = "UPDATE users SET tg_name = :name, tg_username = :username, tg_photo = :photo WHERE tg_id = :tg_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':tg_id' => $tg['id'],
			':name' => $name,
			':username' => $tg['username'] ?? '',
			':photo' => $tg['photo_url'] ?? '',
		]);
	}

	public function updateUserNameTg(int $tg_id, string $name) {
		$sql = "UPDATE users SET name = :name WHERE tg_id = :tg_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':tg_id' => $tg_id,
			':name' => $name,
		]);
	}

	public function updateUserLogin(string $stuid) {
		$sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':stuid' => $stuid,
		]);
	}

	public function getUserByStuid(string $stuid = ''): ?array {
		if (empty($stuid))
			return NULL;
		$sql = "SELECT * FROM users WHERE stuid = :stuid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':stuid' => $stuid]);
		$result = $stmt->fetch();

		if ($result === false)
			return NULL;
		return $result;
	}

	public function getUserByTg(int $id) {
		$sql = "SELECT * FROM users WHERE tg_id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':id' => $id]);
		return $stmt->fetch();
	}

	public function getTgUsers() {
		$sql = "SELECT * FROM users WHERE tg_id > 0 ORDER BY approvals + rejects DESC, tg_id ASC";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	/* For opt-out messages */
	public function removeUserTg(int $tg_id) {
		$sql = "UPDATE users SET tg_name = NULL WHERE tg_id = :tg_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':tg_id' => $tg_id]);
	}

	/* Unlink account from Telegram */
	public function unlinkUserTg(int $tg_id) {
		$sql = "UPDATE users SET tg_id = NULL, tg_name = NULL, tg_username = NULL, tg_photo = NULL WHERE tg_id = :tg_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':tg_id' => $tg_id]);
	}

	public function setTgMsg(string $uid, int $chat, int $msg) {
		if ($msg <= 0) {
			$this->deleteTgMsg($uid, $chat);
			return;
		}

		if ($this->getTgMsg($uid, $chat))
			$this->updateTgMsg($uid, $chat, $msg);
		else
			$this->insertTgMsg($uid, $chat, $msg);
	}


	public function insertTgMsg(string $uid, int $chat, int $msg) {
		$sql = "INSERT INTO tg_msg(uid, chat_id, msg_id) VALUES (:uid, :chat, :msg)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':chat' => $chat,
			':msg' => $msg,
		]);
	}

	public function updateTgMsg(string $uid, int $chat, int $msg) {
		$sql = "UPDATE tg_msg SET msg_id = :msg WHERE uid = :uid AND chat_id = :chat";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':chat' => $chat,
			':msg' => $msg,
		]);
	}

	public function getTgMsg(string $uid, int $chat): int {
		$sql = "SELECT msg_id FROM tg_msg WHERE uid = :uid AND chat_id = :chat";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':chat' => $chat,
		]);

		$item = $stmt->fetch();

		return $item['msg_id'] ?? 0;
	}

	public function getTgMsgsByUid(string $uid): array {
		$sql = "SELECT chat_id, msg_id FROM tg_msg WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);

		$result = [];
		while ($item = $stmt->fetch())
			$result[] = $item;

		return $result;
	}

	public function deleteTgMsg(string $uid, int $chat) {
		$sql = "DELETE FROM tg_msg WHERE uid = :uid AND chat_id = :chat";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':chat' => $chat,
		]);
	}
}
