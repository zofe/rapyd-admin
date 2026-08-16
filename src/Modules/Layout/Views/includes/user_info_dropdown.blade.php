<li class="nav-item dropdown no-arrow">

    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <div class="mr-2 d-none d-lg-inline">
            <div class="px-2"><span class="small text-gray-500">logged as &nbsp;</span> {{ Auth::user()->name }}</div>
            @if(Auth::user()->company ?? null)
                <div class="text-center mt-n2"><small><em>{{ Auth::user()->company->business_name }}</em></small></div>
            @endif
        </div>
        <img class="img-profile rounded-circle mb-1"
             src="{{ Auth::user()->avatar ? asset('storage/users/'.Auth::user()->id.'/photos/avatar.jpg') : asset('vendor/rapyd/img/user-account-icon.png') }}"
             alt="{{ Auth::user()->name }}">
    </a>

    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in" aria-labelledby="userDropdown">
        @if(Route::has('profile'))
            <a class="dropdown-item" href="{{ route('profile') }}">
                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-500"></i>
                {{ __('Profile') }}
            </a>
        @endif

        @impersonating
        <a class="dropdown-item" href="{{ route('impersonate.leave') }}">
            <i class="fas fa-user-slash fa-sm fa-fw mr-2"></i>
            {{ __('Leave impersonation') }}
        </a>
        @endImpersonating

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a class="dropdown-item" href="#" onclick="this.parentNode.submit();">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i>
                {{ __('Logout') }}
            </a>
        </form>

        @yield('user_info_dropdown')
    </div>

</li>
