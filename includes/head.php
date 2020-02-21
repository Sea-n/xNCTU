<?php
if (!isset($IMG))
	$IMG = 'https://x.nctu.app/assets/img/logo.png';

if (!isset($DESC))
	$DESC = '不要問為何沒有人審文，先承認你就是沒有人。新版靠北交大 2.0 (xNCTU) 讓全校師生都有了審核的權限，每天穩定發出投稿文章。並支援 Telegram、Plurk、Twitter、Facebook 四大社群媒體平台。';

$URL = $_SERVER['REQUEST_URI'];
$URL = explode('?', $URL, 2);
parse_str($URL[1] ?? '', $q);
$URL = $URL[0];
$query = [];
foreach (['id', 'uid'] as $i)
	if (isset($q[$i]))
		$query[$i] = $q[$i];
if (count($query) > 0)
	$URL .= '?' . http_build_query($query);

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= $TITLE ?> - 靠北交大 2.0 (xNCTU)</title>
<link rel="icon" type="image/png" href="/assets/img/logo-192.png" sizes="192x192">
<link rel="icon" type="image/png" href="/assets/img/logo-128.png" sizes="128x128">
<link rel="icon" type="image/png" href="/assets/img/logo-96.png" sizes="96x96">
<link rel="icon" type="image/png" href="/assets/img/logo-64.png" sizes="64x64">
<link rel="icon" type="image/png" href="/assets/img/logo-32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/assets/img/logo-16.png" sizes="16x16">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<meta name="keywords" content="xNCTU, 靠北交大 2.0" />
<meta name="description" content="<?= htmlentities($DESC) ?>" />
<meta property="og:title" content="<?= htmlentities($TITLE) ?>" />
<meta property="og:url" content="https://x.nctu.app<?= htmlentities($URL) ?>" />
<meta property="og:image" content="<?= htmlentities($IMG) ?>" />
<meta property="og:image:secure_url" content="<?= htmlentities($IMG) ?>" />
<meta property="og:type" content="website" />
<meta property="og:description" content="<?= htmlentities($DESC) ?>" />
<meta property="og:site_name" content="靠北交大 2.0" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tocas-ui/2.3.3/tocas.css">
<link rel="stylesheet" href="/assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/tocas-ui/2.3.3/tocas.js"></script>
<script src="/assets/js/common.js"></script>
