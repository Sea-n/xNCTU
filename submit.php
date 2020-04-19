<?php
session_start();
require_once('utils.php');
require_once('database.php');
require_once('send-review.php');
$db = new MyDB();

$ip_addr = $_SERVER['REMOTE_ADDR'];
$ip_masked = ip_mask($ip_addr);
$ip_from = ip_from($ip_addr);

if (isset($_SESSION['nctu_id']))
	$USER = $db->getUserByNctu($_SESSION['nctu_id']);

if (!isset($_SESSION['csrf_token']))
	$_SESSION['csrf_token'] = rand58(8);

$captcha_q = "請輸入「交大ㄓㄨˊㄏㄨˊ」（四個字）";
$captcha_a = "";
if (isset($USER) || $ip_from == '交大')
	$captcha_a = "交大竹湖";
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
<?php
$TITLE = '文章投稿';
$IMG = 'https://x.nctu.app/assets/img/og.png';
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
			<div id="rule">
				<h2>投稿規則</h2>
				<ol>
					<li>攻擊性投稿內容不能含有姓名、暱稱等可能洩漏對方身分的資料，請把關鍵字自行碼掉。
						<ol><li>登入後具名投稿者，不受此條文之限制。</li></ol></li>
					<li>含有歧視、人身攻擊、色情內容、不實訊息等文章，將由審核團隊衡量發文尺度。</li>
					<li>如果對文章感到不舒服，請來信審核團隊，如有合理理由將協助刪文。</li>
				</ol>
			</div>

			<div id="submit-section">
				<h2>立即投稿</h2>
<?php if (isset($USER)) { ?>
				<div id="warning-name" class="ts warning message">
					<div class="header">注意：您目前為登入狀態</div>
					<p>一但送出投稿後，所有人都能看到您（<?= toHTML($USER['name']) ?>）具名投稿，如想匿名投稿請於下方勾選「匿名投稿」。</p>
				</div>
<?php } ?>
				<div id="warning-ip" class="ts info message" style="<?= isset($USER) ? 'display: none;' : '' ?>">
					<div class="header">注意</div>
					<p>一但送出投稿後，所有人都能看到您的網路服務商（<?= $ip_from ?>），已登入的交大人能看見您的部分 IP 位址 (<?= $ip_masked ?>) 。</p>
				</div>
				<form id ="submit-post" class="ts form" action="/submit" method="POST" enctype="multipart/form-data">
					<div id="body-field" class="required resizable field">
						<label>貼文內容</label>
						<textarea id="body-area" name="body" rows="6" placeholder="請在這輸入您的投稿內容。"></textarea>
						<span>目前字數：<span id="body-wc">0</span></span>
					</div>
					<div class="inline field">
						<label>附加圖片</label>
						<div class="two wide"><input type="file" id="img" name="img" accept="image/png, image/jpeg" style="display: inline-block;" /></div>
						<div class="ts spaced bordered fluid image" style="display: none !important;">
							<div style="width: 100%; height: 100%; z-index: 1; position: absolute; display: flex; align-items: center; justify-content: center; text-align: center;">
								<p style="transform: rotate(-30deg);text-align: center; font-size: 8vw;opacity: 0.6;">Preview</p>
							</div>
							<img id="img-preview" />
						</div>
					</div>
					<div id="captcha-field" class="required inline field">
						<label>驗證問答</label>
						<div class="two wide"><input id="captcha-input" name="captcha" data-len="4" value="<?= $captcha_a ?>"/></div>
						<span>&nbsp; <?= $captcha_q ?></span>
					</div>
					<div id="field" class="inline field" style="<?= isset($USER) ? '' : 'display: none;' ?>">
						<label for="anon">匿名投稿</label>
						<div class="ts toggle checkbox">
							<input id="anon" type="checkbox" onchange="changeAnon();">
							<label for="anon"></label>
						</div>
					</div>
					<input name="csrf_token" id="csrf_token" type="hidden" value="<?= $_SESSION['csrf_token'] ?>" />
					<input id="submit" type="submit" class="ts disabled button" value="提交貼文" />
					<div id="warning-preview" class="ts info compact segment" style="margin: -8px 0 -6px 12px; display: none;">
						<p>Tips: 只有在網址單獨寫在最後一行時，靠交才會自動顯示頁面預覽</p>
					</div>
				</form>
			</div>

			<div class="ts card" id="preview-section" style="margin-bottom: 42px; display: none;">
				<div class="image">
					<img id="preview-img" class="post-image" />
				</div>
				<div class="content">
					<div class="header">投稿預覽</div>
					<div id="preview-body"></div>
				</div>
				<div class="extra content">
					<div class="right floated author">
						<img id="author-photo" class="ts circular avatar image" onerror="this.src='/assets/img/avatar.jpg';"> <span id="author-name"></span>
					</div>
					<p>發文者 IP 位址：<span id="author-ip">140.113.***.*87</span></p>
				</div>
				<div class="ts fluid bottom attached large buttons">
					<button id="confirm-button" class="ts positive disabled button" onclick="confirmSubmission();">確認投稿 (<span id="countdown">03</span>)</button>
					<button id="delete-button" class="ts negative button" onclick="deleteSubmission();">刪除投稿</button>
				</div>
			</div>
		</div>
<?php include('includes/footer.php'); ?>
	</body>
</html>
