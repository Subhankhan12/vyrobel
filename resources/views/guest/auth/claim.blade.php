{{-- Guest claim / booking-tied sign-up (Phase 4a · SG2). Enter the email from a real booking; the response is
     always neutral (no enumeration). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Access your bookings')

@section('card')
    <h1 class="vy-title">Access your bookings</h1>
    <p class="vy-sub">Enter the email from your booking and we’ll send you a secure link to set up your account.</p>

    @if ($errors->any())
        <div class="vy-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.claim.store') }}" novalidate>
        @csrf
        <div class="vy-field">
            <label class="vy-label" for="email">Booking email</label>
            <input class="vy-input" type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus required>
        </div>
        <button type="submit" class="vy-btn">Send me a link</button>
    </form>

    <div class="vy-foot">Already set up? <a class="vy-link" href="{{ route('guest.login') }}">Sign in</a></div>
@endsection
