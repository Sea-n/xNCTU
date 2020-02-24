<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

/* Backward compatible since 25 Feb 2019 */
if (isset($_GET['id'])) {
	http_response_code(301);
	header("Location: /post/{$_GET['id']}");
	exit;
}

$posts = $db->getPosts(50);

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '文章列表';
$IMG = 'https://x.nctu.app/assets/img/logo.png';
include('includes/head.php');
?>
		<script src="/assets/js/review.js"></script>
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
<?php
foreach ($posts as $post) {
	$id = $post['id'];
	$img = "/img/{$post['img']}.jpg";
	$body = toHTML($post['body']);
	$time = humanTime($post['submitted_at']);

	unset($author);
	if (!empty($post['author_id'])) {
		$author = $db->getUserByNctu($post['author_id']);
		$author_name = toHTML($author['name']);
	} else
		$author_name = toHTML($post['author_name']);
?>
			<div class="ts card" id="post-<?= $id ?>" style="margin-bottom: 42px;">
<?php if (!empty($post['img'])) { ?>
				<div class="image">
					<img class="post-image" src="<?= $img ?>" />
				</div>
<?php } ?>
				<div class="content">
					<div class="header"><a href="/post/<?= $id ?>">#靠交<?= $id ?></a></div>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
<?php if (isset($USER) && empty($post['author_id'])) { ?>
					<p>發文者 IP 位址：<?= ip_mask($post['ip']) ?></p>
<?php }
$photo = $author['tg_photo'] ?? '';
?>
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <?= $author_name ?></img>
					</div>
					<span>投稿時間：<?= $time ?></span>
				</div>
			</div>
<?php } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
