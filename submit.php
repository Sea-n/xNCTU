<?php
require('database.php');
$db = new MyDB();

if (!check_cf_ip($_SERVER['REMOTE_ADDR'] ?? '1.1.1.1'))
	exit("Please don't hack me.");

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
if ($_SERVER["HTTP_CF_IPCOUNTRY"] != 'TW')
	$ip_author = "境外, {$_SERVER["HTTP_CF_IPCOUNTRY"]}";
else switch (substr($ip, 0, 8)) {
	case '140.113.': $ip_author = "交大, 台灣"; break;
	case '140.112.': $ip_author = "台大, 台灣"; break;
	case '140.114.': $ip_author = "清大, 台灣"; break;
	case '140.115.': $ip_author = "中央, 台灣"; break;
	case '140.116.': $ip_author = "成大, 台灣"; break;
	case '140.118.': $ip_author = "台科, 台灣"; break;
	case '140.119.': $ip_author = "政大, 台灣"; break;
	case '140.121.': $ip_author = "海大, 台灣"; break;
	case '140.122.': $ip_author = "師大, 台灣"; break;
	case '140.124.': $ip_author = "北科, 台灣"; break;
	case '140.129.': $ip_author = "陽明, 台灣"; break;

	default:
	$curl = curl_init('https://rms.twnic.net.tw/query_whois1.php');
	curl_setopt($curl, CURLOPT_POSTFIELDS, "q=$ip");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	$resp = curl_exec($curl);
	if (preg_match('#<tr><td>Chinese Name</td><td>([^<]+?)(股份|有限|公司)*</td></tr>#', $resp, $matches))
		$ip_author = "{$matches[1]}, 台灣";
	else
		$ip_author = "台灣";
}
if (isset($_POST['body'])) {
	$captcha = trim($_POST['captcha'] ?? 'X');
	if ($captcha != '交大竹湖')
		exit('Are you human? 驗證碼錯誤');

	$body = $_POST['body'];
	if (mb_strlen($body) < 5)
		exit('Body too short. 文章過短');
	if (mb_strlen($body) > 1024)
		exit('Body too long. 文章過長');

	if (isset($_FILES['img'])) {
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
			$rand = $db->rand58(4);
			$img = "$rand.$ext";
			$dst = __DIR__ . "/img/$img";
		} while (file_exists($dst));

		if (!move_uploaded_file($src, $dst))
			exit('Failed to move uploaded file. 上傳發生錯誤');
	} else
		$img = '';

	$uid = $db->rand58(4);
	$author = "匿名, $ip_author";

	$error = $db->insertSubmission($uid, $body, $img, $ip, $author);
	if ($error[0] != '00000')
		exit("Database error {$error[0]}, {$error[1]}, {$error[2]}. 資料庫發生錯誤");
} else {
	$captcha = "請輸入「交大ㄓㄨˊㄏㄨˊ」（四個字）";

	$ip_masked = explode('.', $ip);
	$ip_masked[2] = 'xxx';
	$ip_masked[3] = 'x'.$ip_masked[3]%100;
	$ip_masked = join('.', $ip_masked);
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
		<link rel="stylesheet" href="/style.css">
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
					<p>您可以於 <a href="/post?uid=<?= $uid ?>">這裡</a> 查看審核動態，但提醒您為自己的貼文按「通過」會留下公開紀錄</p>
<?php } else { ?>
					<h2>發文規則</h2>
					<ol>
						<li>攻擊性投稿內容不能含有姓名、暱稱等各種明顯洩漏對方身分的個人資料，請把關鍵字自行碼掉，例如王 XX、王學長。
							<ul><li>登入後具名投稿者，不受此條文之限制。</li></ul></li>
						<li>含有性別歧視、種族歧視、人身攻擊、色情內容、不實訊息等文章，將由審核團隊衡量發文尺度。</li>
						<li>如果對文章感到不舒服、或是怕被發現是自己發的文想要刪文，請有禮貌的私訊審核團隊，並有合理的理由說服審核者，才會予以刪文。</li>
					</ol>

					<h2>貼文內容</h2>
					<form action="/submit" method="POST" enctype="multipart/form-data">
						<p>字數上限：<span id="wc">0</span> / 1,024</p>
						<textarea id="body" name="body" rows="6" maxlength="1024" placeholder="請在這輸入您的投稿內容。" style="width: 100%;"></textarea>
						<p>附加圖片：<input type="file" name="img" accept="image/*" style="display: inline-block;" /></p>
						<p>驗證問答：<?= $captcha ?><input id="captcha" name="captcha" size="8" /></p>
						<input type="submit" class="btn btn-info" value="提交貼文" />
						<p>請注意：您使用的網路服務商（<?= $ip_author ?>）及部分 IP 位址 (<?= $ip_masked ?>) 將會永久保留於系統後台，所有已登入的審核者均可見。</p>
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
