<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('database.php');
$db = new MyDB();


switch ($argv[1]) {
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
