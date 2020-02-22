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

if (isset($USER))
	$items = [
		'/' => '首頁',
		'/submit' => '投稿',
		'/review' => '審核',
		'/posts' => '文章',
	];
else
	$items = [
		'/' => '首頁',
		'/submit' => '投稿',
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
			<img class="ts circular avatar image" src="<?= $photo ?>">
			&nbsp;<b><?= $USER['name'] ?></b>&nbsp;
			<a class="item" href="/logout" data-type="logout" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">Logout</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" data-type="login" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
