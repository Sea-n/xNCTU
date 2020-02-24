<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if (isset($_SESSION['nctu_id']) && !isset($USER))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

if (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
	if (!($post = $db->getSubmissionByUid($uid))) {
		http_response_code(404);
		exit('Post not found. 文章不存在');
	}

	if (isset($USER)) {
		$votes = $db->getVotesByUser($USER['nctu_id']);
		foreach ($votes as $vote)
			if ($vote['uid'] == $uid)
				$post['vote'] = $vote['vote'];
	}

	$posts = [$post];

} else {
	if (isset($USER))
		$posts = $db->getSubmissionsByVoter($USER['nctu_id'], 50);
	else
		$posts = $db->getSubmissions(50);
}

$postsCanVote = array_filter($posts, function($item) {
	return !isset($item['vote']);
});

if (isset($_GET['all'])) {
	$showAll = ($_GET['all'] == 'true');
} else {
	$showAll = (count($postsCanVote) == 0);
}

if (!$showAll)
	$posts = $postsCanVote;

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
if (isset($post)) {
	$hashtag = "待審貼文 {$post['uid']}";

	$DESC = $post['body'];
	$TITLE = "$hashtag $DESC";

	if (mb_strlen($TITLE) > 40)
		$TITLE = mb_substr($TITLE, 0, 40) . '...';

	if (mb_strlen($DESC) > 150)
		$DESC = mb_substr($DESC, 0, 150) . '...';

	if ($post['img'])
		$IMG = "https://x.nctu.app/img/{$post['img']}.jpg";
} else {
	$TITLE = '貼文審核';
	$IMG = 'https://x.nctu.app/assets/img/logo.png';
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
/* Time period 00:00 - 09:59 */
if (substr(date('H'), 0, 1) == '0') {
?>
		<div class="ts info message">
			<div class="header">現在時間 <?= date('H:i') ?></div>
			<p>系統已進入休眠模式，將於 10:00 起發送通過審核的貼文。這段時間內仍然可以正常投稿、審核。</p>
		</div>
<?php
}
if (count($posts) == 0) {
	if (isset($USER)) {
?>
			<h2 class="ts header">太棒了！您已審完所有投稿</h2>
			<p>歡迎使用 Telegram Bot 接收投稿通知，並在程式內快速審查</p>
<?php } else { ?>
			<h2 class="ts header">太棒了！目前沒有待審投稿</h2>
			<p>歡迎使用 Telegram Bot 接收投稿通知，並在程式內快速審查</p>
<?php } }
foreach ($posts as $post) {
	$uid = $post['uid'];
	$author_name = toHTML($post['author_name']);
	$img = "/img/{$post['img']}.jpg";
	$body = toHTML($post['body']);
	$time = humanTime($post['created_at']);
	$canVote = (isset($post['id']) || isset($post['vote']) || isset($post['deleted_at'])) ? 'disabled' : '';

	if (isset($_GET['uid'])) {
		if (isset($post['deleted_at'])) {
?>
			<div class="ts negative message">
				<div class="header">此文已刪除</div>
				<p>刪除原因：<?= toHTML($post['delete_note']) ?? '(無)' ?></p>
			</div>
<?php } else if (isset($post['id'])) { ?>
			<div class="ts positive message">
				<div class="header">文章已發出</div>
				<p>您可以在 <a href="/posts?id=<?= $post['id'] ?>">#靠交<?= $post['id'] ?></a> 找到這篇文章</p>
			</div>
<?php } else if (isset($USER) && empty($post['author_id']) && !isset($post['vote'])) { ?>
			<div class="ts warning message">
				<div class="header">注意：您目前為登入狀態</div>
				<p>提醒您，為自己的投稿按 <button class="ts vote positive button">通過</button> 或 <button class="ts vote negative button">駁回</button> 均會留下公開紀錄哦</p>
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
					<div class="header">投稿編號 <?= $uid ?></div>
<?php } else { ?>
					<div class="header"> <a href="?uid=<?= $uid ?>">投稿編號 <?= $uid ?></a></div>
<?php } ?>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
<?php if (isset($USER) && empty($post['author_id'])) { ?>
					<p>發文者 IP 位址：<?= ip_mask($post['ip']) ?></p>
<?php }
$photo = $post['author_photo'] ?? '';
?>
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <?= $author_name ?></img>
					</div>
					<span>投稿時間：<?= $time ?></span>
				</div>
				<div class="ts fluid bottom attached large buttons">
					<button class="ts positive <?= $canVote ?> button" onclick="approve('<?= $uid ?>');">通過貼文 (目前 <span id="approvals"><?= $post['approvals'] ?></span> 票)</button>
					<button class="ts negative <?= $canVote ?> button" onclick="reject('<?= $uid ?>');">駁回投稿 (目前 <span id="rejects"><?= $post['rejects'] ?></span> 票)</button>
				</div>
			</div>
<?php }
if (isset($_GET['uid']) && isset($USER)) {
	$votes = $db->getVotersBySubmission($post['uid']);
	if (count($votes) > 0) {
?>
			<table class="ts votes table">
				<thead>
					<tr>
						<th>#</th>
						<th></th>
						<th>系級</th>
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
		$dep = idToDep($id);
		$name = toHTML($user['name']);
?>
					<tr>
						<td><?= $i+1 ?></td>
						<td><?= $type ?></td>
						<td><?= $dep ?></td>
						<td><?= $name ?></td>
						<td><?= toHTML($vote['reason']) ?></td>
					</tr>
<?php } ?>
				</tbody>
			</table>
<?php } }
if (isset($USER) && !isset($_GET['uid'])) {
?>
			<div class="ts toggle checkbox">
				<input id="showAll" <?= $showAll ? 'checked' : '' ?> type="checkbox" onchange="location.href='?all='+this.checked;">
				<label for="showAll">顯示所有投稿</label>
			</div>
<?php } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
