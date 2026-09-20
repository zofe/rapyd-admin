<nav class="navbar navbar-admin navbar-expand-sm topbar mb-2 static-top shadow">
    <div class="container-fluid">

        <button id="sidebarToggleTop" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll" aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse rounded d-flex" id="navbarScroll">

            @if(config('rapyd.search.enabled', true) && Route::has('search.items'))
                @livewire('search::search-navbar')
            @endif

            <ul class="navbar-nav">
                @include('layout::includes.locale_switcher')

                @if(Route::has('admin.home') && Route::has('home'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">{{ __('Home') }}</a>
                    </li>
                @endif
            </ul>

            <ul class="navbar-nav ms-auto">
                @stack('navbar_right')

                @guest
                    @if(Route::has('login') && config('rapyd.layout.auth_links', true))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @endif
                @endguest

                @auth
                    @include('layout::includes.user_info_dropdown')
                @endauth

                @include('layout::includes.theme_switcher')
                @include('layout::includes.theme_picker')
            </ul>

        </div>

    </div>
</nav>
