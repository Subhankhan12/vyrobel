{{-- Minimal authed guest landing (Phase 4a · SG2) — proof the claimed guest is logged in on the guest guard. The
     real Guest Portal (Trips, bookings, Account) is Phase 4b/4c. RAW-BLADE RULE: every `@` follows a space,
     newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Your account')

@section('card')
    <h1 class="vy-title">Welcome, {{ $name }}</h1>
    <p class="vy-sub">Your account is set up. Your trips and bookings will appear here soon.</p>

    <div class="vy-notice">You’re signed in to your Vyrobel guest account.</div>

    <a class="vy-btn" href="{{ route('guest.account') }}" style="text-decoration:none;margin-bottom:10px">Your account</a>

    <form method="POST" action="{{ route('guest.logout') }}">
        @csrf
        <button type="submit" class="vy-btn" style="background:none;color:var(--vy-accent);border:1px solid var(--vy-hairline);box-shadow:none">Sign out</button>
    </form>
@endsection
