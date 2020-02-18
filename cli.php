<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('/usr/share/nginx/x.nctu.app/database.php');
$db = new MyDB();


switch ($argv[1]) {
case 'build':
	$sql = 'CREATE TABLE submissions (' .
		'uid TEXT NOT NULL UNIQUE, ' .
		'body TEXT NOT NULL, ' .
		'img TEXT NOT NULL, ' .
		'ip TEXT NOT NULL, ' .
		'author TEXT NOT NULL, ' .
		'approval INTEGER DEFAULT 0, ' .
		'rejects INTEGER DEFAULT 0, ' .
		'created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL)';
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();

	$sql = 'CREATE TABLE queue (' .
		'uid TEXT NOT NULL UNIQUE, ' .
		'id INTEGER NOT NULL, ' .
		'body TEXT NOT NULL, ' .
		'img TEXT NOT NULL, ' .
		'ip TEXT NOT NULL, ' .
		'author TEXT NOT NULL, ' .
		'created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL)';
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();

	$sql = 'CREATE TABLE votes (' .
		'uid TEXT NOT NULL, ' .
		'vote INTEGER NOT NULL, ' .
		'reason TEXT NOT NULL, ' .
		'voter TEXT NOT NULL, ' .
		'created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL)';
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();

	$sql = 'CREATE TABLE posts (' .
		'uid TEXT NOT NULL UNIQUE, ' .
		'id INTEGER NOT NULL, ' .
		'body TEXT NOT NULL, ' .
		'img TEXT NOT NULL, ' .
		'ip TEXT NOT NULL, ' .
		'author TEXT NOT NULL, ' .
		'created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL)';
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();
	break;

case 'dump':
	$sql = "SELECT * FROM main";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();
	while ($data = $stmt->fetch())
		printf("%-5s %-30s %10s  %s\n", $data['code'], $data['url'], $data['author'], $data['created_at']);
	break;

case 'export':
	$sql = "SELECT * FROM main";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();
	$result = [];
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC))
		$result[] = $data;
	echo json_encode($result, JSON_PRETTY_PRINT);
	break;

case 'import':
	$json = file_get_contents($argv[2]);
	$json = json_decode($json, true);
	foreach ($json as $data) {
		$sql = 'INSERT INTO main(' .
			join(', ', array_keys($data)) .
			') VALUES (:' .
			join(', :', array_keys($data)) .
			')';

		$stmt = $db->pdo->prepare($sql);
		foreach ($data as $k => $v)
			$stmt->bindValue(":$k", $v);
		$stmt->execute();
	}
	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
