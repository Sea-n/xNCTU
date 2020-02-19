<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('/usr/share/nginx/x.nctu.app/database.php');
$db = new MyDB();


switch ($argv[1]) {
case 'build':
	$sql = 'CREATE TABLE submissions (' .
		'uid TEXT PRIMARY KEY UNIQUE, ' .
		'id INTEGER, ' .
		'body TEXT NOT NULL, ' .
		"img TEXT DEFAULT '', " .
		'ip TEXT NOT NULL, ' .
		'author_name TEXT NOT NULL, ' .
		"author_id TEXT DEFAULT '', " .
		"author_photo TEXT DEFAULT '', " .
		'approvals INTEGER DEFAULT 0, ' .
		'rejects INTEGER DEFAULT 0, ' .
		"deleted_at DATETIME," .
		"created_at DATETIME DEFAULT (datetime('now','localtime')))";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();

	$sql = 'CREATE TABLE votes (' .
		'uid TEXT NOT NULL, ' .
		'voter TEXT NOT NULL, ' .
		'vote INTEGER NOT NULL, ' .
		"reason TEXT DEFAULT '', " .
		"created_at DATETIME DEFAULT (datetime('now','localtime')))";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();

	$sql = 'CREATE TABLE posts (' .
		'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
		'body TEXT NOT NULL, ' .
		"img TEXT DEFAULT '', " .
		'ip TEXT NOT NULL, ' .
		'author_name TEXT NOT NULL, ' .
		"author_id TEXT DEFAULT '', " .
		"author_photo TEXT DEFAULT '', " .
		"approvers TEXT DEFAULT '', " .
		"rejecters TEXT DEFAULT '', " .
		'telegram_id INTEGER DEFAULT 0, ' .
		'plurk_id INTEGER DEFAULT 0, ' .
		'twitter_id INTEGER DEFAULT 0, ' .
		'facebook_id INTEGER DEFAULT 0, ' .
		"submitted_at DATETIME," .
		"deleted_at DATETIME," .
		"created_at DATETIME DEFAULT (datetime('now','localtime')))";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();

	$sql = 'CREATE TABLE users (' .
		'name TEXT, ' .
		'nctu_id TEXT NOT NULL, ' .
		'nctu_mail TEXT, ' .
		'tg_id INTEGER, ' .
		'tg_name TEXT, ' .
		'tg_username TEXT, ' .
		'tg_photo TEXT, ' .
		"deleted_at DATETIME," .
		"created_at DATETIME DEFAULT (datetime('now','localtime')))";
	$stmt = $db->pdo->prepare($sql);
	$stmt->execute();
	break;

case 'dump':
	$data = [];

	$tables = ['submissions', 'votes', 'posts', 'users'];
	foreach ($tables as $table) {
		$sql = "SELECT * FROM $table";
		$stmt = $db->pdo->prepare($sql);
		$stmt->execute();
		$data[$table] = [];
		while ($item = $stmt->fetch(PDO::FETCH_ASSOC))
			$data[$table][] = $item;
	}

	echo json_encode($data, JSON_PRETTY_PRINT);
	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
