<?php
require('utils.php');
require('database.php');
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
	if (mb_strlen($body) > 1024)
		exit('Body too long. 文章過長');

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
	$ip_from = ip_from($ip);
	$author = "匿名, $ip_from";

	$error = $db->insertSubmission($uid, $body, $img, $ip, $author);
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
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>文章投稿 - 靠交 2.0</title>
		<link rel="icon" type="image/png" href="/assets/img/logo-192.png" sizes="192x192">
		<link rel="icon" type="image/png" href="/assets/img/logo-128.png" sizes="128x128">
		<link rel="icon" type="image/png" href="/assets/img/logo-96.png" sizes="96x96">
		<link rel="icon" type="image/png" href="/assets/img/logo-64.png" sizes="64x64">
		<link rel="icon" type="image/png" href="/assets/img/logo-32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="/assets/img/logo-16.png" sizes="16x16">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
		<meta name="keywords" content="NCTU, 靠北交大, 靠交 2.0" />
		<meta name="description" content="在這裡您可以匿名地發送貼文" />
		<meta property="og:title" content="文章投稿" />
		<meta property="og:url" content="https://x.nctu.app/submit" />
		<meta property="og:image" content="https://x.nctu.app/logo.png" />
		<meta property="og:image:secure_url" content="https://x.nctu.app/logo.png" />
		<meta property="og:image:type" content="image/png" />
		<meta property="og:image:width" content="640" />
		<meta property="og:image:height" content="640" />
		<meta property="og:type" content="website" />
		<meta property="og:description" content="在這裡您可以匿名地發送貼文" />
		<meta property="og:site_name" content="靠交 2.0" />
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
		<link href="https://www.sean.taipei/assets/css/tocas-ui/tocas.css" rel="stylesheet">
		<link rel="stylesheet" href="/assets/css/style.css">
	</head>
	<body>
		<div>
			<div class="row">
				<div class="col-xs-12 col-sm-offset-1 col-sm-10 col-md-offset-2 col-md-8 col-lg-offset-3 col-lg-6">
					<h1>靠交 2.0</h1>
					<p>給您一個沒有偷懶小編的靠北交大</p>
<?php if (isset($_POST['body'])) { ?>
					<h2>投稿成功！</h2>
					<p>文章臨時代碼：<code><?= $uid ?></code></p>
					<p>您可以於 <a href="/review?uid=<?= $uid ?>">這裡</a> 查看審核動態，但提醒您為自己的貼文按「通過」會留下公開紀錄</p>
<?php } else { ?>
					<h2>發文規則</h2>
					<ol>
						<li>攻擊性投稿內容不能含有姓名、暱稱等各種明顯洩漏對方身分的個人資料，請把關鍵字自行碼掉，例如王 XX、王學長。
							<ul><li>登入後具名投稿者，不受此條文之限制。</li></ul></li>
						<li>含有性別歧視、種族歧視、人身攻擊、色情內容、不實訊息等文章，將由審核團隊衡量發文尺度。</li>
						<li>如果對文章感到不舒服、或是怕被發現是自己發的文想要刪文，請有禮貌的私訊審核團隊，並有合理的理由說服審核者，才會予以刪文。</li>
					</ol>

					<h2>文章投稿</h2>
					<form class="ts form" action="/submit" method="POST" enctype="multipart/form-data">
						<div class="required field">
							<label>貼文內容</label>
							<textarea id="body" name="body" rows="6" maxlength="1024" placeholder="請在這輸入您的投稿內容。" style="width: 100%;"></textarea>
							<span>字數上限：<span id="wc">0</span> / 1,024</span>
						</div>
						<div class="inline field">
							<label>附加圖片</label>
							<div class="four wide"><input type="file" name="img" accept="image/*" style="display: inline-block;" /></p></div>
						</div>
						<div class="required inline field">
							<label>驗證問答</label>
							<div class="two wide"><input id="captcha" name="captcha" /></div>
							<span>&nbsp; <?= $captcha ?></span>
						</div>
						<input type="submit" class="ts button" value="提交貼文" />
						<p>請注意：您使用的網路服務商（<?= ip_from($ip) ?>）及部分 IP 位址 (<?= $ip_masked ?>) 將會永久保留於系統後台，所有已登入的審核者均可見。</p>
						<input type="hidden" name="ip" value="<?= $ip_masked ?>">
					</form>
<?php } ?>
					<p></p>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<center><p>&copy; 2020 <a target="_blank" href="https://www.sean.taipei/">Sean</a></p></center>
		</footer>
	</body>
</html>
