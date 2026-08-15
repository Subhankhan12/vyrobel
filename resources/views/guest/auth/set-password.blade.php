{{-- Set-password / complete-claim (Phase 4a · SG2). Reached via a valid signed link; POSTs back to the SAME
     signed URL so the `signed` middleware re-validates. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Set your password')

@section('card')
    <h1 class="vy-title">Welcome, {{ $name }}</h1>
    <p class="vy-sub">Your email is confirmed. Set a password to finish setting up your account.</p>

    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $action }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="password">New password</label>
            <input class="vy-input" type="password" id="password" name="password" autocomplete="new-password" autofocus required>
        </div>
        <div class="vy-field">
            <label class="vy-label" for="password_confirmation">Confirm password</label>
            <input class="vy-input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
        </div>
        <button type="submit" class="vy-btn">Set password &amp; continue</button>
    </form>

    <div class="vy-foot">{{ \App\Support\Auth\PasswordPolicy::hint() }}</div>
@endsection
