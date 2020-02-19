<?php
session_start();
require_once('utils.php');
require_once('database.php');
$db = new MyDB();

if (!check_cf_ip($_SERVER['REMOTE_ADDR'] ?? '1.1.1.1'))
	exit("Please don't hack me.");

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];

if (isset($_POST['body'])) {
	$captcha = trim($_POST['captcha'] ?? 'X');
	if ($captcha != '交大竹湖')
		exit('Are you human? 驗證碼錯誤');

	$body = $_POST['body'];
	if (mb_strlen($body) < 5)
		exit('Body too short. 文章過短');
	if (mb_strlen($body) > 1000)
		exit('Body too long (' . mb_strlen($body) . ' chars). 文章過長');

	if (isset($_FILES['img']) && $_FILES['img']['size']) {
		$src = $_FILES['img']['tmp_name'];
		if (!file_exists($src) || !is_uploaded_file($src))
			exit('Uploaded file not found. 上傳發生錯誤');

		if ($_FILES['img']['size'] > 5*1000*1000)
			exit('Image too large. 圖片過大');

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if (!($ext = array_search($finfo->file($src), [
				'jpg' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			], true)))
			exit('Extension not recognized. 圖片副檔名錯誤');

		do {
			$rand = rand58(4);
			$img = "$rand.$ext";
			$dst = __DIR__ . "/img/$img";
		} while (file_exists($dst));

		if (!move_uploaded_file($src, $dst))
			exit('Failed to move uploaded file. 上傳發生錯誤');
	} else
		$img = '';

	$uid = rand58(4);

	if (isset($_SESSION['name'])) {
		$author_name = $_SESSION['name'];
		$author_id = $_SESSION['nctu_id'];
		$author_photo = $_SESSION['photo'] ?? '';
	} else {
		$ip_from = ip_from($ip);
		$author_name = "匿名, $ip_from";
		$author_id = '';
		$author_photo = '';
	}

	$error = $db->insertSubmission($uid, $body, $img, $ip, $author_name, $author_id, $author_photo);
	if ($error[0] != '00000')
		exit("Database error {$error[0]}, {$error[1]}, {$error[2]}. 資料庫發生錯誤");
} else {
	$captcha = "請輸入「交大ㄓㄨˊㄏㄨˊ」（四個字）";

	$ip_masked = ip_mask($ip);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php $TITLE = '文章投稿'; include('includes/head.php'); ?>
		<script src="/assets/js/submit.js"></script>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">文章投稿</h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
<?php if (isset($_POST['body'])) { ?>
			<h2 class="ts header">投稿成功！</h2>
			<p>文章臨時代碼：<code><?= $uid ?></code>，您可以於 <a href="/review?uid=<?= $uid ?>">這裡</a> 查看審核動態</p>
			<p>但提醒您，為自己的貼文按「通過」或「駁回」均會留下公開紀錄</p>
<?php } else { ?>
			<h2>投稿規則</h2>
			<ol>
				<li>攻擊性投稿內容不能含有姓名、暱稱等各種明顯洩漏對方身分的個人資料，請把關鍵字自行碼掉。
					<ul><li>登入後具名投稿者，不受此條文之限制。</li></ul></li>
				<li>含有性別歧視、種族歧視、人身攻擊、色情內容、不實訊息等文章，將由審核團隊衡量發文尺度。</li>
				<li>如果對文章感到不舒服，請有禮貌的來信審核團隊，如有合理理由將協助刪文。</li>
			</ol>

			<h2>立即投稿</h2>
<?php if (isset($_SESSION['name'])) { ?>
			<div class="ts warning message">
				<div class="header">注意：您目前為登入狀態</div>
				<p>所有人都能看到您（<?= $_SESSION['name'] ?>）具名投稿，如想匿名投稿請先點擊右上角登出後再發文。</p>
			</div>
<?php } ?>
			<form class="ts form" action="/submit" method="POST" enctype="multipart/form-data">
				<div id="body-field" class="required field">
					<label>貼文內容</label>
					<textarea id="body-area" name="body" rows="6" placeholder="請在這輸入您的投稿內容。" style="width: 100%;"></textarea>
					<span>字數上限：<span id="body-wc">0</span> / 870</span>
				</div>
				<div class="inline field">
					<label>附加圖片</label>
					<div class="four wide"><input type="file" name="img" accept="image/*" style="display: inline-block;" /></p></div>
				</div>
				<div id="captcha-field" class="required inline field">
					<label>驗證問答</label>
					<div class="two wide"><input id="captcha-input" name="captcha" data-len="4" /></div>
					<span>&nbsp; <?= $captcha ?></span>
				</div>
				<input id="submit" type="submit" class="ts disabled button" value="提交貼文" />
				<p><small>請注意：一但送出投稿後，所有已登入的交大人都能看見您使用的網路服務商（<?= ip_from($ip) ?>）及部分 IP 位址 (<?= $ip_masked ?>) 。詳請見 <a href="/policies">隱私權政策</a>。</small></p>
			</form>
<?php } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
