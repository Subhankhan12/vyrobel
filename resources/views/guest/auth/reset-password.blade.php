{{-- Guest reset-password (Phase 4a · SG3) — token in a hidden field; the guest re-enters their email (never in
     the URL). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/auth/reset-password.page_title'))

@section('card')
    <h1 class="vy-title">{{ __('guest/auth/reset-password.heading') }}</h1>
    <p class="vy-sub">{{ __('guest/auth/reset-password.subheading') }}</p>

    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="vy-field">
            <label class="vy-label" for="email">{{ __('common.email') }}</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password">{{ __('guest/auth/reset-password.new_password') }}</label>
            <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password_confirmation">{{ __('guest/auth/reset-password.confirm_password') }}</label>
            <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="vy-btn">{{ __('guest/auth/reset-password.reset_password') }}</button>
    </form>

    <div class="vy-foot">{{ \App\Support\Auth\PasswordPolicy::hint() }}</div>
@endsection
