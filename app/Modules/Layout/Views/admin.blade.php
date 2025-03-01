@extends('layout::app')

@section('main')
    <div id="wrapper">

        @include('layout::includes/admin_sidebar')

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">


                @include('layout::includes/admin_navbar')

                <div class="container-fluid">
                    <div class="navbar-nav">
                        <div class="nav-item navbar-search-wrapper pt-3">
                            <x-rpd::breadcrumbs class="breadcrumb-item small" active="active" />
                        </div>
                    </div>

                    @include('layout::includes/messages')


                    @yield('main-content')
                    {{ $slot ??'' }}


                    @yield('doc')
                </div>

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">



                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>

    </a>

{{--    <!-- Logout Modal-->--}}
{{--    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">--}}
{{--        <div class="modal-dialog" role="document">--}}
{{--            <div class="modal-content">--}}
{{--                <div class="modal-header">--}}
{{--                    <h5 class="modal-title" id="exampleModalLabel">{{ __('message.ready_to_leave') }}</h5>--}}
{{--                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">--}}
{{--                        <span aria-hidden="true">×</span>--}}
{{--                    </button>--}}
{{--                </div>--}}
{{--                <div class="modal-body">{{ __('message.confirm_logout') }}</div>--}}
{{--                <div class="modal-footer">--}}
{{--                    <button class="btn btn-link" type="button" data-dismiss="modal">{{ __('global.cancel') }}</button>--}}
{{--                    <a class="btn btn-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">{{ __('global.logout') }}</a>--}}
{{--                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">--}}
{{--                        @csrf--}}
{{--                    </form>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
@endsection
