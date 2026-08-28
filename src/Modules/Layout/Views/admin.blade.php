@extends('layout::app')

@section('main')
    <div id="wrapper">

        @include('layout::includes.admin_sidebar')

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                @include('layout::includes.admin_navbar')

                <div class="container-fluid">
                    <div class="navbar-nav">
                        <div class="nav-item navbar-search-wrapper pt-1">
                            <x-rpd::breadcrumbs class="breadcrumb-item" active="active" />
                        </div>
                    </div>

                    @include('layout::includes.messages')

                    @yield('main-content')
                    {{ $slot ?? '' }}

                    @yield('doc')
                </div>

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto"></div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>

    <!-- Scroll to Top Button -->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

@endsection
