<?php
session_start();
require_once('database.php');

if (isset($_SESSION['stuid'])) {
	header('Location: /');
	exit;
}

if (!isset($_SESSION['google_sub'])) {
	header('Location: /login-google');
	exit;
}

$TITLE = '驗證交大身份';
$IMG = "https://$DOMAIN/assets/img/og.png";
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php include('includes/head.php'); ?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header"><?= $TITLE ?></h1>
				<div class="description"><?= SITENAME ?></div>
			</div>
		</header>
		<div class="ts container" name="main">
			<h2 class="ts header">請先綁定 NCTU OAuth 帳號</h2>
			<p><a href="/login-nctu">點我登入 NCTU OAuth</a></p>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
