<?php
session_start(['read_and_close' => true]);
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if (isset($_SESSION['nctu_id']) && !isset($USER))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

if (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
	if (!($post = $db->getPostByUid($uid))) {
		http_response_code(404);
		exit('Post not found. 文章不存在');
	}

	if (isset($USER)) {
		$canVote = $db->canVote($uid, $USER['nctu_id']);
		if (!$canVote['ok'])
			$post['vote'] = 87;
	}

	$posts = [$post];

} else {
	if (isset($_GET['deleted'])) {
		if (isset($USER)) {
			$posts = $db->getDeletedSubmissions(50);
		} else
			$posts = [];
	} else if (isset($USER))
		$posts = $db->getSubmissionsForVoter(50, true, $USER['nctu_id']);
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

	if ($post['has_img'])
		$IMG = "https://x.nctu.app/img/{$post['uid']}.jpg";
} else if (isset($_GET['deleted'])) {
	$TITLE = '已刪投稿';
	$IMG = 'https://x.nctu.app/assets/img/og.png';
} else {
	$TITLE = '貼文審核';
	$IMG = 'https://x.nctu.app/assets/img/og.png';
}
include('includes/head.php');
?>
		<script src="/assets/js/review.js"></script>
	</head>
<?php if (isset($uid)) { ?>
	<body data-uid="<?= $uid ?>">
<?php } else { ?>
	<body>
<?php }
include('includes/nav.php');
?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header"><?= isset($_GET['deleted']) ? '已刪投稿' : '貼文審核' ?></h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
if (count($posts) == 0) {
	if (isset($USER)) {
?>
			<h2 class="ts header">太棒了！您已審完所有投稿</h2>
			<p>歡迎使用 Telegram Bot 接收投稿通知，並在程式內快速審查</p>
<?php } else if (isset($_GET['deleted'])) { ?>
			<div class="ts negative message">
				<div class="header">你不是交大生</div>
				<p>這邊僅限交大使用者瀏覽，外校生僅可在知道投稿編號的情況下看到刪除記錄，例如 <a href="/review/2C8j">#投稿2C8j</a>。</p>
			</div>
<?php } else { ?>
			<h2 class="ts header">太棒了！目前沒有待審投稿</h2>
			<p>歡迎使用 Telegram Bot 接收投稿通知，並在程式內快速審查</p>
<?php } }
foreach ($posts as $post) {
	$uid = $post['uid'];
	$ip_masked = ip_mask($post['ip_addr']);
	$author_name = toHTML($post['author_name']);

	$author_photo = $post['author_photo'] ?? '';
	if (empty($author_photo))
		$author_photo = genPic($ip_masked);

	$body = toHTML($post['body']);
	$ts = strtotime($post['created_at']);
	$time = humanTime($post['created_at']);
	$canVote = !(isset($post['id']) || isset($post['vote']) || isset($post['deleted_at']));

	if (isset($post['deleted_at'])) {
?>
			<div class="ts negative message">
				<div class="header">此文已刪除</div>
				<p>刪除原因：<?= toHTML($post['delete_note']) ?? '(無)' ?></p>
			</div>
<?php }
if (isset($post['id'])) {
?>
			<div class="ts positive message">
				<div class="header">文章已發出</div>
				<p>您可以在 <a href="/post/<?= $post['id'] ?>">#靠交<?= $post['id'] ?></a> 找到這篇文章</p>
			</div>
<?php } ?>
			<div class="ts card" id="post-<?= $uid ?>" style="margin-bottom: 42px;">
<?php if ($post['has_img']) { ?>
				<div class="image">
<?php if (isset($_GET['uid'])) { ?>
					<img class="post-image" src="/img/<?= $uid ?>.jpg" />
<?php } else { ?>
					<img class="post-image" src="/img/<?= $uid ?>.jpg" onclick="showImg(this);" style="max-height: 40vh; width: auto; cursor: zoom-in;" />
<?php } ?>
				</div>
<?php } ?>
				<div class="content">
<?php if (isset($_GET['uid'])) { ?>
					<div class="header">#投稿<?= $uid ?></div>
<?php } else { ?>
					<div class="header"><a href="/review/<?= $uid ?>">#投稿<?= $uid ?></a></div>
<?php } ?>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $author_photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <?= $author_name ?>
<?php if (isset($USER) && empty($post['author_id']) || $post['status'] == 10) { ?>
						<br><span class="right floated">(<?= $ip_masked ?>)</span>
<?php } ?>
					</div>
					<p style="margin-top: 0; line-height: 1.7em">
						<span>審核狀況：<button class="ts vote positive button">通過</button>&nbsp;<span id="approvals"><?= $post['approvals'] ?></span>&nbsp;票 /&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;<span id="rejects"><?= $post['rejects'] ?></span>&nbsp;票</span>
						<br><span>投稿時間：<time data-ts="<?= $ts ?>"><?= $time ?></time></span>
					</p>
				</div>
				<div class="ts fluid bottom attached large buttons" style="<?= $canVote ? '' : 'display: none;' ?>">
					<button class="ts positive button" onclick="approve('<?= $uid ?>');">通過貼文</button>
					<button class="ts negative button" onclick="reject('<?= $uid ?>');">駁回投稿</button>
				</div>
			</div>
<?php }
if (isset($_GET['uid'])) {
	$VOTES = $db->getVotesByUid($post['uid']);
	include('includes/table-vote.php');
	if (isset($USER) && 1 <= $post['status'] && $post['status'] <= 3 || $post['status'] == 10) {
?>
			<button id="refresh" class="ts primary button" onclick="updateVotes('<?= $uid ?>');">重新整理</button>
<?php } } else if (!isset($_GET['deleted']) && isset($USER)) { ?>
			<div class="ts toggle checkbox">
				<input id="showAll" <?= $showAll ? 'checked' : '' ?> type="checkbox" onchange="location.href='?all='+this.checked;">
				<label for="showAll">顯示所有投稿</label>
			</div>
<?php } ?>

			<div class="ts modals dimmer" id="img-container-wrapper" style="margin-top: 40px;">
				<dialog id="modal" class="ts basic fullscreen closable modal" open>
					<i class="close icon"></i>
					<div class="ts icon header"></div>
					<div class="content">
						<img id="img-container-inner" style="width: 100%;">
					</div>
					<div class="actions">
						<button class="ts inverted basic cancel button">關閉</button>
					</div>
				</dialog>
			</div>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
