<nav class="navbar navbar-expand-sm navbar-front static-top mb-4 topbar">
    <div class="container">

        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name', 'Laravel') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse px-2" id="navbarSupportedContent">

            <ul class="navbar-nav me-auto">
                @section('left_navbar')
                    @foreach(config('rapyd.menus.frontend', []) as $menu)
                        @include($menu)
                    @endforeach
                @show

                @if(Route::has('admin.home') && Auth::user())
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.home') }}">
                            <span class="text-capitalize pt-1">
                                @if(Auth::user()->hasRole('admin'))
                                    Admin
                                @else
                                    Dashboard
                                @endif
                            </span>
                        </a>
                    </li>
                @endif
            </ul>

            <ul class="navbar-nav ms-auto">
                @stack('right_navbar')

                @guest
                    @if(Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @endif
                    @if(Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                    @endif
                @else
                    @include('layout::includes.user_info_dropdown')
                    @include('layout::includes.theme_switcher')
                @endguest
            </ul>

        </div>
    </div>
</nav>
