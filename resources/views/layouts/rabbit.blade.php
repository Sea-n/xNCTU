<!DOCTYPE html>
<html>
<head>
@include('includes.head')

@yield('head')
</head>
<body>
@include('includes.nav')

<header class="ts fluid vertically padded heading slate">
    <div class="ts narrow container">
        <h1 class="ts header">靠北交大 2.0</h1>
        <div class="description">新版靠北交大讓全校師生都有了審核的權限，每天穩定發出投稿文章。</div>
    </div>
</header>

<div class="ts container" name="main">
@yield('content')

@include('includes.imgbox')
</div>

@include('includes.footer')
</body>
</html>
