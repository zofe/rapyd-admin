@extends('auth::blank')

@section('main-content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-5">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="p-5">


{{--                                    <div class="text-center">--}}
{{--                                        <h1 class="h4 text-gray-900 mb-4">{{ __('Verify Your Email Address') }}</h1>--}}
{{--                                    </div>--}}



                                    @if (session('status') == 'verification-link-sent')
                                        <div class="alert alert-success" role="alert">
                                            {{ __('2fa.new_link_email') }}
                                        </div>
                                    @endif


                                    <div class="py-4">
                                        {{ __('2fa.confirm_using_code') }}
                                    </div>


                                    <form method="POST" action="{{ url('two-factor-challenge') }}">
                                        @csrf

                                        <div class="form-group row">
                                            <label for="code" class="col-md-4 col-form-label text-md-right">{{ __('Code') }}</label>

                                            <div class="col-md-6">
                                                <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" name="code" value="{{ old('code') }}"  autocomplete="code" autofocus>
            {{--                                    <span class="text-muted">{{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}</span>--}}
                                                @error('code')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="small offset-md-2"> {{ __('2fa.or_use_a') }}:</div>
                                        <div class="form-group row">
                                            <label for="recovery_code" class="col-md-4 col-form-label text-md-right">{{ __('Recovery Code') }}</label>

                                            <div class="col-md-6">
                                                <input id="recovery_code" type="text" class="form-control @error('recovery_code') is-invalid @enderror" name="recovery_code" value="{{ old('recovery_code') }}"  autocomplete="recovery_code" autofocus>
                                                @error('recovery_code')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="form-group row mb-0">
                                            <div class="col-md-6 offset-md-4">
                                                <button type="submit" class="btn btn-primary">
                                                    {{ __('Login') }}
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
