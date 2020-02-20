<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if (!check_cf_ip($_SERVER['REMOTE_ADDR'] ?? '1.1.1.1'))
	exit("Please don't hack me.");

if (isset($_SESSION['nctu_id']) && !isset($USER))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

if (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
	if (!($post = $db->getSubmissionByUid($uid)))
		exit('Post not found. 文章不存在');

	if (isset($USER)) {
		$votes = $db->getVotesByUser($USER['nctu_id']);
		foreach ($votes as $vote)
			if ($vote['uid'] == $uid)
				$post['vote'] = $vote['vote'];
	}

	$posts = [$post];

} else {
	if (isset($USER))
		$posts = $db->getSubmissionsByVoter($USER['nctu_id'], 10);
	else
		$posts = $db->getSubmissions(10);
}

if (!isset($_GET['uid']) && !isset($_GET['all']))
	$posts = array_filter($posts, function($item) {
		return !isset($item['vote']);
	});

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '貼文審核';
if (isset($post)) {
	$hashtag = "待審貼文 {$post['uid']}";

	$DESC = $post['body'];
	$TITLE = "$hashtag $DESC";

	if (mb_strlen($TITLE) > 40)
		$TITLE = mb_substr($TITLE, 0, 40) . '...';

	if (mb_strlen($DESC) > 150)
		$DESC = mb_substr($DESC, 0, 150) . '...';

	if ($post['img'])
		$IMG = "/img/{$post['img']}";
}
include('includes/head.php');
?>
		<script src="/assets/js/review.js"></script>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">貼文審核</h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
if (count($posts) == 0) {
	if (isset($USER) && !isset($_GET['all'])) {
?>
			<h2 class="ts header">太棒了！您已審完所有貼文</h2>
			<p>歡迎使用 Telegram Bot 接收投稿通知，並在程式內快速審查</p>
			<p>目前僅顯示您未審核的文章，<a href="?all=1">點此查看所有貼文</a></p>
<?php } else { ?>
			<h2 class="ts header">太棒了！目前沒有待審貼文</h2>
			<p>歡迎使用 Telegram Bot 接收投稿通知，並在程式內快速審查</p>
<?php } }
foreach ($posts as $post) {
	$uid = $post['uid'];
	$author_name = $post['author_name'];
	$img = "/img/{$post['img']}";
	$body = toHTML($post['body']);
	$time = humanTime($post['created_at']);

	if (isset($_GET['uid'])) {
		if (isset($post['id'])) {
?>
			<div class="ts positive message">
				<div class="header">文章已發出</div>
				<p>您可以在 <a href="/posts?id=<?= $post['id'] ?>">#靠交<?= $post['id'] ?></a> 找到這篇文章</p>
			</div>
<?php } else if (isset($USER) && !isset($post['vote'])) { ?>
			<div class="ts warning message">
				<div class="header">注意：您目前為登入狀態</div>
				<p>提醒您，為自己的貼文按「通過」會留下公開紀錄哦</p>
			</div>
<?php } } ?>
			<div class="ts card" id="post-<?= $uid ?>" style="margin-bottom: 42px;">
<?php if (!empty($post['img'])) { ?>
				<div class="image">
					<img class="post-image" src="<?= $img ?>" />
				</div>
<?php } ?>
				<div class="content">
<?php if (isset($_GET['uid'])) { ?>
					<div class="header">貼文編號 <?= $uid ?></div>
<?php } else { ?>
					<div class="header"> <a href="?uid=<?= $uid ?>">貼文編號 <?= $uid ?></a></div>
<?php } ?>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
<?php if (isset($USER)) { ?>
					<p>發文者 IP 位址：<?= ip_mask($post['ip']) ?></p>
<?php }
$photo = 'https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg';
if (!empty($post['author_photo']))
	$photo = $post['author_photo'];
?>
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $photo ?>"> <?= $author_name ?></img>
					</div>
					<span>投稿時間：<?= $time ?></span>
				</div>
<?php if (!isset($post['id']) && !isset($post['vote'])) { ?>
				<div class="ts fluid bottom attached large buttons">
					<button class="ts positive button" onclick="approve('<?= $uid ?>');">通過貼文 (目前 <span id="approvals"><?= $post['approvals'] ?></span> 票)</button>
					<button class="ts negative button" onclick="reject('<?= $uid ?>');">駁回投稿 (目前 <span id="rejects"><?= $post['rejects'] ?></span> 票)</button>
				</div>
<?php } ?>
			</div>
<?php }
if (isset($_GET['uid']) && isset($USER)) {
	$votes = $db->getVotersBySubmission($post['uid'], 0);
	if (count($votes) > 0) {
?>
			<table class="ts table">
				<thead>
					<tr>
						<th>#</th>
						<th></th>
						<th>暱稱</th>
						<th>理由</th>
					</tr>
				</thead>
				<tbody>
<?php
	foreach ($votes as $i => $vote) {
		$type = $vote['vote'] == 1 ? '✅ 通過' : '❌ 駁回';
		$id = $vote['voter'];
		$user = $db->getUserByNctu($id);
		$name = $user['name'];
?>
					<tr>
						<td><?= $i+1 ?></td>
						<td><?= $type ?></td>
						<td><?= $name ?></td>
						<td><?= $vote['reason'] ?></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
<?php } } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
