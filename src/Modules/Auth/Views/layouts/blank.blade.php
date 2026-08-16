<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        html, body { height: 100%; }
        body { background-color: #f8f9fa; }
        .auth-card { max-width: 900px; }
        .auth-card-narrow { max-width: 460px; }
    </style>
    @yield('styles')
</head>
<body class="d-flex align-items-center min-vh-100">
    @yield('main-content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @rapydScripts
    @yield('scripts')
</body>
</html>
