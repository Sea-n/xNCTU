<?php
/**
 * ====================
 * CAT-AVATAR-GENERATOR
 * ====================
 *
 * @author: Andreas Gohr, David Revoy
 * Modified by Sean Wei
 *
 * This PHP is licensed under the short and simple permissive:
 * [MIT License](https://en.wikipedia.org/wiki/MIT_License)
 *
 * Original repo: https://framagit.org/Deevad/cat-avatar-generator/
 *
**/

header('Pragma: public');
header('Cache-Control: max-age=2592000'); # 30 days
header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));
header('Content-Type: image/jpeg');

$seed = $_GET["seed"];
$seed = preg_replace('/[^A-Za-z0-9]/', '_', $seed);
$seed = preg_replace('/___+/', '__', $seed);
$seed = substr($seed, 0, 42) . '';
$cachefile = "avatars/cache/{$seed}.jpg";

// Serve from the cache if it is younger than $cachetime
if (file_exists($cachefile)) {
	readfile($cachefile);
	exit;
}

// ...Or start generation
ob_start();

// render the picture:
build_cat($seed);

// Save/cache the output to a file
$savedfile = fopen($cachefile, 'w+'); # w+ to be at start of the file, write mode, and attempt to create if not existing.
fwrite($savedfile, ob_get_contents());
fclose($savedfile);
ob_end_flush();

function build_cat($seed = '') {
	// init random seed
	if ($seed) {
		$seed = substr(md5($seed), 0, 6);
		srand(hexdec($seed));
	}

	// throw the dice for body parts
	$parts = [
		'body' => rand(1, 15),
		'fur' => rand(1, 10),
		'eyes' => rand(1, 15),
		'mouth' => rand(1, 10),
		'accessorie' => rand(1, 20)
	];

	// restore random seed
	if ($seed) srand();


	// create backgound
	$cat = @imagecreatetruecolor(70, 70)
		or die("GD image create failed");
	$white = imagecolorallocate($cat, 255, 255, 255);
	imagefill($cat, 0, 0, $white);

	// add parts
	foreach ($parts as $part => $num) {
		$file = dirname(__FILE__) . "/avatars/{$part}_{$num}.png";

		$im = @imagecreatefrompng($file);
		if (!$im) die("Failed to load $file.");
		imageSaveAlpha($im, true);
		imagecopy($cat, $im, 0, 0, 0, 0, 70, 70);
		imagedestroy($im);
	}

	imagejpeg($cat, NULL, 87);
	imagedestroy($cat);
}
