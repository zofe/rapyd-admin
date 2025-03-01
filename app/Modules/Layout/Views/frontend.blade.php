@extends('layout::app')

@section('main')
    <div id="wrapper">

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="min-vh-100">


            <!-- Main Content -->
            <div id="content">

                @include('layout::includes/frontend_navbar')

                <div class="container">


{{--                    <div class="navbar-nav">--}}
{{--                        <div class="nav-item navbar-search-wrapper pt-3">--}}
{{--                            <x-rpd::breadcrumbs class="breadcrumb-item small" active="active" />--}}
{{--                        </div>--}}
{{--                    </div>--}}

                    @yield('main-content')
                    {{ $slot ??'' }}
                </div>

            </div>
            <!-- End of Main Content -->



        </div>
        <!-- End of Content Wrapper -->

    </div>

    <!-- Scroll to Top Button-->

@endsection
