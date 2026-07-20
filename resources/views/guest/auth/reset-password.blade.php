{{-- Guest reset-password (Phase 4a · SG3) — token in a hidden field; the guest re-enters their email (never in
     the URL). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Choose a new password')

@section('card')
    <h1 class="vy-title">Choose a new password</h1>
    <p class="vy-sub">Confirm your email and set a new password.</p>

    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="vy-field">
            <label class="vy-label" for="email">Email</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password">New password</label>
            <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password_confirmation">Confirm password</label>
            <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="vy-btn">Reset password</button>
    </form>

    <div class="vy-foot">At least 8 characters.</div>
@endsection
