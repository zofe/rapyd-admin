<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('img/favicon-32x32.png') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('img/favicon-192x192.png') }}" sizes="192x192">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('img/favicon-180x180.png') }}">

    @if(config('layout.custom_css'))
        <link href="{{ config('layout.custom_css') }}" rel="stylesheet">
    @endif

    @livewireStyles
    @rapydStyles
    @stack('head_scripts')
</head>
<body class="bg-white min-vh-100 d-flex justify-content-center align-items-center">

<div id="app" class="container">
    @yield('main-content')
    {{ $slot ?? '' }}
</div>

@livewireScripts
@rapydScripts
@stack('footer_scripts')
</body>
</html>
