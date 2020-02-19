<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if (isset($_GET['id'])) {
	if (!($post = $db->getPostById($_GET['id'])))
		exit('Post not found. 文章不存在');
	$posts = [$post];
} else
	$posts = $db->getPosts(10);

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php $TITLE = '文章列表'; include('includes/head.php'); ?>
		<script src="/assets/js/review.js"></script>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">文章列表</h1>
				<div class="description">靠交 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
foreach ($posts as $post) {
	$id = $post['id'];
	$author = $post['author'];
	$img = "/img/{$post['img']}";
	$body = toHTML($post['body']);
	$time = humanTime($post['submitted_at']);
?>
			<div class="ts card" id="post-<?= $id ?>" style="margin-bottom: 42px;">
<?php if (!empty($post['img'])) { ?>
				<div class="image">
					<img src="<?= $img ?>" />
				</div>
<?php } ?>
				<div class="content">
<?php if (isset($_GET['id'])) { ?>
					<div class="header">#靠交<?= $id ?></div>
<?php } else { ?>
					<a class="header" href="?id=<?= $id ?>">#靠交<?= $id ?></a>
<?php } ?>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
<?php
if (isset($_GET['id'])) {
$plurk = base_convert($post['plurk_id'], 10, 36);
?>
					<p>Telegram: <a target="_blank" href="https://t.me/s/xNCTU/<?= $post['telegram_id'] ?>">@xNCTU/<?= $post['telegram_id'] ?></a></p>
					<p>Facebook: <a target="_blank" href="https://www.facebook.com/xNCTU/posts/<?= $post['facebook_id'] ?>">@xNCTU/<?= $post['facebook_id'] ?></a></p>
					<p>Plurk: <a target="_blank" href="https://www.plurk.com/p/<?= $plurk ?>">@xNCTU/<?= $plurk ?></a></p>
					<p>Twitter: <a target="_blank" href="https://twitter.com/x_NCTU/status/<?= $post['twitter_id'] ?>">@x_NCTU/<?= $post['twitter_id'] ?></a></p>
<?php
if (!empty($post['approvers']))
	echo "<p>表決通過：{$post['approvers']}</p>\n";
if (!empty($post['rejectors']))
	echo "<p>表決駁回：{$post['rejectors']}</p>\n";
}
?>
					<div class="right floated author">
						<img class="ts circular avatar image" src="https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg"> <?= $author ?></img>
					</div>
					<span>投稿時間：<?= $time ?></span>
				</div>
			</div>
<?php } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
