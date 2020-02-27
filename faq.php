<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '常見問答';
$IMG = 'https://x.nctu.app/assets/img/logo.png';
include('includes/head.php');
?>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">常見問答</h1>
				<div class="description">靠北交大 2.0</div>
			</div>
		</header>
		<div class="ts container" name="main">
			<p>下面列出了幾個關於此服務的問題，如有疏漏可聯絡開發團隊，將儘快答覆您。</p>

			<h2 class="ts header" id="modify-name">Q：如何更改暱稱</h2>
			<p>目前此功能僅實作於 Telegram bot 中，請至首頁下方連結 Telegram 帳號。</p>
			<p>於 Telegram 使用 /name 指令即可更改您的暱稱，所有過往的投稿、投票也會一起修正。</p>

			<h2 class="ts header" id="facebook-sucks">Q：打不開 Facebook 貼文</h2>
			<p>由於 Facebook 政策，發佈 App 之前必須先 <a target="_blank" href="/post/4">審查身分證件</a>，已於 2/19 送件，等到審查通過後就能看得到了。</p>

			<h2 class="ts header" id="length-limit">Q：字數上限是多少</h2>
			<p>純文字投稿的字數上限為 3,600 字、附圖投稿為 870 字。遊走字數上限發文時請注意，最好在發出前自行備份，避免因伺服器判斷誤差造成投稿失敗。</p>

			<h2 class="ts header" id="deleted-submissions">Q：可以去哪找到被黑箱的投稿</h2>
			<p>如果管理團隊覺得投稿不適合發出，或是放置過久、累積足夠的駁回，就會放到 <a href="/deleted">已刪投稿</a> 頁面。</p>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
