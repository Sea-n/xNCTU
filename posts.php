<?php
require('utils.php');
require('database.php');
$db = new MyDB();

if (!check_cf_ip($_SERVER['REMOTE_ADDR'] ?? '1.1.1.1'))
	exit("Please don't hack me.");

if (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
	if (!($post = $db->getPostByUid($uid)))
		exit('Post not found. 文章不存在');
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>貼文預覽 - 靠交 2.0</title>
		<link rel="icon" type="image/png" href="/logo-192.png" sizes="192x192">
		<link rel="icon" type="image/png" href="/logo-128.png" sizes="128x128">
		<link rel="icon" type="image/png" href="/logo-96.png" sizes="96x96">
		<link rel="icon" type="image/png" href="/logo-64.png" sizes="64x64">
		<link rel="icon" type="image/png" href="/logo-32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="/logo-16.png" sizes="16x16">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
		<meta name="keywords" content="NCTU, 靠北交大, 靠交 2.0" />
		<meta name="description" content="在這裡您可以匿名地發送貼文" />
		<meta property="og:title" content="貼文預覽" />
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
		<link rel="stylesheet" href="/style.css">
	</head>
	<body>
		<div>
			<div class="row">
				<div class="col-xs-12 col-sm-offset-1 col-sm-10 col-md-offset-2 col-md-8 col-lg-offset-3 col-lg-6">
					<h1>靠交 2.0</h1>
					<p>給您一個沒有偷懶小編的靠北交大</p>
<?php
if (isset($post)) {
	$author = $post['author'];
	$img = "/img/{$post['img']}";
	$body = toHTML($post['body']);
	$time = $post['created_at'];
?>
					<h2>貼文預覽</h2>
					<p>文章臨時代碼：<code><?= $post['uid'] ?></code></p>
					<p>提醒您，為自己的貼文按「通過」會留下公開紀錄哦</p>
					<div class="ts card">
<?php if (!empty($post['img'])) { ?>
						<div class="image">
							<img src="<?= $img ?>" />
						</div>
<?php } ?>
						<div class="content">
							<div class="header">投稿內容</div>
							<p><?= $body ?></p>
						</div>
						<div class="extra content">
							<div class="right floated author">
								<img class="ts circular avatar image" src="https://c.disquscdn.com/uploads/users/20967/622/avatar128.jpg"> <?= $author ?></img>
							</div>
							<span>投稿時間：<?= $time ?></span>
						</div>
						<div class="ts fluid bottom attached large buttons">
							<button class="ts positive button">通過貼文</button>
							<button class="ts negative button">駁回投稿</button>
						</div>
					</div>
<?php } else { ?>
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
