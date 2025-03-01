@extends('auth::blank')

@section('main-content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-5">
                    <div class="row">
                        <div class="col-lg-6 d-flex align-items-center justify-content-center">

                            @if(config('layout.logo_login'))
                                <img src="{{ config('layout.logo_login') }}" class="img-fluid px-2">
                            @else
                                <h1>{{ config('app.name', 'Laravel') }}</h1>
                            @endif


                        </div>
                        <div class="col-lg-6">
                            <div class="p-md-5">

                                <div class="text-center">
                                    <h1 class="h4 text-gray-900 mb-4">{{ __('Login') }}</h1>
                                </div>

                                @if ($errors->any() || session()->has('log_error'))

                                    <div class="alert alert-danger border-left-danger" role="alert">
                                        <ul class="pl-4 my-2">
                                            @if(session()->has('log_error'))
                                                <li>{{ session('log_error') }}</li>
                                            @else
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            @endif
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route_lang('login') }}" class="user">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                                    <div class="form-group">
                                        <input type="email" class="form-control form-control-user" name="email" placeholder="{{ __('E-Mail Address') }}" value="{{ old('email') }}" required autofocus>
                                    </div>

                                    <div class="form-group pt-1">
                                        <input type="password" class="form-control form-control-user" name="password" placeholder="{{ __('Password') }}" required>
                                        @if (Route::has('password.request'))
                                            <div class="float-right pt-1">
                                                <a class="small" href="{{ route_lang('password.request') }}">
                                                    {{ __('Forgot Your Password?') }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="form-group pt-1">
                                        <div class="custom-control custom-checkbox small">
                                            <input type="checkbox" class="custom-control-input" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="remember">{{ __('Remember Me') }}</label>
                                        </div>
                                    </div>

{{--                                    <div class="form-group">--}}
{{--                                        <div class="custom-control custom-checkbox small">--}}
{{--                                            <input type="checkbox" class="custom-control-input" name="terms" id="terms" {{ old('terms') ? 'checked' : '' }} required="required">--}}
{{--                                            <label class="custom-control-label terms" for="terms">{{ __('Terms of Service') }}</label>--}}
{{--                                        </div>--}}
{{--                                    </div>--}}

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-user w-100 mt-2">
                                            {{ __('Login') }}
                                        </button>
                                    </div>


                                </form>

                                <hr>

                                <div class="text-center mb-2">Login or Signup with</div>
                                <div class="row g-1">
                                    <div class="col-12">
                                        <a style="border-color: #000; color: #000" class="w-100 small btn" href="{{ route_lang('google.redirect') }}"><i class="fab fa-google"></i> Google</a>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
