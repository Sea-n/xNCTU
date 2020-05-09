<?php
require_once(__DIR__ . '/../config.php');

$TITLE = htmlentities($TITLE);
$TITLE = str_replace("\n", "  ", $TITLE);

if (!isset($DESC))
	$DESC = "不要問為何沒有人審文，先承認你就是沒有人。新版$SITENAME (xNCTU) 讓全校師生都有了審核的權限，每天穩定發出投稿文章。並支援 Telegram、Plurk、Twitter、Facebook、Instagram 五大社群媒體平台。";
$DESC = htmlentities($DESC);
$DESC = str_replace("\n", "  ", $DESC);

if (isset($IMG))
	$IMG = htmlentities($IMG);

$URL = $_SERVER['REQUEST_URI'];
$URL = explode('?', $URL, 2)[0];  // remove the query part
$URL = "https://$DOMAIN$URL";
$URL = htmlentities($URL);

?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= $TITLE ?> - <?= SITENAME ?> (xNCTU)</title>
<link rel="icon" type="image/png" href="/assets/img/logo-192.png" sizes="192x192">
<link rel="icon" type="image/png" href="/assets/img/logo-128.png" sizes="128x128">
<link rel="icon" type="image/png" href="/assets/img/logo-96.png" sizes="96x96">
<link rel="icon" type="image/png" href="/assets/img/logo-64.png" sizes="64x64">
<link rel="icon" type="image/png" href="/assets/img/logo-32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/assets/img/logo-16.png" sizes="16x16">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<meta name="keywords" content="xNCTU, <?= SITENAME ?>" />
<meta name="description" content="<?= $DESC ?>" />
<link rel="canonical" href="<?= $URL ?>" />
<meta property="og:title" content="<?= $TITLE ?>" />
<meta property="og:url" content="<?= $URL ?>" />
<?php if (isset($IMG)) { ?>
<meta property="og:image" content="<?= $IMG ?>" />
<meta property="og:image:secure_url" content="<?= $IMG ?>" />
<?php } ?>
<meta property="og:type" content="website" />
<meta property="og:description" content="<?= $DESC ?>" />
<meta property="og:site_name" content="<?= SITENAME ?>" />
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@x_NCTU" />
<meta property="fb:app_id" content="776010579474059" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tocas-ui/2.3.3/tocas.css">
<link rel="stylesheet" href="/assets/css/style.css">
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-158901570-1"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());

	gtag('config', 'UA-158901570-1');
</script>
