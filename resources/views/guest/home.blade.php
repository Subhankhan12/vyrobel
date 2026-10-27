{{-- Minimal authed guest landing (Phase 4a · SG2) — proof the claimed guest is logged in on the guest guard. The
     real Guest Portal (Trips, bookings, Account) is Phase 4b/4c. RAW-BLADE RULE: every `@` follows a space,
     newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/home.your_account'))

@section('card')
    <h1 class="vy-title">{{ __('guest/home.welcome', ['name' => $name]) }}</h1>
    <p class="vy-sub">{{ __('guest/home.subheading') }}</p>

    <div class="vy-notice">{{ __('guest/home.signed_in_notice') }}</div>

    <a class="vy-btn" href="{{ route('guest.account') }}" style="text-decoration:none;margin-bottom:10px">{{ __('guest/home.your_account') }}</a>

    <form method="POST" action="{{ route('guest.logout') }}">
        @csrf
        <button type="submit" class="vy-btn" style="background:none;color:var(--vy-accent-text);border:1px solid var(--vy-hairline);box-shadow:none">{{ __('common.sign_out') }}</button>
    </form>
@endsection
