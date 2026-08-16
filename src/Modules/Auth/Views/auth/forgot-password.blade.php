@extends('auth::layouts.blank')

@section('title', __('Forgot Password'))

@section('main-content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 auth-card-narrow mx-auto">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <h4 class="mb-2">{{ __('Forgot your password?') }}</h4>
                    <p class="text-muted small mb-4">{{ __('Enter your email and we\'ll send you a reset link.') }}</p>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route_lang('password.email') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}" required autofocus autocomplete="email">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Send Reset Link') }}
                        </button>

                        <div class="text-center mt-4">
                            <a href="{{ route_lang('login') }}" class="small text-muted">{{ __('Back to login') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
