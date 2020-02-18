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
	$sql = "SELECT * FROM submissions";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();
	$data = [];
	while ($item = $stmt->fetch(PDO::FETCH_ASSOC))
		$data[] = $item;
	echo json_encode($data, JSON_PRETTY_PRINT);
	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
