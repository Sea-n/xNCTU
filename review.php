<?php
session_start(['read_and_close' => true]);
require_once('utils.php');
require_once('database.php');
$db = new MyDB();


$USER = $db->getUserByStuid($_SESSION['stuid'] ?? '');
$uid = $_GET['uid'] ?? '';
$deleted = isset($_GET['deleted']);


if ($uid) {
	if (!preg_match('/^[a-zA-Z0-9]{4}$/', $uid)) {
		http_response_code(404);
		exit('Wrong uid format. 投稿編號格式錯誤');
	}

	if (!($post = $db->getPostByUid($uid))) {
		http_response_code(404);
		exit('Post not found. 文章不存在');
	}

	$posts = [$post];
} else if ($deleted) {
	if (isset($USER)) {
		$votes = $USER['approvals'] + $USER['rejects'];
		$count = max($votes/10, 3);
		$posts = $db->getDeletedSubmissions($count);
	} else
		$posts = [];
} else
	$posts = $db->getSubmissions(50);

foreach ($posts as $key => $item)
	if ($deleted)
		$posts[$key]['voted'] = true;
	else if (!isset($USER))
		$posts[$key]['voted'] = false;
	else {
		$canVote = $db->canVote($item['uid'], $USER['stuid']);
		$posts[$key]['voted'] = !$canVote['ok'];
	}

$IMG = "https://$DOMAIN/assets/img/og.png";
if ($uid) {
	$hashtag = "#投稿{$post['uid']}";

	$DESC = $post['body'];
	$TITLE = "$hashtag $DESC";

	if (mb_strlen($TITLE) > 40)
		$TITLE = mb_substr($TITLE, 0, 40) . '...';

	if (mb_strlen($DESC) > 150)
		$DESC = mb_substr($DESC, 0, 150) . '...';

	if ($post['has_img'])
		$IMG = "https://$DOMAIN/img/{$post['uid']}.jpg";
} else if ($deleted)
	$TITLE = '已刪投稿';
else
	$TITLE = '貼文審核';
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php include('includes/head.php'); ?>
		<script src="/assets/js/review.js"></script>
	</head>
<?php if ($uid) { ?>
	<body data-uid="<?= $uid ?>">
<?php } else { ?>
	<body>
<?php }
include('includes/nav.php');
?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header"><?= $deleted ? '已刪投稿' : '貼文審核' ?></h1>
				<div class="description"><?= SITENAME ?></div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php
if ($deleted) {
	if (!isset($USER)) { ?>
		<div class="ts negative message">
			<div class="header">你不是交大生</div>
			<p>這邊僅限交大使用者瀏覽，外校生僅可在知道投稿編號的情況下看到刪除記錄，例如 <a href="/review/2C8j">#投稿2C8j</a>。</p>
		</div>
<?php } else
	foreach ($posts as $post)
		renderPost($post);
} else if (!$uid) {
?>
			<div id="rule">
				<h2>審核規範</h2>
				<ol>
					<li>依照直覺憑良心審文，為自己的投票負責，可以參考過往的&nbsp;<a href="/deleted" target="_blank">已刪投稿</a>。</li>
					<li>符合&nbsp;<a href="https://zh-tw.facebook.com/communitystandards/" target="_blank">Facebook 社群守則</a>，例如禁止不實訊息、暴力、煽動仇恨、性誘惑等。</li>
					<li>遵從台灣法律規定，駁回無根據的誹謗、抹黑、帶風向投稿。</li>
				</ol>
			</div>
<?php
	$header = false;
	foreach ($posts as $post) {
		if ($post['voted'])
			continue;
		if (!$header) {
			echo "<h2>待審貼文</h2>";
			$header = true;
		}
		renderPost($post);
	}

	$header = false;
	foreach ($posts as $post) {
		if (!$post['voted'])
			continue;
		if (!$header) {
			echo "<h2>已審貼文</h2>";
			$header = true;
		}
		renderPost($post);
	}
?>
			<hr>
			<h2 class="ts header">排行榜</h2>
			<p>排名積分會依時間遠近調整權重，正確的駁回 <a href="/deleted">已刪投稿</a> 將大幅提升排名。</p>
			<p>您可以在 <a href="/ranking">這個頁面</a> 查看排行榜。</p>
<?php
} else {
	renderPost($post, true);

	$VOTES = $db->getVotesByUid($post['uid']);
	include('includes/table-vote.php');
	if (isset($USER) && 1 <= $post['status'] && $post['status'] <= 3 || $post['status'] == 10) { ?>
		<button id="refresh" class="ts primary button" onclick="updateVotes('<?= $uid ?>');">重新整理</button>
<?php } } ?>

			<!-- Full-page Image Box -->
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

