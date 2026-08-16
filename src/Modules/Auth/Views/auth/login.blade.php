@extends('auth::layouts.blank')

@section('title', __('Login'))

@section('main-content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 auth-card mx-auto">
            <div class="card shadow-lg border-0">
                <div class="card-body p-0">
                    <div class="row g-0">

                        {{-- Logo / Brand --}}
                        <div class="col-lg-5 d-flex align-items-center justify-content-center bg-primary text-white rounded-start p-5">
                            <div class="text-center">
                                @if(config('layout.logo_login'))
                                    <img src="{{ config('layout.logo_login') }}" class="img-fluid mb-3" style="max-height:80px">
                                @else
                                    <h2 class="fw-bold mb-0">{{ config('app.name', 'Laravel') }}</h2>
                                @endif
                            </div>
                        </div>

                        {{-- Form --}}
                        <div class="col-lg-7 p-5">
                            <h4 class="mb-4">{{ __('Sign In') }}</h4>

                            @if($errors->any() || session('log_error'))
                                <div class="alert alert-danger">
                                    <ul class="mb-0 ps-3">
                                        @if(session('log_error'))
                                            <li>{{ session('log_error') }}</li>
                                        @else
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route_lang('login') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                                    <input type="email" id="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email') }}" required autofocus autocomplete="email">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('Password') }}</label>
                                    <input type="password" id="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        required autocomplete="current-password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if(Route::has('password.request'))
                                        <div class="mt-1 text-end">
                                            <a href="{{ route_lang('password.request') }}" class="small text-muted">
                                                {{ __('Forgot your password?') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-4 form-check">
                                    <input type="checkbox" id="remember" name="remember"
                                        class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                                    <label for="remember" class="form-check-label">{{ __('Remember Me') }}</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    {{ __('Login') }}
                                </button>

                                @if(Route::has('register'))
                                    <div class="text-center mt-4">
                                        <span class="text-muted small">{{ __("Don't have an account?") }}</span>
                                        <a href="{{ route_lang('register') }}" class="small ms-1">{{ __('Register') }}</a>
                                    </div>
                                @endif
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
