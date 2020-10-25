<?php
$items = [
	'/' => '首頁',
	'/submit' => '投稿',
	'/review' => '審核',
	'/posts' => '文章',
];
?>
<nav class="ts basic fluid borderless menu horizontally scrollable">
	<div class="ts container">
<?php
foreach ($items as $href => $name) {
	$uri = $_SERVER['REQUEST_URI'];
	$uri = explode('?', $uri)[0];
	if ($uri == $href)
		$class = 'active item';
	else
		$class = 'item';

	echo "<a class='$class' href='$href'>$name</a>\n";
}
?>
		<div class="right fitted item" id="nav-right">
<?php
if (isset($USER)) {
	if (!empty($USER['tg_photo']))
		$photo = "/img/tg/{$USER['tg_id']}-x64.jpg";
	else
		$photo = genPic($USER['stuid']);
?>
			<img class="ts circular related avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';">
			&nbsp;<b id="nav-name" style="overflow: hidden;"><?= toHTML($USER['name']) ?></b>&nbsp;
			<a class="item" href="/logout" data-type="logout" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">
				<i class="log out icon"></i>
				<span class="tablet or large device only">Logout</span>
			</a>
<?php } else if (isset($GOOGLE)) {
	if (!empty($GOOGLE['picture']))
		$photo = $GOOGLE['picture'];
	else
		$photo = genPic($GOOGLE['sub']);
?>
			<img class="ts circular related avatar image" src="<?= $photo ?>" onerror="this.src='/assets/img/avatar.jpg';">
			&nbsp;<b id="nav-name" style="overflow: hidden;">Guest</b>&nbsp;
			<a class="item" href="/verify" data-type="login">Verify</a>
<?php } else { ?>
			<a class="item" href="/login-nctu" data-type="login" onclick="document.getElementById('login-wrapper').style.display = ''; return false;">Login</a>
<?php } ?>
		</div>
	</div>
</nav>

<div class="login-wrapper" id="login-wrapper" style="display: none;">
	<div class="login-background" onclick="this.parentNode.style.display = 'none';"></div>
	<div class="login-inner">
		<dialog class="ts fullscreen modal" open>
			<div class="header">
				靠北交大 2.0 登入
			</div>
			<div class="content">
				<div style="display: inline-flex; width: 100%; justify-content: space-around;">
					<a href="/login-nctu" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">
						<img class="logo" src="/assets/img/login-nctu.png">
					</a>
					<a href="/login-google" onclick="this.href+='?r='+encodeURIComponent(location.pathname+location.search);">
						<img class="logo" src="/assets/img/login-google.png">
					</a>
					<a href="https://t.me/xNCTUbot?start=login" onclick="this.href+='?start=login_'+encodeURIComponent(location.pathname+location.search);">
						<img class="logo" src="/assets/img/login-telegram.png">
					</a>
				</div>
			</div>
		</dialog>
	</div>
</div>
