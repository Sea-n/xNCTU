<?php
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}
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
<?php if (isset($_SESSION['name'])) { ?>
			<img class="ts mini circular image" src="https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg">&nbsp;<b><?= $_SESSION['name'] ?></b>
			<a class="item" href="/logout" data-type="logout">Logout</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" data-type="login">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
