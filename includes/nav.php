<?php
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

if (!isset($db)) {
	require_once('database.php');
	$db = new MyDB();
}

if (isset($_SESSION['nctu_id']) && !isset($USER))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

?>
<nav class="ts basic fluid borderless menu horizontally scrollable">
	<div class="ts container">
<?php
$items = [
	'/' => '首頁',
	'/submit' => '投稿',
	'/review' => '審核',
	'/posts' => '文章列表',
];
foreach ($items as $href => $name) {
	if ($_SERVER['REQUEST_URI'] == $href)
		$class = 'active item';
	else
		$class = 'item';

	echo "<a class='$class' href='$href'>$name</a>\n";
}
?>
		<div class="right fitted item">
<?php
if (isset($USER['name'])) {
	$photo = 'https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg';
	if (isset($USER['tg_photo']) && !empty($USER['tg_photo']))
		$photo = $USER['tg_photo'];
?>
			<img class="ts mini circular image" src="<?= $photo ?>">
			&nbsp;<b><?= $USER['name'] ?></b>
			<a class="item" href="/logout" data-type="logout">Logout</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" data-type="login">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
