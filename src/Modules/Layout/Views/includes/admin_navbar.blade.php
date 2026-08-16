<nav class="navbar navbar-admin navbar-expand-sm topbar mb-2 static-top shadow">
    <div class="container-fluid">

        <button id="sidebarToggleTop" class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarScroll" aria-controls="navbarScroll" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse rounded d-flex" id="navbarScroll">

            @if(config('search.models'))
                @livewire('search::search-navbar')
            @endif

            <ul class="navbar-nav">
                @if(config('app.locales'))
                    <li class="nav-item dropdown no-arrow">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="{{ asset('vendor/rapyd/img/'.app()->getLocale().'.svg') }}" width="15" alt="{{ app()->getLocale() }}">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            @foreach(config('app.locales') as $locale)
                                <a class="dropdown-item" href="{{ url_lang($locale) }}">
                                    <img src="{{ asset("vendor/rapyd/img/{$locale}.svg") }}" width="10" alt="{{ $locale }}">
                                    {{ $locale }}
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endif

                @if(Route::has('admin.home') && Route::has('home'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">Home</a>
                    </li>
                @endif
            </ul>

            <ul class="navbar-nav ms-auto">
                @guest
                    @if(Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                    @endif
                @endguest

                @auth
                    @include('layout::includes.user_info_dropdown')
                @endauth

                @include('layout::includes.theme_switcher')
            </ul>

        </div>

    </div>
</nav>
