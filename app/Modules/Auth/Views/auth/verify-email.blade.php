@extends('auth::blank')

@section('main-content')
    <x-rpd::card>
        <div class="text-center mb-4">
            <img src="{{ config('layout.logo_login') }}" class="img-fluid px-2" style="width: 450px;" alt="Logo"/>
        </div>

        <h4 style="font-size: 1.2rem; font-weight: 700!important; color:#3b3e42; margin-bottom: 1rem;">
            {{ __('Verify Your Email Address') }}
        </h4>

        <div class="card-body">
            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success" role="alert">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </div>
            @endif

            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}

            <form method="POST" action="{{ route_lang('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-link p-0 m-0 align-baseline pt-2 pb-2">{{ __('Resend Verification Email') }}</button>.
            </form>

            <form method="POST" action="{{ route_lang('logout') }}">
                @csrf

                <button type="submit" class="btn btn-secondary">
                    {{ __('Logout') }}
                </button>
            </form>
        </div>
    </x-rpd::card>
@endsection
