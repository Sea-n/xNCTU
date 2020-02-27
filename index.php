<?php session_start(); ?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '首頁';
$IMG = 'https://x.nctu.app/assets/img/logo.png';
include('includes/head.php');
?>
		<script src="/assets/js/index.js"></script>
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
			<h2 class="ts header">社群平台</h2>
			<div class="icon-row">
				<a class="ts link tiny circular image" target="_blank" href="https://t.me/xNCTU"            ><img src="https://image.flaticon.com/icons/svg/2111/2111646.svg" alt="Telegram"></a>
				<a class="ts link tiny circular image" target="_blank" href="https://twitter.com/x_NCTU"    ><img src="https://image.flaticon.com/icons/svg/124/124021.svg"   alt="Twitter" ></a>
				<a class="ts link tiny circular image" target="_blank" href="https://www.plurk.com/xNCTU"   ><img src="https://image.flaticon.com/icons/svg/124/124026.svg"   alt="Plurk"   ></a>
				<a class="ts link tiny circular image" target="_blank" href="https://www.facebook.com/xNCTU"><img src="https://image.flaticon.com/icons/svg/220/220200.svg"   alt="Facebook"></a>
			</div>

			<h2 class="ts header">審文機制</h2>
			<details class="ts accordion">
				<summary>
					<p><i class="dropdown icon"></i>
					新版靠北交大 2.0 採自助式審文，所有交大師生皆可加入審核者的行列，以下是系統判斷標準（點擊展開）</p>
				</summary>
				<div class="content">
					<h4>(A) 具名投稿</h4>
					<p>如在 10 分鐘內無 <button class="ts vote negative button">駁回</button>，免審核即自動發出，詳細判斷條件如下：</p>
					<ul>
						<li>等待審核至少 10 分鐘</li>
						<li><button class="ts vote positive button">通過</button>&nbsp;不少於&nbsp;<button class="ts vote negative button">駁回</button></li>
					</ul>
					<h4>(B) 交大 IP 位址</h4>
					<p>使用 113 位址投稿者，滿足以下三個條件即發出</p>
					<ul>
						<li>等待審核至少 10 分鐘</li>
						<li><button class="ts vote positive button">通過</button>&nbsp;不少於&nbsp;<button class="ts vote negative button">駁回</button></li>
						<li>拿到足夠的&nbsp;<button class="ts vote positive button">通過</button>&nbsp;數 (扣除&nbsp;<button class="ts vote negative button">駁回</button>)<ul>
							<li>10 分鐘至 2 小時：達到 2 個&nbsp;<button class="ts vote positive button">通過</button></li>
							<li>2 至 6 小時：達到 1 個&nbsp;<button class="ts vote positive button">通過</button></li>
						</ul></li>
					</ul>
					<p>經過 6 小時以後，只要 <button class="ts vote negative button">駁回</button> 不多於 <button class="ts vote positive button">通過</button> 即自動發出，意即無人審核也會發出，有效避免因為審核團隊偷懶而造成投稿卡住的情形</p>
					<h4>(C) 使用台灣 IP 位址</h4>
					<p>熱門投稿會快速登上版面，審核者們也有足夠時間找出惡意投稿，滿足以下三個條件即發出</p>
					<ul>
						<li>等待審核至少 10 分鐘</li>
						<li><button class="ts vote positive button">通過</button>&nbsp;多於&nbsp;<button class="ts vote negative button">駁回</button></li>
						<li>拿到足夠的&nbsp;<button class="ts vote positive button">通過</button>&nbsp;數 (扣除&nbsp;<button class="ts vote negative button">駁回</button>)<ul>
							<li>10 分鐘至 1 小時：達到 5 個&nbsp;<button class="ts vote positive button">通過</button></li>
							<li>1 至 6 小時：達到 3 個&nbsp;<button class="ts vote positive button">通過</button></li>
							<li>6 至 24 小時：達到 1 個&nbsp;<button class="ts vote positive button">通過</button></li>
						</ul></li>
					</ul>
					<p>經過 24 小時以後，只要 <button class="ts vote negative button">駁回</button> 不多於 <button class="ts vote positive button">通過</button> 即自動發出</p>
					<h4>(D) 境外 IP 位址</h4>
					<p>使用境外 IP 發文除了自動化的廣告機器人外，很可能是心虛怕用台灣 IP 位址做壞事會被抓到，因此除非通過群眾嚴厲的審核，否則一概不發出</p>
					<ul>
						<li>等待審核至少 60 分鐘</li>
						<li><button class="ts vote positive button">通過</button>&nbsp;比&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;多 10 個</li>
					</ul>

					<h3>排程發文</h3>
					<p>通過審核之文章將會進入發文佇列，在每天中午至晚上（10:00 - 23:50），由系統每 10 分鐘 po 出一篇至各大社群平台，如欲搶先看也可申請加入審核團隊</p>
				</div>
			</details>

			<div class="ts horizontal divider">現在開始</div>
			<div class="ts fluid stackable buttons"><a class="ts massive positive button" href="/submit">我要投稿</a><a class="ts massive info button" href="/review">我想審核</a></div>

			<h2 class="ts header">使用 Telegram 快速審核</h2>
			<p>您只要登入 NCTU 帳號，點擊下面按鈕即可綁定 Telegram 帳號，讓您收到最即時的投稿通知，並快速通過/駁回貼文。</p>
<?php if (!isset($USER) || !isset($USER['tg_id'])) { ?>
			<script async src="https://telegram.org/js/telegram-widget.js?7" data-telegram-login="xNCTUbot" data-size="large" data-auth-url="https://x.nctu.app/login-tg" data-request-access="write"></script>
<?php } else { ?>
<div class="ts positive message">
	<div class="header">您已連結成功！</div>
	<p>Tips: 使用 /name 指令即可修改您的暱稱</p>
</div>
<?php } ?>

			<h2 class="ts header">排行榜</h2>
			<p>為鼓勵用心審文，避免全部通過/全部駁回，排名計算公式為：總投票數 + min(少數票, 多數票/2) * 3<br>
			意即「&nbsp;<button class="ts vote positive button">通過</button>&nbsp;20 票」與「&nbsp;<button class="ts vote positive button">通過</button>&nbsp;8 票 +&nbsp;<button class="ts vote negative button">駁回</button>&nbsp;3 票」的排名相同</p>
			<p>您可以在 <a href="/ranking">這個頁面</a> 查看前 15 名</p>

			<h2 class="ts header">服務聲明</h2>
			<p>感謝您使用「靠北交大 2.0」（以下簡稱本網站），本網站之所有文章皆為不特定使用者自行投稿、不特定師生進行審核，並不代表本網站立場。</p>
			<p>如有侵害您權益之貼文，麻煩寄信至服務團隊，將在最短時間協助您撤下貼文或進行澄清。</p>
			<p>投稿者如散播不實訊息而遭司法單位追究，在司法機關提供調取票等充分條件下，本網站將依法提供 IP 位址配合偵辦，切勿以身試法。</p>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
