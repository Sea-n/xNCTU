<?php
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

$posts = $db->getPosts(0);

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '文章列表';
$IMG = 'https://x.nctu.app/assets/img/og.png';
include('includes/head.php');
?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">文章列表</h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>

		<div class="ts container" name="main">
			<div id="posts">
<?php
foreach ($posts as $post) {
?>
				<div class="ts card" id="post-<?= $post['id'] ?>" style="margin-bottom: 42px;">
					<div class="content">
						<div class="header"><a id="hashtag" href="/post/<?= $post['id'] ?>">#靠交<?= $post['id'] ?></a></div>
						<div id="body"><?= toHTML($post['body']) ?></div>
					</div>
				</div>
<?php } ?>
			</div>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
