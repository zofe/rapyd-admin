@extends('auth::layouts.blank')

@section('title', __('Verify Email'))

@section('main-content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 auth-card-narrow mx-auto">
            <div class="card shadow border-0">
                <div class="card-body p-5 text-center">
                    <h4 class="mb-3">{{ __('Verify Your Email') }}</h4>
                    <p class="text-muted mb-4">
                        {{ __('Thanks for signing up! Please verify your email by clicking the link we sent you.') }}
                    </p>

                    @if(session('status') === 'verification-link-sent')
                        <div class="alert alert-success mb-4">
                            {{ __('A new verification link has been sent.') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            {{ __('Resend Verification Email') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-link text-muted small">
                            {{ __('Log Out') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
