<!DOCTYPE html>
<html>
<head>
@include('includes.head')

@yield('head')
</head>
<body>
@include('includes.nav')

@include('includes.header')

<div class="ts container" name="main">
@yield('content')
</div>

@include('includes.footer')
</body>
</html>
