<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('utils.php');
require('database.php');
$db = new MyDB();


switch ($argv[1]) {
case 'dump':
	$data = [];

	$tables = ['posts', 'votes', 'users', 'tg_msg'];
	foreach ($tables as $table) {
		$sql = "SELECT * FROM $table ORDER BY created_at DESC";
		$stmt = $db->pdo->prepare($sql);
		$stmt->execute();
		$data[$table] = [];
		while ($item = $stmt->fetch()) {
			if (isset($item['nctu_id']))
				$item['nctu_id'] = idToDep($item['nctu_id']) . ' ' . $item['nctu_id'];

			if (isset($item['voter']))
				$item['voter'] = idToDep($item['voter']) . ' ' . $item['voter'];

			$data[$table][] = $item;
		}
	}

	echo json_encode($data, JSON_PRETTY_PRINT);
	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
