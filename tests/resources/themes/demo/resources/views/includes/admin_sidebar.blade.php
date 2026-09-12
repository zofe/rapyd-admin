<ul class="navbar-nav bg-sidebar sidebar accordion" id="accordionSidebar">

    @php
        // Routes are optional for a theme: always guard them with Route::has().
        $homeRoute = Route::has('admin.home') ? route('admin.home') : (Route::has('home') ? route('home') : url('/'));
    @endphp

    <a class="sidebar-brand d-flex align-items-center justify-content-center" style="overflow: hidden;" href="{{ $homeRoute }}">
        @if(config('rapyd.layout.logo_sidebar'))
            <img src="{{ config('rapyd.layout.logo_sidebar') }}" class="img-fluid px-2" alt="{{ config('rapyd.layout.brand') ?: config('app.name') }}">
        @else
            {{ config('rapyd.layout.brand') ?: config('app.name') }}
        @endif
    </a>

    @if(app()->environment(['stage']))
        <div class="text-white text-center py-1 h5">
            {{ app()->environment() }}
        </div>
    @endif

    @section('left_sidebar')
        @foreach(config('rapyd.menus.admin', []) as $menu)
            @include($menu)
        @endforeach

        @includeIf('menu')
    @show

    @yield('role_menu')

    @stack('sidebar_footer')

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Sidebar Toggler -->
    <div class="text-center">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
