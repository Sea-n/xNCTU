<?php
require_once('utils.php');
class MyDB {
	public $pdo;

	public function __construct() {
		$this->pdo = new PDO('sqlite:/usr/share/nginx/x.nctu.app/sqlite.db');
		$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/* Return error info or ['00000', null, null] on success */
	public function insertSubmission(string $uid, string $body, string $img, string $ip, string $author_name, $author_id, $author_photo): array {
		if (strlen($uid) < 3)
			return ['SEAN', 0, 'UID too short.'];

		if (mb_strlen($body) < 5)
			return ['SEAN', 0, 'Body too short.'];

		if (mb_strlen($body) > 1000)
			return ['SEAN', 0, 'Body too long.'];

		if (!empty($img) && !preg_match('#^[0-9a-zA-Z]{4}$#', $img))
			return ['SEAN', 0, 'Image invaild.'];

		$sql = "INSERT INTO submissions(uid, body, img, ip, author_name, author_id, author_photo) VALUES (:uid, :body, :img, :ip, :author_name, :author_id, :author_photo)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':body' => $body,
			':img' => $img,
			':ip' => $ip,
			':author_name' => $author_name,
			':author_id' => $author_id,
			':author_photo' => $author_photo,
		]);

		return $stmt->errorInfo();
	}

	public function getSubmissionByUid(string $uid) {
		$sql = "SELECT * FROM submissions WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		return $stmt->fetch();
	}

	public function getSubmissions(int $limit) {
		if ($limit == 0) $limit = 9487;

		$sql = "SELECT * FROM submissions ORDER BY created_at DESC";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch()) {
			if (isset($item['id']))
				continue;

			if (isset($item['deleted_at']))
				continue;

			$results[] = $item;
			$limit--;
		}

		return $results;
	}

	public function getSubmissionsByVoter(string $nctu_id, int $limit) {
		if ($limit == 0) $limit = 9487;

		$data = $this->getVotesByUser($nctu_id);
		$votes = [];
		foreach ($data as $item)
			$votes[ $item['uid'] ] = $item['vote'];

		$sql = "SELECT * FROM submissions ORDER BY created_at DESC";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($item = $stmt->fetch()) {
			if (isset($item['id']))
				continue;

			if (isset($item['deleted_at']))
				continue;

			if (isset($votes[ $item['uid'] ]))
				$item['vote'] = $votes[ $item['uid'] ];

			$results[] = $item;
			$limit--;
		}

		return $results;
	}

	public function deleteSubmission(string $uid, string $reason) {
		$sql = "UPDATE submissions SET (delete_note, deleted_at) = (:reason, datetime('now')) WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':reason' => $reason
		]);
	}

	public function getPostById(string $id) {
		$sql = "SELECT * FROM posts WHERE id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':id' => $id]);
		return $stmt->fetch();
	}

	public function getPosts(int $limit) {
		if ($limit == 0) $limit = 9487;

		$sql = "SELECT * FROM posts ORDER BY created_at DESC";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($limit-- && $item = $stmt->fetch())
			if (!isset($item['deleted_at']))
				$results[] = $item;

		return $results;
	}

	public function voteSubmissions(string $uid, string $voter, int $vote, string $reason = '') {
		if ($vote == 1)
			$type = 'approvals';
		else if ($vote == -1)
			$type = 'rejects';
		else
			return ['ok' => false, 'msg' => 'Unknown vote.'];

		if ($uid == 'TEST')
			return [
				'ok' => true,
				'approvals' => 87,
				'rejects' => 42
			];

		$sql = "SELECT id, delete_note FROM submissions WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		if (!($item = $stmt->fetch()))
			return ['ok' => false, 'msg' => 'uid not found.'];

		if (isset($item['id']))
			return ['ok' => false, 'msg' => 'Already posted.'];

		if (!empty($item['delete_note']))
			return [
				'ok' => false,
				'msg' => 'Deleted: ' . $item['delete_note']
			];

		$sql = "SELECT created_at FROM votes WHERE uid = :uid AND voter = :voter";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':voter' => $voter
		]);
		if ($stmt->fetch())
			return ['ok' => false, 'msg' => 'Already voted.'];

		$sql = "INSERT INTO votes(uid, vote, reason, voter) VALUES (:uid, :vote, :reason, :voter)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':vote' => $vote,
			':reason' => $reason,
			':voter' => $voter
		]);

		$sql = "UPDATE submissions SET $type = $type + 1 WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);

		$sql = "SELECT approvals, rejects FROM submissions WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		$result = $stmt->fetch();

		$result['ok'] = true;
		return $result;
	}

	public function getVotersBySubmission(string $uid) {
		$sql = "SELECT * FROM votes WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	public function getVotesByUser(string $nctu_id) {
		$sql = "SELECT * FROM votes WHERE voter = :nctu_id ORDER BY created_at DESC";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':nctu_id' => $nctu_id]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	private function isSubmissionEligible(array $item) {
		$dt = time() - strtotime($item['created_at']);

		/* Rule for Logged-in users */
		if (!empty($item['author_id'])) {
			if ($dt < 10*60)
				return false;

			if ($item['approvals'] < $item['rejects'])
				return false;

			return true;
		}

		/* Rule for NCTU IP address */
		if (substr($item['ip'], 0, 8) == '140.113.'
		 || substr($item['ip'], 0, 9) == '2001:f18:') {
			if ($dt < 30*60)
				return false;

			if ($item['approvals'] < $item['rejects'])
				return false;

			if ($dt < 2*60*60) {
				if ($item['approvals'] < 2)
					return false;
			} else if ($dt < 6*60*60) {
				if ($item['approvals'] < 1)
					return false;
			}

			return true;
		}

		/* Rule for Taiwan IP address */
		if (strpos($item['author_name'], '境外') === false) {
			if ($dt < 60*60)  // 0 - 60min
				return false;

			if ($dt < 24*60*60) {  // 60min - 24hr
				if ($item['approvals'] <= $item['rejects'])
					return false;

				if ($dt < 2*60*60) {  // 30min - 2hr
					if ($item['approvals'] < 5)
						return false;
				} else if ($dt < 6*60*60) {  // 2hr - 6hr
					if ($item['approvals'] < 3)
						return false;
				} else                    {  // 6hr - 24hr
					if ($item['approvals'] < 1)
						return false;
				}
			} else {
				if ($item['approvals'] < $item['rejects'])
					return false;
			}

			return true;
		}

		/* Rule for Foreign IP address */
		if (true) {
			if ($dt < 60*60)
				return false;

			if ($item['approvals'] < 10)
				return false;

			if ($item['approvals'] < 2*$item['rejects'])
				return false;

			return true;
		}
	}

	public function getPostReady() {
		/* Check undone post */
		$posts = $this->getPosts(1);
		if (isset($posts[0])
		&& ($posts[0]['telegram_id'] <= 0
		 || $posts[0]['plurk_id']    <= 0
		 || $posts[0]['twitter_id']  <= 0
		 || $posts[0]['facebook_id'] <= 0))
		 return $posts[0];
		unset($posts);

		/* Get all pending submissions, oldest first */
		$submissions = $this->getSubmissions(0);
		array_reverse($submissions);

		foreach ($submissions as $item) {
			if ($this->isSubmissionEligible($item)) {
				$post = $item;
				break;
			}
		}

		/* No eligible pending submission */
		if (!isset($post))
			return false;

		$sql = "INSERT INTO posts(uid, body, img, ip, author_name, author_id, author_photo, submitted_at) VALUES (:uid, :body, :img, :ip, :author_name, :author_id, :author_photo, :submitted_at)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $post['uid'],
			':body' => $post['body'],
			':img' => $post['img'],
			':ip' => $post['ip'],
			':author_name' => $post['author_name'],
			':author_id' => $post['author_id'],
			':author_photo' => $post['author_photo'],
			':submitted_at' => $post['created_at']
		]);

		$id = $this->pdo->lastInsertId();
		$post['id'] = $id;

		$sql = "UPDATE submissions SET id = :id WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $post['uid'],
			':id' => $id
		]);

		return $post;
	}

	public function updatePostSns(int $id, string $type, int $pid) {
		$sql = "UPDATE posts SET {$type}_id = :pid WHERE id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':id' => $id,
			':pid' => $pid,
		]);
	}

	public function insertUserNctu(string $nctu_id, string $mail) {
		$sql = "SELECT nctu_id FROM users WHERE nctu_id = :nctu_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':nctu_id' => $nctu_id]);

		if ($stmt->fetch())
			return;

		$sql = "INSERT INTO users(name, nctu_id, nctu_mail) VALUES (:name, :nctu_id, :mail)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':name' => $nctu_id,
			':nctu_id' => $nctu_id,
			':mail' => $mail
		]);
	}

	public function insertUserTg(string $nctu_id, array $tg) {
		$sql = "SELECT nctu_id FROM users WHERE nctu_id = :nctu_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':nctu_id' => $nctu_id]);

		if (!$stmt->fetch())
			return;

		$name = $tg['first_name'];
		if (isset($tg['last_name']))
			$name .= ' ' . $tg['last_name'];

		$sql = "UPDATE users SET (tg_id, tg_name, tg_username, tg_photo) = (:tg_id, :name, :username, :photo) WHERE nctu_id = :nctu_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':nctu_id' => $nctu_id,
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

	public function getUserByNctu(string $id) {
		$sql = "SELECT * FROM users WHERE nctu_id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':id' => $id]);
		return $stmt->fetch();
	}

	public function getUserByTg(int $id) {
		$sql = "SELECT * FROM users WHERE tg_id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':id' => $id]);
		return $stmt->fetch();
	}

	public function getTgUsers() {
		$sql = "SELECT * FROM users WHERE tg_id > 0";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item;

		return $results;
	}

	public function updateUserTgProfile(array $tg) {
		$name = $tg['first_name'];
		if (isset($tg['last_name']))
			$name .= ' ' . $tg['last_name'];

		$sql = "UPDATE users SET (tg_name, tg_username, tg_photo) = (:name, :username, :photo) WHERE tg_id = :tg_id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':tg_id' => $tg['id'],
			':name' => $name,
			':username' => $tg['username'] ?? '',
			':photo' => $tg['photo_url'] ?? '',
		]);
	}
}
