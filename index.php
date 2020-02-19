<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php $TITLE = '首頁'; include('includes/head.php'); ?>
		<script src="/assets/js/index.js"></script>
	</head>
	<body>
<?php include('includes/nav.php'); ?>
		<header class="ts fluid vertically padded heading slate">
			<div class="ts narrow container">
				<h1 class="ts header">靠北交大 2.0</h1>
				<div class="description">別再說沒有人審文，先承認你就是沒有人。</div>
			</div>
		</header>
		<div class="ts container" name="main">
			<h2 class="ts header">社群平台</h2>
			<ul>
				<li><a target="_blank" href="https://t.me/xNCTU"><i class="telegram icon"></i> Telegram</a></li>
				<li><a target="_blank" href="https://twitter.com/x_NCTU"><i class="twitter icon"></i> Twitter</a></li>
				<li><a target="_blank" href="https://www.facebook.com/xNCTU"><i class="facebook square icon"></i> Facebook</a></li>
				<li><a target="_blank" href="https://www.plurk.com/xNCTU"><i class="talk icon"></i> Plurk</a></li>
			</ul>

			<h2 class="ts header">審文機制</h2>
			<details class="ts accordion">
				<summary>
					<p><i class="dropdown icon"></i>
					新版靠北交大 2.0 採自助式審文，所有交大師生皆可加入審核者的行列，以下是系統判斷標準（點擊展開）</p>
				</summary>
				<div class="content">
					<h4>(A) 具名投稿</h4>
					<p>如在 5 分鐘內無「駁回」，免審核即自動發出，詳細判斷條件如下：</p>
					<ul>
						<li>等待審核至少 5 分鐘</li>
						<li>「通過」不少於「駁回」</li>
					</ul>
					<h4>(B) 交大 IP 位址</h4>
					<p>使用 113 位址投稿者，滿足以下三個條件即發出</p>
					<ul>
						<li>等待審核至少 10 分鐘</li>
						<li>「通過」不少於「駁回」</li>
						<li>拿到足夠的「通過」數<ul>
							<li>10 分鐘至 2 小時：達到 2 個「通過」</li>
							<li>2 至 6 小時：達到 1 個「通過」</li>
						</ul></li>
					</ul>
					<p>經過 6 小時以後，只要「駁回」不多於「通過」即自動發出，意即無人審核也會發出，有效避免因為審核團隊偷懶而造成投稿卡住的情形</p>
					<h4>(C) 使用台灣 IP 位址</h4>
					<p>熱門投稿會快速登上版面，審核者們也有足夠時間找出惡意投稿，滿足以下三個條件即發出</p>
					<ul>
						<li>等待審核至少 30 分鐘</li>
						<li>「通過」多於「駁回」</li>
						<li>拿到足夠的「通過」數<ul>
							<li>30 分鐘至 2 小時：達到 5 個「通過」</li>
							<li>2 至 6 小時：達到 3 個「通過」</li>
							<li>6 至 24 小時：達到 1 個「通過」</li>
						</ul></li>
					</ul>
					<p>經過 24 小時以後，只要「駁回」不多於「通過」即自動發出</p>
					<h4>(D) 境外 IP 位址</h4>
					<p>使用境外 IP 發文除了自動化的廣告機器人外，很可能是心虛怕用台灣 IP 位址做壞事會被抓到，因此除非通過群眾嚴厲的審核，否則一概不發出</p>
					<ul>
						<li>等待審核至少 60 分鐘</li>
						<li>「通過」比「駁回」多兩倍</li>
						<li>達到 10 個「通過」</li>
					</ul>

					<h3>排程發文</h3>
					<p>通過審核之文章將會進入發文佇列，在每天中午至晚上（09:00 - 24:00），由系統每 10 分鐘 po 出一篇至各大社群平台，如欲搶先看也可申請加入審核團隊</p>
				</div>
			</details>

			<div class="ts horizontal divider">現在開始</div>
			<div class="ts fluid stackable buttons"><a class="ts massive positive button" href="/submit">我要投稿</a><a class="ts massive info button" href="/review">我想審核</a></div>

			<h2 class="ts header">使用 Telegram 快速審核</h2>
			<p>您只要登入 NCTU 帳號，點擊下面按鈕即可綁定 Telegram 帳號，讓您收到最即時的投稿通知，並快速通過/駁回貼文。</p>
			<script async src="https://telegram.org/js/telegram-widget.js?7" data-telegram-login="xNCTUbot" data-size="large" data-auth-url="https://x.nctu.app/login-tg" data-request-access="write"></script>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
