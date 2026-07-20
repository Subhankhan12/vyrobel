{{-- Guest forgot-password (Phase 4a · SG3) — neutral response, no enumeration.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Reset password')

@section('card')
    <h1 class="vy-title">Reset your password</h1>
    <p class="vy-sub">Enter your email and we’ll send you a link to choose a new password.</p>

    @if (session('status'))
        <div class="vy-notice">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.password.email') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="email">Email</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <button type="submit" class="vy-btn">Send reset link</button>
    </form>

    <div class="vy-foot"><a class="vy-link" href="{{ route('guest.login') }}">Back to sign in</a></div>
@endsection
