{{-- Set-password / complete-claim (Phase 4a · SG2). Reached via a valid signed link; POSTs back to the SAME
     signed URL so the `signed` middleware re-validates. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/auth/set-password.page_title'))

@section('card')
    <h1 class="vy-title">{{ __('guest/auth/set-password.heading', ['name' => $name]) }}</h1>
    <p class="vy-sub">{{ __('guest/auth/set-password.subheading') }}</p>

    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $action }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="password">{{ __('guest/auth/set-password.new_password') }}</label>
            <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" autofocus required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password_confirmation">{{ __('guest/auth/set-password.confirm_password') }}</label>
            <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="vy-btn">{{ __('guest/auth/set-password.submit') }}</button>
    </form>

    <div class="vy-foot">{{ \App\Support\Auth\PasswordPolicy::hint() }}</div>
@endsection
