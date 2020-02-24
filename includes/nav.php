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
		<div class="right fitted item" style="x-overflow: hidden;">
<?php
if (isset($USER)) {
	$photo = $USER['tg_photo'] ?? '';
?>
			<img class="ts circular related avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';">
			&nbsp;<b style="x-overflow: hidden;"><?= $USER['name'] ?></b>&nbsp;
			<a class="item" href="/logout" data-type="logout" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);" style="x-overflow: hidden; min-width: 50px;">
				<i class="log out icon"></i>
				<span style="x-overflow: hidden;">Logout</span>
			</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" data-type="login" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
