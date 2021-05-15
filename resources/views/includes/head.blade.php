<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>@yield('title') - {{ env('APP_CHINESE_NAME') }} ({{ env('APP_NAME') }})</title>
<link rel="icon" type="image/png" href="/assets/img/logo-192.png" sizes="192x192">
<link rel="icon" type="image/png" href="/assets/img/logo-128.png" sizes="128x128">
<link rel="icon" type="image/png" href="/assets/img/logo-96.png" sizes="96x96">
<link rel="icon" type="image/png" href="/assets/img/logo-64.png" sizes="64x64">
<link rel="icon" type="image/png" href="/assets/img/logo-32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/assets/img/logo-16.png" sizes="16x16">

<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<meta name="keywords" content="{{ env('APP_NAME') }}, {{ env('APP_CHINESE_NAME') }}" />
<meta name="description" content="@yield('desc', '不要問為何沒有人審文，先承認你就是沒有人。新版' . env('APP_CHINESE_NAME') . ' (' . env('APP_NAME') . ') 讓全校師生都有了審核的權限，每天穩定發出投稿文章。並支援 Telegram、Plurk、Twitter、Facebook、Instagram 五大社群媒體平台。')" />
<link rel="canonical" href="{{ url()->current() }}" />

<meta property="og:title" content="@yield('title')" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="@yield('img', url('/assets/img/og.png'))" />
<meta property="og:image:secure_url" content="@yield('img', url('/assets/img/og.png'))" />
<meta property="og:type" content="website" />
<meta property="og:description" content="@yield('desc', '不要問為何沒有人審文，先承認你就是沒有人。新版' . env('APP_CHINESE_NAME') . ' (' . env('APP_NAME') . ') 讓全校師生都有了審核的權限，每天穩定發出投稿文章。並支援 Telegram、Plurk、Twitter、Facebook、Instagram 五大社群媒體平台。')" />
<meta property="og:site_name" content="{{ env('APP_CHINESE_NAME') }}" />
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="{{ '@' . env('TWITTER_USERNAME') }}" />
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
