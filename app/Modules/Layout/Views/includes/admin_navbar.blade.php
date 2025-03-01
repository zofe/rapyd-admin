<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <div class="container-fluid">
        <!-- Sidebar Toggle (Topbar) -->
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
        </button>

        <span class="nav-heading">
            {{ optional(optional(optional(auth()->user())->roles())->first())->name }} panel
        </span>

        <!-- Topbar Navbar -->
        <ul class="navbar-nav ml-auto">

            @if(config('search.models'))
                @ifcomponent('search::search-navbar')
                    @livewire('search::search-navbar')
                @endifcomponent
            @endif

            @if(config('app.locales'))
                <li class="nav-item dropdown no-arrow">
                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                        <img src="{{ asset('vendor/layout/img/'.app()->getLocale().'.svg') }}" width="15">
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        @foreach(config('app.locales') as $locale)
                            <a class="dropdown-item" href="{{ url_lang($locale) }}">
                                <img src="{{ asset("vendor/layout/img/{$locale}.svg") }}" width="15">
                                {{ $locale }}
                            </a>
                        @endforeach
                    </div>
                </li>
            @endif


            @if (Route::has('admin.home') && Route::has('home'))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">Home</a>
                </li>
            @endif

            @if(Auth::user())
            <!-- Nav Item - User Information -->
            <li class="nav-item dropdown no-arrow">

                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="mr-2 d-none d-lg-inline text-gray-600 small">
                            <div class="px-2">{{ Auth::user()->name }}</div>
                            @if(Auth::user()->company) <div class="text-center mt-n2"><small><em>{{Auth::user()->company->business_name}}</em></small></div> @endif
                    </div>
                    <img class="img-profile rounded-circle" src="{{ Auth::user()->avatar ? asset("storage/users/".Auth::user()->id."/photos/avatar.jpg") : asset('vendor/layout/img/user-account-icon.png') }}">
                </a>

                <!-- Dropdown - User Information -->
                <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
                    @if(Route::has('profile'))
                    <a class="dropdown-item" href="{{ route('profile') }}">
                        <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                        {{ __('Profile') }}
                    </a>
                    @endif

                    @impersonating
                    <a class="dropdown-item" href="{{ route('impersonate.leave') }}">
                        <i class="fas fa-user-slash fa-sm fa-fw mr-2 text-gray-400"></i>
                        {{ __('Leave impersonation') }}
                    </a>
                    @endImpersonating

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <a class="dropdown-item" href="#" onclick="this.parentNode.submit();">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>

                            {{ __('Logout') }}
                        </a>
                    </form>



                    @yield('user_info_dropdown')

                </div>

            </li>
            @endif
        </ul>
    </div>
</nav>

