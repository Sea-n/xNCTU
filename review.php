<?php
session_start();
require('utils.php');
require('database.php');
$db = new MyDB();

if (!check_cf_ip($_SERVER['REMOTE_ADDR'] ?? '1.1.1.1'))
	exit("Please don't hack me.");

if (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
	if (!($post = $db->getSubmissionByUid($uid)))
		exit('Post not found. 文章不存在');
	$posts = [$post];
} else
	$posts = $db->getSubmissions();

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php $TITLE = '貼文審核'; include('includes/head.php'); ?>
		<script src="/assets/js/review.js"></script>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">貼文審核</h1>
				<div class="description">靠交 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
foreach ($posts as $post) {
	$uid = $post['uid'];
	$author = $post['author'];
	$img = "/img/{$post['img']}";
	$body = toHTML($post['body']);
	$time = humanTime($post['created_at']);
?>
			<p><?= isset($_GET['uid']) ? '提醒您，為自己的貼文按「通過」會留下公開紀錄哦' : '' ?></p>
			<div class="ts card" id="post-<?= $uid ?>" style="margin-bottom: 42px;">
<?php if (!empty($post['img'])) { ?>
				<div class="image">
					<img src="<?= $img ?>" />
				</div>
<?php } ?>
				<div class="content">
					<div class="header">貼文編號 <?= $uid ?></div>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
					<div class="right floated author">
						<img class="ts circular avatar image" src="https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg"> <?= $author ?></img>
					</div>
					<span>投稿時間：<?= $time ?></span>
				</div>
				<div class="ts fluid bottom attached large buttons">
					<button class="ts positive button" onclick="approve('<?= $uid ?>');">通過貼文 (目前 <span id="approval"><?= $post['approval'] ?></span> 票)</button>
					<button class="ts negative button" onclick="reject('<?= $uid ?>');">駁回投稿 (目前 <span id="rejects"><?= $post['rejects'] ?></span> 票)</button>
				</div>
			</div>
<?php } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
