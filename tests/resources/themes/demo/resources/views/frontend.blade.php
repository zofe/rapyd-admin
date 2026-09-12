@extends('layout::app')

@section('main')
    <div id="wrapper">

        <div id="content-wrapper" class="min-vh-100">
            <div id="content">

                @include('layout::includes.frontend_navbar')

                <div class="container">
                    @yield('main-content')
                    {{ $slot ?? '' }}
                </div>

            </div>
        </div>

    </div>
@endsection
