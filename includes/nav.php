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

$items = [
	'/' => '首頁',
	'/submit' => '投稿',
	'/review' => '審核',
	'/posts' => '文章列表',
];

?>
<nav class="ts basic fluid borderless menu horizontally scrollable">
	<div class="ts container">
<?php
foreach ($items as $href => $name) {
	$uri = $_SERVER['REQUEST_URI'];
	$uri = explode('?', $uri)[0];
	if ($uri == $href)
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
			&nbsp;<b><?= $USER['name'] ?></b>&nbsp;
			<a class="item" href="/logout" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);" data-type="login" data-type="logout">Logout</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);" data-type="login">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
