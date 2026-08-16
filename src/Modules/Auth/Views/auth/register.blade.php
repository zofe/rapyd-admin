@extends('auth::layouts.blank')

@section('title', __('Register'))

@section('main-content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 auth-card-narrow mx-auto">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <h4 class="mb-4">{{ __('Create Account') }}</h4>

                    <form method="POST" action="{{ route_lang('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }}</label>
                            <input type="text" id="name" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required autofocus autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" required autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input type="password" id="password" name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" required autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Register') }}
                        </button>

                        @if(Route::has('login'))
                            <div class="text-center mt-4">
                                <span class="text-muted small">{{ __('Already have an account?') }}</span>
                                <a href="{{ route_lang('login') }}" class="small ms-1">{{ __('Sign in') }}</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
