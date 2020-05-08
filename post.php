<?php
session_start(['read_and_close' => true]);
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

/* NginX rewrite rule:  /post/87 -> /post?id=87 */
if (!isset($_GET['id'])) {
	header('Location: /posts');
	exit('ID not found. 請輸入文章編號');
}

$id = $_GET['id'];

if (!preg_match('/^\d+$/', $id)) {
	http_response_code(404);
	exit('Wrong post id format. 文章編號格式錯誤');
}

$post = $db->getPostById($id);
if (!$post) {
	http_response_code(404);
	exit('Post not found. 文章不存在');
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$hashtag = "#靠交{$id}";

$DESC = $post['body'];
$TITLE = "$hashtag $DESC";

if (mb_strlen($TITLE) > 40)
	$TITLE = mb_substr($TITLE, 0, 40) . '...';

if (mb_strlen($DESC) > 150)
	$DESC = mb_substr($DESC, 0, 150) . '...';

if ($post['has_img'])
	$IMG = "https://$DOMAIN/img/{$post['uid']}.jpg";

include('includes/head.php');
?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header"><?= SITENAME ?></h1>
				<div class="description">不要問為何沒有人審文，先承認你就是沒有人。</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
$id = $post['id'];
$body = toHTML($post['body']);
$timeS = humanTime($post['created_at']);
$timeP = humanTime($post['posted_at']);
$tsS = strtotime($post['created_at']);

$author_name = toHTML($post['author_name']);
$ip_masked = ip_mask($post['ip_addr']);
if (strpos($author_name, '境外') !== false)
	$ip_masked = $post['ip_addr'];

if (!empty($post['author_id'])) {
	$author = $db->getUserByStuid($post['author_id']);
	$author_name = toHTML($author['name']);
	if (!empty($author['tg_photo'] ?? ''))
		$author_photo = "/img/tg/{$author['tg_id']}-x64.jpg";
	else
		$author_photo = genPic($post['author_id']);
} else
	$author_photo = genPic($ip_masked);


$plurk = base_convert($post['plurk_id'], 10, 36);

$VOTES = $db->getVotesByUid($post['uid']);

if (isset($post['deleted_at'])) {
?>
			<div class="ts negative message">
				<div class="header">此文已刪除</div>
				<p>刪除原因：<?= $post['delete_note'] ?? '(無)' ?></p>
			</div>
<?php } ?>
			<article itemscope itemtype="http://schema.org/Article" class="ts card" style="margin-bottom: 42px;">
<?php if (isset($IMG)) { ?>
				<div class="image">
					<img itemprop="image" class="post-image" src="<?= $IMG ?>" />
				</div>
<?php } else { ?>
<meta itemprop="image" content="/assets/img/logo.png">
<?php } ?>
				<div class="content">
					<div itemprop="headline" class="header">#靠交<?= $id ?></div>
					<p itemprop="articleBody"><?= $body ?></p>
				</div>
				<div class="extra content">
<?php if ($post['telegram_id'] > 10) { ?>
					<p><span><i class="telegram icon"></i> Telegram: <a target="_blank" href="https://t.me/s/xNCTU/<?= $post['telegram_id'] ?>">@xNCTU/<?= $post['telegram_id'] ?></a></span><br>
<?php }
if ($post['facebook_id'] > 10) { ?>
					<span><i class="facebook icon"></i> Facebook: <a target="_blank" href="https://www.facebook.com/xNCTU/posts/<?= $post['facebook_id'] ?>">@xNCTU/<?= $post['facebook_id'] ?></a></span><br>
<?php }
if (strlen($post['instagram_id']) > 1) { ?>
					<span><i class="instagram icon"></i> Instagram: <a target="_blank" href="https://www.instagram.com/p/<?= $post['instagram_id'] ?>">@x_nctu/<?= $post['instagram_id'] ?></a></span><br>
<?php }
if (strlen($plurk) > 1) { ?>
					<span><i class="talk icon"></i> Plurk: <a target="_blank" href="https://www.plurk.com/p/<?= $plurk ?>">@xNCTU/<?= $plurk ?></a></span><br>
<?php }
if ($post['twitter_id'] > 10) { ?>
					<span><i class="twitter icon"></i> Twitter: <a target="_blank" href="https://twitter.com/x_NCTU/status/<?= $post['twitter_id'] ?>">@x_NCTU/<?= $post['twitter_id'] ?></a></span></p>
<?php } ?>

					<div itemprop="author" itemscope itemtype="http://schema.org/Person" class="right floated author">
						<img itemprop="image" class="ts circular avatar image" src="<?= $author_photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <span itemprop="name"><?= $author_name ?></span>
<?php if (isset($USER) && empty($post['author_id'])) { ?>
						<br>
						<span class="right floated">(<?= $ip_masked ?>)</span>
<?php } ?>
					</div>
					<p>
						<span>審核結果：<button class="ts vote positive button">通過</button>&nbsp;<?= $post['approvals'] ?>&nbsp;票 /&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;<?= $post['rejects'] ?>&nbsp;票</span><br>
						<span>投稿時間：<time itemprop="dateCreated" datetime="<?= $post['created_at'] ?>" data-ts="<?= $tsS ?>"><?= $timeS ?></time></span>
						<span style="display: none;"><br>發出時間：<time itemprop="datePublished" datetime="<?= $post['posted_at'] ?>"><?= $timeP ?></time></span>
						<span style="display: none;"><br>更新時間：<time itemprop="dateModified" datetime="<?= $post['posted_at'] ?>"><?= $timeP ?></time></span>
					</p>
					<div itemprop="publisher" itemscope itemtype="http://schema.org/Organization" style="display: none;">
						<div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">
							<meta itemprop="url" content="/assets/img/logo.png">
						</div>
						<span itemprop="name"><?= SITENAME ?></span>
					</div>
					<link itemprop="mainEntityOfPage" href="<?= $URL ?>" />
				</div>
			</article>
<?php
include('includes/table-vote.php');
?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
