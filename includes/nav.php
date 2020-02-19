<nav class="ts basic fluid borderless menu horizontally scrollable">
	<div class="ts container">
<?php
$items = [
	'/' => '首頁',
	'/submit' => '投稿',
	'/review' => '審核'
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
<?php if (isset($_SESSION['nctu_id'])) { ?>
			<img class="ts mini circular image" src="https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg">&nbsp;<b><?= $_SESSION['name'] ?></b>
<?php } else { ?>
			<a class="item" href="/login-nctu">Login</a>
<?php } ?>
		</div>
	</div>
</nav>
