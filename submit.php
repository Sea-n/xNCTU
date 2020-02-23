<?php
session_start();
require_once('utils.php');
require_once('database.php');
require_once('send-review.php');
$db = new MyDB();

$ip = $_SERVER['REMOTE_ADDR'];

if (isset($_SESSION['nctu_id']))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

if (isset($_POST['body'])) {
	/* Check CSRF Token */
	if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']))
		exit('No CSRF Token. 請重新操作');

	if ($_SESSION['csrf_token'] !== $_POST['csrf_token'])
		exit('Invalid CSRF Token. 請重新操作');

	unset($_SESSION['csrf_token']);  // Prevent reuse

	/* Check CAPTCHA */
	$captcha = trim($_POST['captcha'] ?? 'X');
	if ($captcha != '交大竹湖' && $captcha != '交大竹狐') {
		if (strlen($captcha) > 1 && strlen($captcha) < 20)
			error_log("Captcha failed: $captcha.");
		exit('Are you human? 驗證碼錯誤');
	}

	/* Check Body */
	$body = $_POST['body'];
	$body = str_replace("\r", "", $body);

	if (mb_strlen($body) < 5)
		exit('Body too short. 文章過短');
	if (mb_strlen($body) > 1000)
		exit('Body too long (' . mb_strlen($body) . ' chars). 文章過長');

	/* Generate UID (Collision not handled) */
	$uid = rand58(4);

	/* Upload Image */
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
			$img = $uid;
			$dst = __DIR__ . "/img/$uid";
		} while (file_exists($dst));

		if (!move_uploaded_file($src, $dst))
			exit('Failed to move uploaded file. 上傳發生錯誤');

		$size = getimagesize($dst);
		$width = $size[0];
		$height = $size[1];
		if ($width * $height < 320*320)
			exit('Image must be at least 320x320.');
		if ($width > $height*4)
			exit('Image must be at least 1:4');
		if ($width*2 < $height)
			exit('Image must be at least 2:1');

		system("ffmpeg -i $dst $dst.jpg");
		system("ffmpeg -i $dst $dst.png");
	} else
		$img = '';

	/* Get Author Name */
	if (isset($USER)) {
		$author_name = $USER['name'];
		$author_id = $USER['nctu_id'];
		$author_photo = $USER['tg_photo'] ?? '';
	} else {
		$ip_from = ip_from($ip);
		$author_name = "匿名, $ip_from";
		$author_id = '';
		$author_photo = '';
	}

	/* Insert record */
	$error = $db->insertSubmission($uid, $body, $img, $ip, $author_name, $author_id, $author_photo);
	if ($error[0] != '00000')
		exit("Database error {$error[0]}, {$error[1]}, {$error[2]}. 資料庫發生錯誤");
} else {
	if (!isset($_SESSION['csrf_token']))
		$_SESSION['csrf_token'] = rand58(8);

	$captcha = "請輸入「交大ㄓㄨˊㄏㄨˊ」（四個字）";

	$ip_masked = ip_mask($ip);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '文章投稿';
$IMG = 'https://x.nctu.app/assets/img/logo.png';
include('includes/head.php');
?>
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
<?php if (isset($body)) { ?>
			<h2 class="ts header">投稿成功！</h2>
			<p>您可以在 3 分鐘內反悔，逾時刪除請來信聯絡開發團隊。</p>
			<div class="ts card" id="post-preview" style="margin-bottom: 42px;">
<?php if (isset($img)) { ?>
				<div class="image">
					<img class="post-image" src="https://x.nctu.app/img/<?= $img ?>.png" />
				</div>
<?php } ?>
				<div class="content">
					<div class="header"> <a href="review?uid=<?= $uid ?>">投稿編號 <?= $uid ?></a></div>
					<p><?= toHTML($body) ?></p>
				</div>
				<div class="extra content">
					<div class="right floated author">
						<img class="ts circular avatar image" src="<?= $author_photo ?>"> <?= $author_name ?></img>
					</div>
					<p>發文者 IP 位址：<?= ip_mask($ip) ?></p>
				</div>
				<div class="ts fluid bottom attached large buttons">
					<button id="delete-button" class="ts negative button" onclick="deleteSubmission('<?= $uid ?>');">刪除投稿 (<span id="countdown">3:00</span>)</button>
				</div>
			</div>
<?php } else { ?>
			<h2>投稿規則</h2>
			<ol>
				<li>攻擊性投稿內容不能含有姓名、暱稱等可能洩漏對方身分的資料，請把關鍵字自行碼掉。
					<ol><li>登入後具名投稿者，不受此條文之限制。</li></ol></li>
				<li>含有歧視、人身攻擊、色情內容、不實訊息等文章，將由審核團隊衡量發文尺度。</li>
				<li>如果對文章感到不舒服，請來信審核團隊，如有合理理由將協助刪文。</li>
			</ol>

			<h2>立即投稿</h2>
<?php if (isset($USER['name'])) { ?>
			<div class="ts warning message">
				<div class="header">注意：您目前為登入狀態</div>
				<p>所有人都能看到您（<?= $USER['name'] ?>）具名投稿，如想匿名投稿請先點擊右上角登出後再發文。</p>
			</div>
<?php } ?>
			<form id ="submit-post" class="ts form" action="/submit" method="POST" enctype="multipart/form-data">
				<div id="body-field" class="required resizable field">
					<label>貼文內容</label>
					<textarea id="body-area" name="body" rows="6" placeholder="請在這輸入您的投稿內容。"></textarea>
					<span>字數上限：<span id="body-wc">0</span> / 870</span>
				</div>
				<div class="inline field">
					<label>附加圖片</label>
					<div class="four wide"><input type="file" name="img" accept="image/png, image/jpeg, image/gif" style="display: inline-block;" /></p></div>
				</div>
				<div id="captcha-field" class="required inline field">
					<label>驗證問答</label>
					<div class="two wide"><input id="captcha-input" name="captcha" data-len="4" /></div>
					<span>&nbsp; <?= $captcha ?></span>
				</div>
				<input name="csrf_token" type="hidden" value="<?= $_SESSION['csrf_token'] ?>" />
				<input id="submit" type="submit" class="ts disabled button" value="提交貼文" />
			</form>
			<p><small>請注意：一但送出投稿後，所有人都能看到您的網路服務商（<?= ip_from($ip) ?>），已登入的交大人能看見您的部分 IP 位址 (<?= $ip_masked ?>) 。</small></p>
<?php } ?>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
<?php
fastcgi_finish_request();

if (isset($uid)) {
	sendReview($uid, $body, $img);
}
