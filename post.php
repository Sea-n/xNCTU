<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if (!isset($_GET['id'])) {
	header('Location: /posts');
	exit('ID not found. 請輸入文章編號');
}

if (!($post = $db->getPostById($_GET['id']))) {
	http_response_code(404);
	exit('Post not found. 文章不存在');
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$hashtag = "#靠交{$post['id']}";

$DESC = $post['body'];
$TITLE = "$hashtag $DESC";

if (mb_strlen($TITLE) > 40)
	$TITLE = mb_substr($TITLE, 0, 40) . '...';

if (mb_strlen($DESC) > 150)
	$DESC = mb_substr($DESC, 0, 150) . '...';

if ($post['img'])
	$IMG = "https://x.nctu.app/img/{$post['img']}.jpg";

include('includes/head.php');
?>
		<script src="/assets/js/review.js"></script>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">靠北交大 2.0</h1>
				<div class="description">不要問為何沒有人審文，先承認你就是沒有人。</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
$id = $post['id'];
$body = toHTML($post['body']);
$time = humanTime($post['submitted_at']);

$author_name = toHTML($post['author_name']);
if (!empty($post['author_id'])) {
	$author = $db->getUserByNctu($post['author_id']);
	$author_name = toHTML($author['name']);
}

$plurk = base_convert($post['plurk_id'], 10, 36);

$VOTES = $db->getVotersBySubmission($post['uid']);
$vote_count = [1=>0, -1=>0];
foreach ($VOTES as $item)
	++$vote_count[ $item['vote'] ];

if (isset($post['deleted_at'])) {
?>
			<div class="ts negative message">
				<div class="header">此文已刪除</div>
				<p>刪除原因：<?= $post['delete_note'] ?? '(無)' ?></p>
			</div>
<?php } ?>
			<div class="ts card" style="margin-bottom: 42px;">
<?php if (isset($IMG)) { ?>
				<div class="image">
					<img class="post-image" src="<?= $IMG ?>" />
				</div>
<?php } ?>
				<div class="content">
					<div class="header">#靠交<?= $id ?></div>
					<p><?= $body ?></p>
				</div>
				<div class="extra content">
					<p><span><i class="telegram icon"></i> Telegram: <a target="_blank" href="https://t.me/s/xNCTU/<?= $post['telegram_id'] ?>">@xNCTU/<?= $post['telegram_id'] ?></a></span><br>
					<span><i class="facebook icon"></i> Facebook: <a target="_blank" href="https://www.facebook.com/xNCTU/posts/<?= $post['facebook_id'] ?>">@xNCTU/<?= $post['facebook_id'] ?></a></span><br>
<?php if (strlen($plurk) > 1) { ?>
					<span><i class="talk icon"></i> Plurk: <a target="_blank" href="https://www.plurk.com/p/<?= $plurk ?>">@xNCTU/<?= $plurk ?></a></span><br>
<?php } ?>
					<span><i class="twitter icon"></i> Twitter: <a target="_blank" href="https://twitter.com/x_NCTU/status/<?= $post['twitter_id'] ?>">@x_NCTU/<?= $post['twitter_id'] ?></a></span></p>
					<p>審核結果：通過 <?= $vote_count[1] ?> 票、駁回 <?= $vote_count[-1] ?> 票</p>
<?php if (isset($USER) && empty($post['author_id'])) { ?>
					<p>發文者 IP 位址：<?= ip_mask($post['ip']) ?></p>
<?php }

$photo = $author['tg_photo'] ?? '';
?>
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <?= $author_name ?>
					</div>
					<span>投稿時間：<?= $time ?></span>
				</div>
			</div>
<?php
if (isset($USER)) {
	include('includes/table-vote.php');
}
?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
