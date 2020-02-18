<?php
class MyDB {
	public $pdo;

	public function __construct() {
		$this->pdo = new PDO('sqlite:/usr/share/nginx/x.nctu.app/sqlite.db');
		$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function rand58(int $len = 1): string {
		$base58 = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

		$rand = '';
		for ($_=0; $_<$len; $_++)
			$rand .= $base58[rand(0, 57)];

		return $rand;
	}

	/* Return error info or ['00000', null, null] on success */
	public function insertSubmission(string $body, string $img, string $ip, string $author): array {
		if (mb_strlen($body) < 5)
			return ['SEAN', 0, 'Body too short.'];

		if (mb_strlen($body) > 1024)
			return ['SEAN', 0, 'Body too long.'];

		if (!preg_match('#^[0-9a-zA-Z]{0,5}$#', $img))
			return ['SEAN', 0, 'Image invaild.'];

		$uid = rand58(5);

		$sql = "INSERT INTO submissions(uid, body, img, ip, author) VALUES (:uid, :body, :img, :ip, :author)";
		$stmt = $this->pdo->prepare($sql);
		$stmt->execute([
			':uid' => rand58(5),
			':body' => $body,
			':img' => $img,
			':ip' => $ip,
			':author' => $author,
		]);

		return $stmt->errorInfo();
	}
}
