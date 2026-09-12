{{-- Layout contract: admin area (docs/THEMES.md) --}}
@extends('layout::app')

@section('main')
    <div class="demo-theme-marker">DEMO THEME</div>
    <div id="wrapper">

        @include('layout::includes.admin_sidebar')

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                @include('layout::includes.admin_navbar')

                <div class="container-fluid">
                    <div class="navbar-nav">
                        <div class="nav-item navbar-search-wrapper pt-1">
                            <x-rpd::breadcrumbs class="breadcrumb-item" active="active" />
                        </div>
                    </div>

                    @stack('page_header')

                    @include('layout::includes.messages')

                    @yield('main-content')
                    {{ $slot ?? '' }}

                    @yield('doc')
                </div>

            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        @stack('footer')
                    </div>
                </div>
            </footer>

        </div>

    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
@endsection
