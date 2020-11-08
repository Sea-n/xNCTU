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
        <div class="description">靠北交大 2.0</div>
    </div>
</header>

<div class="ts container" name="main">
@yield('content')
</div>

@include('includes.footer')
</body>
</html>
