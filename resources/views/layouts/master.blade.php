<!DOCTYPE html>
<html>
<head>
@include('includes.head')

@yield('head')
</head>
<body>
@include('includes.nav')
@include('includes.header')

@yield('content')

@include('includes.footer')
</body>
</html>
