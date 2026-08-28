<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900|Roboto+Mono:wght@100" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('img/favicon-32x32.png') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('img/favicon-192x192.png') }}" sizes="192x192">
    <link rel="apple-touch-icon-precomposed" href="{{ asset('img/favicon-180x180.png') }}">

    @if(config('layout.custom_css'))
        <link href="{{ config('layout.custom_css') }}" rel="stylesheet">
    @endif

    @rapydStyles
    @livewireStyles
    @stack('head_scripts')
</head>
<body>

<div id="app">
    @section('main')
    <main class="p-4">
        @yield('content')
        {{ $slot ?? '' }}
    </main>
    @show
</div>

@aiWidget
@livewireScripts
@rapydScripts
@stack('footer_scripts')
</body>
</html>
