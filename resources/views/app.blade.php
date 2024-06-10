<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    @section('meta', '')

    <link href="//fonts.googleapis.com/css?family=Bitter" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="{{ asset('vendor/rapyd/bootstrap.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @livewireStyles
    @rapydStyles
</head>
<body>

<main class="d-flex" x-data x-cloak>

    @yield('sidebar')

    <div id="app" >

        <div class="container py-3">
            <header>
                default app layout
                @yield('header')
            </header>

            @section('main-content')
                <div class="row">
                    <div class="col-sm-12 gx-5">
                        @yield('content')
                        {{ $slot ??'' }}

                    </div>
                </div>
            @show


            <div class="row">
                <div class="col-sm-12 gx-5">
                    @yield('doc')
                </div>
            </div>


        </div>
    </div>

    <div id="footer"></div>

</main>

@livewireScripts
@rapydScripts

<script src="{{ asset('vendor/rapyd/bootstrap.js') }}" defer></script>
<script src="{{ asset('vendor/rapyd/alpine.js') }}" defer></script>
</body>
</html>