<?php
function renderPost(array $post, $single = false) {
	global $USER;

	$uid = $post['uid'];
	$author_name = toHTML($post['author_name']);

	if (!empty($post['author_id']))
		$ip_masked = false;
	else if (!isset($USER))
		$ip_masked = ip_mask_anon($post['ip_addr']);
	else if (strpos($author_name, '境外') === false)
		$ip_masked = ip_mask($post['ip_addr']);
	else
		$ip_masked = $post['ip_addr'];

	$author_photo = $post['author_photo'] ?? '';
	if (empty($author_photo))
		$author_photo = genPic($ip_masked);

	$body = toHTML($post['body']);
	$ts = strtotime($post['created_at']);
	$time = humanTime($post['created_at']);

	if (isset($post['deleted_at'])) { ?>
		<div class="ts negative message">
			<div class="header">此文已刪除</div>
			<p>刪除原因：<?= toHTML($post['delete_note'] ?? '(無)') ?></p>
		</div>
<?php } else if (isset($post['id'])) { ?>
		<div class="ts positive message">
			<div class="header">文章已發出</div>
			<p>您可以在 <a href="/post/<?= $post['id'] ?>">#靠交<?= $post['id'] ?></a> 找到這篇文章</p>
		</div>
<?php } ?>

<div class="ts card" id="post-<?= $uid ?>" style="margin-bottom: 42px;">
<?php if ($post['has_img']) { ?>
	<div class="image">
	<?php if ($single) { ?>
		<img class="post-image" src="/img/<?= $uid ?>.jpg" />
	<?php } else { ?>
		<img class="post-image" src="/img/<?= $uid ?>.jpg" onclick="showImg(this);" style="max-height: 40vh; width: auto; cursor: zoom-in;" />
	<?php } ?>
	</div>
<?php } ?>

	<div class="content">
<?php if ($single) { ?>
		<div class="header">#投稿<?= $uid ?></div>
<?php } else { ?>
		<div class="header"><a href="/review/<?= $uid ?>">#投稿<?= $uid ?></a></div>
<?php } ?>

		<p><?= $body ?></p>
	</div>

	<div class="extra content">
		<div class="right floated author">
			<img class="ts circular avatar image" src="<?= $author_photo ?>" onerror="this.src='/assets/img/avatar.jpg';"> <?= $author_name ?>
<?php if ($ip_masked) { ?>
			<br><span class="right floated">(<?= $ip_masked ?>)</span>
<?php } ?>
		</div>

		<p style="margin-top: 0; line-height: 1.7em">
			<span>審核狀況：<button class="ts vote positive button">通過</button>&nbsp;<span id="approvals"><?= $post['approvals'] ?></span>&nbsp;票 /&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;<span id="rejects"><?= $post['rejects'] ?></span>&nbsp;票</span>
			<br><span>投稿時間：<time data-ts="<?= $ts ?>"><?= $time ?></time></span>
		</p>
	</div>
	<div class="ts fluid bottom attached large buttons" style="<?= $post['voted'] ? 'display: none;' : '' ?>">
		<button class="ts positive button" onclick="approve('<?= $uid ?>');">通過貼文</button>
		<button class="ts negative button" onclick="reject('<?= $uid ?>');">駁回投稿</button>
	</div>
</div>
<?php }  // function renderPost($post)
