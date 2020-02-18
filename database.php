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

		if (mb_strlen($body) > 1024)
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

	public function getSubmissions(int $limit = 10) {
		$sql = "SELECT * FROM submissions";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute();

		$data = [];
		while ($limit-- && $item = $stmt->fetch())
			$data[] = $item;

		return $data;
	}

	public function voteSubmissions(string $uid, string $voter, int $vote, string $reason = '') {
		if ($vote != 1 && $vote != -1)
			return 'ERROR: Unknown vote.';

		$sql = "SELECT approval, rejects FROM submissions WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		if (!$stmt->fetch())
			return 'ERROR: uid not found.';

		$sql = "INSERT INTO votes(uid, vote, reason, voter) VALUES (:uid, :vote, :reason, :voter)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => $uid,
			':vote' => $vote,
			':reason' => $reason,
			':voter' => $voter
		]);

		$col = ($vote == 1 ? 'approval' : 'rejects');
		$sql = "UPDATE submissions SET $col = $col + 1 WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);

		$sql = "SELECT approval, rejects FROM submissions WHERE uid = :uid";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([':uid' => $uid]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}
