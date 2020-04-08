<?php
/* Only Command-line Execution Allowed */
if (!isset($argv[1]))
	exit;

require('database.php');
$db = new MyDB();


switch ($argv[1]) {
case 'tg_photo':
	$tg_id = $argv[2];
	$USER = $db->getUserByTg($tg_id);

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $USER['tg_photo']);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$file = curl_exec($curl);
	curl_close($curl);

	file_put_contents("img/tg/{$tg_id}-x320.jpg", $file);
	system("ffmpeg -y -i img/tg/{$tg_id}-x320.jpg -vf scale=64x64 img/tg/{$tg_id}-x64.jpg");

	break;

default:
	echo "Unknown argument: {$argv[1]}";
}
