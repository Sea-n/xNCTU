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
        <h1 class="ts header">@yield('header')</h1>
        <div class="description">{{ env('APP_CHINESE_NAME') }}</div>
    </div>
</header>

<div class="ts container" name="main">
@yield('content')

@include('includes.imgbox')
</div>

@include('includes.footer')
</body>
</html>
