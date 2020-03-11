<?php
if (!isset($_SESSION)) {
	session_start(['read_and_close' => true]);
}

if (!isset($db)) {
	require_once(__DIR__ . '/../database.php');
	$db = new MyDB();
}

if (isset($_SESSION['nctu_id']) && !isset($USER))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

$items = [
	'/' => '首頁',
	'/submit' => '投稿',
	'/review' => '審核',
	'/posts' => '文章',
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
		<div class="right fitted item" id="nav-right">
<?php
if (isset($USER)) {
	$photo = $USER['tg_photo'] ?? '';
	if (empty($photo))
		$photo = genPic($USER['nctu_id']);
?>
			<img class="ts circular related avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';">
			&nbsp;<b id="nav-name" style="overflow: hidden;"><?= toHTML($USER['name']) ?></b>&nbsp;
			<a class="item" href="/logout" data-type="logout" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">
				<i class="log out icon"></i>
				<span class="tablet or large device only">Logout</span>
			</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" data-type="login" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
