<?php
class MyDB {
	public $pdo;

	public function __construct() {
		$this->pdo = new PDO('sqlite:/usr/share/nginx/x.nctu.app/sqlite.db');
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	/* Return error info or ['00000', null, null] on success */
	public function insertSubmission(string $uid, string $body, string $img, string $ip, string $author): array {
		if (strlen($uid) < 3)
			return ['SEAN', 0, 'UID too short.'];

		if (mb_strlen($body) < 5)
			return ['SEAN', 0, 'Body too short.'];

		if (mb_strlen($body) > 1000)
			return ['SEAN', 0, 'Body too long.'];

		if (!empty($img) && !preg_match('#^[0-9a-zA-Z]{3,5}\.[a-z]{3}$#', $img))
			return ['SEAN', 0, 'Image invaild.'];

		$sql = "INSERT INTO submissions(uid, body, img, ip, author) VALUES (:uid, :body, :img, :ip, :author)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':body' => $body,
			':img' => $img,
			':ip' => $ip,
			':author' => $author,
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

		$sql = "SELECT * FROM submissions";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$results = [];
		while ($limit-- && $item = $stmt->fetch())
			if (!isset($item['id']) && !isset($item['deleted_at']))
				$results[] = $item;

		return $results;
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

		$sql = "SELECT uid FROM submissions WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		if (!$stmt->fetch())
			return ['ok' => false, 'msg' => 'uid not found.'];

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
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		$result['ok'] = true;
		return $result;
	}

	private function getVotersBySubmissions(string $uid, int $vote) {
		if ($vote != 1 && $vote != -1)
			return false;

		$sql = "SELECT voter FROM votes WHERE uid = :uid AND vote = :vote";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':vote' => $vote
		]);

		$results = [];
		while ($item = $stmt->fetch())
			$results[] = $item['voter'];

		return $results;
	}

	private function submissionEligibe(array $item) {
		$dt = time() - strtotime($item['created_at']);

		/* Rule for Logged-in users */
		if (strpos($item['author'], '匿名') === false) {
			if ($dt < 5*60)
				return false;

			if ($item['approvals'] < $item['rejects'])
				return false;

			return true;
		}

		/* Rule for NCTU IP address */
		if (substr($item['ip'], 0, 8) == '140.113.') {
			if ($dt < 10*60)
				return false;

			if ($item['approvals'] < 2)
				return false;

			if ($item['approvals'] < $item['rejects'])
				return false;

			return true;
		}

		/* Rule for Taiwan IP address */
		if (strpos($item['author'], '境外') === false) {
			if ($dt < 30*60)  // 0 - 30min
				return false;

			if ($dt < 12*60*60) {  // 30min - 12hr
				if ($item['approvals'] <= $item['rejects'])
					return false;

				if ($dt < 2*60*60) {  // 30min - 2hr
					if ($item['approvals'] < 5)
						return false;
				} else if ($dt < 6*60*60) {  // 2hr - 6hr
					if ($item['approvals'] < 2)
						return false;
				} else if ($dt < 12*60*60) {  // 6hr - 12hr
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
		$submissions = $this->getSubmissions(0);

		foreach ($submissions as $item) {
			if ($this->submissionEligibe($item)) {
				$post = $item;
				break;
			}
		}

		if (!isset($post))
			return false;

		$approvers = $this->getVotersBySubmissions($post['uid'], 1);
		$approvers = join(', ', $approvers);

		$rejecters = $this->getVotersBySubmissions($post['uid'], -1);
		$rejecters = join(', ', $rejecters);

		$sql = "INSERT INTO posts(body, img, ip, author, approvers, rejecters, submitted_at) VALUES (:body, :img, :ip, :author, :approvers, :rejecters, :submitted_at)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':body' => $post['body'],
			':img' => $post['img'],
			':ip' => $post['ip'],
			':author' => $post['author'],
			':approvers' => $approvers,
			':rejecters' => $rejecters,
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

	public function updatePostSns(int $id, int $tg, int $plurk, int $twitter, int $fb) {
		$sql = "UPDATE posts SET telegram_id = :tg, plurk_id = :plurk, twitter_id = :twitter, facebook_id = :fb WHERE id = :id";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':id' => $id,
			':tg' => $tg,
			':plurk' => $plurk,
			':twitter' => $twitter,
			':fb' => $fb
		]);
	}
}
