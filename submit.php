<?php
if (!check_cf_ip($_SERVER['REMOTE_ADDR'] ?? '1.1.1.1'))
	exit("Please don't hack me.");

if ($_SERVER["HTTP_CF_IPCOUNTRY"] != 'TW')
	exit("This service is limited to Taiwan's IP address. 此服務僅限台灣 IP 位址使用");

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
if (isset($_POST['text'])) {

} else {
	$ip = explode('.', $ip);
	$ip[2] = 'xxx';
	$ip[3] = 'x'.$ip[3]%100;
	$ip = join('.', $ip);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>發文申請 - 靠交 2.0</title>
		<link rel="icon" type="image/png" href="/logo-192.png" sizes="192x192">
		<link rel="icon" type="image/png" href="/logo-128.png" sizes="128x128">
		<link rel="icon" type="image/png" href="/logo-96.png" sizes="96x96">
		<link rel="icon" type="image/png" href="/logo-64.png" sizes="64x64">
		<link rel="icon" type="image/png" href="/logo-32.png" sizes="32x32">
		<link rel="icon" type="image/png" href="/logo-16.png" sizes="16x16">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
		<meta name="keywords" content="NCTU, 靠北交大, 靠交 2.0" />
		<meta name="description" content="在這裡您可以匿名地發送貼文" />
		<meta property="og:title" content="發文申請" />
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
	</head>
	<body>
		<div>
			<div class="row">
				<div class="col-xs-12 col-sm-offset-1 col-sm-10 col-md-offset-2 col-md-8 col-lg-offset-3 col-lg-6">
					<h1>靠交 2.0</h1>
					<p>給您一個沒有偷懶小編的靠北交大</p>

					<h2>發文規則</h2>
					<ol>
						<li>攻擊性投稿內容不能含有姓名、暱稱等各種明顯洩漏對方身分的個人資料，請把關鍵字自行碼掉，例如王 XX、王學長。
							<ul><li>登入後具名投稿者，不受此條文之限制。</li></ul></li>
						<li>含有性別歧視、種族歧視、人身攻擊、色情內容、不實訊息等文章，將由審核團隊衡量發文尺度。</li>
						<li>如果對文章感到不舒服、或是怕被發現是自己發的文想要刪文，請有禮貌的私訊審核團隊，並有合理的理由說服審核者，才會予以刪文。</li>
					</ol>

					<h2>貼文內容</h2>
					<form action="/submit" method="POST">
						<p>字數上限：<span id="wc">0</span> / 1,024</p>
						<textarea id="text" name="text" rows="6" maxlength="1024" placeholder="請在這輸入您的投稿內容。" style="width: 100%;"></textarea>
						<p>附加圖片：<input type="file" id="img" name="img" accept="image/*" style="display: inline-block;"></p>
						<input type="submit" class="btn btn-info" value="提交貼文">
						<p>請注意：您的 IP 位址 (<?= $ip ?>) 將會永久保留於系統後台，所有審核者均可見</p>
						<input type="hidden" name="ip" value="$ip">
					</form>

					<p></p>
				</div>
			</div>
		</div>
		<footer class="panel-footer">
			<center><p>&copy; 2020 <a target="_blank" href="https://www.sean.taipei/">Sean</a></p></center>
		</footer>
	</body>
</html>

<?php
function check_cf_ip(string $addr) {
	$range = [
		"173.245.48.0" => 20,
		"103.21.244.0" => 22,
		"103.22.200.0" => 22,
		"103.31.4.0" => 22,
		"141.101.64.0" => 18,
		"108.162.192.0" => 18,
		"190.93.240.0" => 20,
		"188.114.96.0" => 20,
		"197.234.240.0" => 22,
		"198.41.128.0" => 17,
		"162.158.0.0" => 15,
		"104.16.0.0" => 12,
		"172.64.0.0" => 13,
		"131.0.72.0" => 22
	];

	foreach ($range as $base => $cidr)
		if (ip2long($addr)>>(32-$cidr)
		=== ip2long($base)>>(32-$cidr))
			return true;

	return false;
}
