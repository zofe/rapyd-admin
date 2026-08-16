@extends('auth::layouts.blank')

@section('title', __('Two Factor Challenge'))

@section('main-content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 auth-card-narrow mx-auto">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <h4 class="mb-2">{{ __('Two-Factor Authentication') }}</h4>
                    <p class="text-muted small mb-4" id="2fa-hint">
                        {{ __('Please enter the authentication code from your app.') }}
                    </p>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('two-factor.login') }}" id="2fa-code-form">
                        @csrf
                        <div class="mb-4">
                            <label for="code" class="form-label">{{ __('Code') }}</label>
                            <input type="text" id="code" name="code" inputmode="numeric"
                                class="form-control" autofocus autocomplete="one-time-code">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            {{ __('Verify') }}
                        </button>
                    </form>

                    <div class="text-center">
                        <button type="button" class="btn btn-link small text-muted" onclick="toggleRecovery()">
                            {{ __('Use a recovery code instead') }}
                        </button>
                    </div>

                    <form method="POST" action="{{ route('two-factor.login') }}" id="2fa-recovery-form" class="d-none mt-3">
                        @csrf
                        <div class="mb-4">
                            <label for="recovery_code" class="form-label">{{ __('Recovery Code') }}</label>
                            <input type="text" id="recovery_code" name="recovery_code"
                                class="form-control" autocomplete="one-time-code">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Verify') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleRecovery() {
    document.getElementById('2fa-code-form').classList.toggle('d-none');
    document.getElementById('2fa-recovery-form').classList.toggle('d-none');
    const hint = document.getElementById('2fa-hint');
    hint.textContent = hint.textContent.includes('app')
        ? '{{ __("Use one of your emergency recovery codes.") }}'
        : '{{ __("Please enter the authentication code from your app.") }}';
}
</script>
@endsection
