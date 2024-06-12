<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Page Title' }}</title>
    {{--        @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    <style>
        html, body {
            height: 100%;
            font-family: 'Ubuntu', sans-serif;
        }
    </style>
</head>
<body>

<div class="container-fluid p-0 d-flex h-100">

    <x-rpd::sidebar title="Admin" class="p-3 text-white border-end">

        <x-rpd::nav-item label="Home" route="home" active="/" />

        {{--    <x-rpd::nav-item label="Profiles" route="profiles.table" active="profiles/table" />--}}
        {{--<x-rpd::nav-item label="Users" route="users.table" active="users/table" />--}}

        @section('left_sidebar')
            @foreach(config('rapyd.menus.admin') as $menu)
                @include($menu)
            @endforeach
        @show

        @yield('role_menu')


    </x-rpd::sidebar>

    <div class="flex-fill ps-3 container">
        <div class="navbar-nav navbar-expand-md">
            <div class="nav-item navbar-search-wrapper pt-3">
                <x-rpd::breadcrumbs />
            </div>

            {{--            <!-- Authentication Links -->--}}
            {{--            @guest--}}
            {{--                @if (Route::has('login'))--}}
            {{--                    <li class="nav-item " role="search">--}}
            {{--                        <a class="nav-link" href="{{ route_lang('login') }}">{{ __('Login') }}</a>--}}
            {{--                    </li>--}}
            {{--                @endif--}}
            {{--            @else--}}
            {{--                <li class="nav-item dropdown" role="search">--}}
            {{--                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>--}}
            {{--                        {{ Auth::user()->name }}--}}
            {{--                    </a>--}}

            {{--                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">--}}
            {{--                        <a class="dropdown-item" href="{{ route_lang('logout') }}"--}}
            {{--                           onclick="event.preventDefault();--}}
            {{--                                                     document.getElementById('logout-form').submit();">--}}
            {{--                            {{ __('Logout') }}--}}
            {{--                        </a>--}}

            {{--                        <form id="logout-form" action="{{ route_lang('logout') }}" method="POST" class="d-none">--}}
            {{--                            @csrf--}}
            {{--                        </form>--}}
            {{--                    </div>--}}
            {{--                </li>--}}
            {{--            @endguest--}}



        </div>

        @if(isset($slot))
            {{ $slot }}
        @else
            @yield('main-content')
        @endif
    </div>


</div>


</body>
</html>
