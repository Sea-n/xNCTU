<?php
session_start(['read_and_close' => true]);
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
		<script src="/assets/js/posts.js"></script>
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
	$body = toHTML($post['body']);
	$time = humanTime($post['created_at']);
	$ts = strtotime($post['created_at']);

	unset($author);
	if (!empty($post['author_id'])) {
		$author = $db->getUserByNctu($post['author_id']);
		$author_name = toHTML($author['name']);
	} else
		$author_name = toHTML($post['author_name']);

	$ip_masked = ip_mask($post['ip_addr']);

	$author_photo = $author['tg_photo'] ?? '';
	if (empty($author_photo))
		$author_photo = genPic($ip_masked);
?>
			<div class="ts card" id="post-<?= $id ?>" style="margin-bottom: 42px;">
<?php if ($post['has_img']) { ?>
				<div class="image">
					<img class="post-image" src="/img/<?= $post['uid'] ?>.jpg" />
				</div>
<?php } ?>
				<div class="content">
					<div class="header"><a href="/post/<?= $id ?>">#靠交<?= $id ?></a></div>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $author_photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <?= $author_name ?>
<?php if (isset($USER) && empty($post['author_id'])) { ?>
						<br><span class="right floated">(<?= $ip_masked ?>)</span>
<?php } ?>
					</div>
					<p style="margin-top: 0; line-height: 1.7em">
						<span>審核狀況：<button class="ts vote positive button">通過</button>&nbsp;<?= $post['approvals'] ?>&nbsp;票 /&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;<?= $post['rejects'] ?>&nbsp;票</span><br>
						<span>投稿時間：<time data-ts="<?= $ts ?>"><?= $time ?></time></span>
					</p>
				</div>
			</div>
<?php } ?>

			<template id="post">
				<div class="ts card" id="post-XXXX" style="margin-bottom: 42px;">
					<div class="image">
						<img id="img" class="post-image" />
					</div>
					<div class="content">
						<div class="header"><a id="hashtag">#靠交000</a></div>
						<p id="body"></p>
					</div>
					<div class="extra content">
						<div class="right floated author">
							<img class="ts circular avatar image" id="author-photo"> <span id="author-name">Sean</span>
							<br><span class="right floated">(<span id="author-ip">140.113.***.*87</span>)</span>
						</div>
						<p style="margin-top: 0; line-height: 1.7em">
							<span>審核狀況：<button class="ts vote positive button">通過</button>&nbsp;<span id="approvals">87</span>&nbsp;票 /&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;<span id="rejects">42</span>&nbsp;票</span>
							<span>投稿時間：<time id="time">01 月 11 日 08:17</time></span>
						</p>
					</div>
				</div>
			</template>
		</div>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
