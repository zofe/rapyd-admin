<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">


    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
{{--    <script src="{{ asset('js/app.js') }}" defer></script>--}}
{{--    <script src="{{ asset('vendor/layout/layout.js') }}" defer></script>--}}

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i|Roboto+Mono:wght@100" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css" integrity="sha512-1PKOgIY59xJ8Co8+NE6FZ+LOAZKjy+KY8iq0G4B3CyeY6wYHN3yt9PW0XpSriVlkMXe40PTKnXrLnZ9+fkDaog==" crossorigin="anonymous" />
    <link rel="icon" href="{{ asset('img/favicon.png') }}" type="image/png">
    <link rel="icon" href="{{ asset('img/favicon-32x32.png') }}" sizes="32x32" />
    <link rel="icon" href="{{ asset('img/favicon-192x192.png') }}" sizes="192x192" />
    <link rel="apple-touch-icon-precomposed"  href="{{ asset('img/favicon-180x180.png') }}" />

{{--    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.7.0/dist/alpine.min.js" defer></script>--}}

    <link href="{{ asset('vendor/layout/styles.css') }}" rel="stylesheet">
    @if(config('layout.custom_css'))
        <link href="{{ config('layout.custom_css') }}" rel="stylesheet">
    @endif
    <!-- Favicon -->

{{--    @include('includes/scripts/map')--}}

    <script>
        window.Laravel = <?php echo json_encode([
            'csrfToken' => csrf_token(),
        ]); ?>

        window._locale = '{{ app()->getLocale() }}';
        //window._translations = {!! cache('translations') !!};
        window.livewire_app_url = '/';
    </script>

    @stack('head_scripts')
    @livewireStyles
    @rapydStyles
</head>
<body class="bg-auth min-vh-100 d-flex justify-content-center align-items-center bg-white">

<div id="app" class="container">
    @yield('main-content')
    {{ $slot ??'' }}
</div>



@livewireScripts


@rapydScripts
@stack('footer_scripts')
{{--<script src="{{ asset('vendor/rapyd-livewire/bootstrap.js') }}" defer></script>--}}
<script src="{{ asset('vendor/rapyd-livewire/alpine.js') }}" defer></script>
<script src="{{ asset('vendor/layout/layout.js') }}" defer></script>
</body>
</html>
