<!-- Sidebar -->
<ul class="navbar-nav {{app()->environment([]) ? 'bg-gradient-primary-'.app()->environment() : 'bg-gradient-primary'}} bg-sidebar sidebar sidebar-dark accordion" id="accordionSidebar">


    @php
        $homeRoute = (Route::has('admin.home') && Route::has('home')) ? route('admin.home') : route('home');
    @endphp

    <a class="sidebar-brand d-flex align-items-center justify-content-center" style="overflow: hidden;" href="{{ $homeRoute }}">


        @if(config('layout.logo_sidebar'))
            <img src="{{ config('layout.logo_sidebar') }}" class="img-fluid px-2" />
        @else
            {{ config('app.name') }}
        @endif

    </a>

    @if(app()->environment(['stage']))
        <div class="text-white text-center py-1 h5">
            {{ app()->environment() }}
        </div>
    @endif


    @section('left_sidebar')
        @foreach(config('rapyd.menus.admin') as $menu)
                @include($menu)
        @endforeach

        @includeIf('menu')
    @show

    @yield('role_menu')


    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
