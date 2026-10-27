{{-- Neutral "check your inbox" (Phase 4a · SG2). Says the SAME thing whether or not the email had a booking —
     no enumeration. Optional rate-limited resend. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/auth/sent.page_title'))

@section('card')
    <h1 class="vy-title">{{ __('guest/auth/sent.heading') }}</h1>
    <p class="vy-sub">{{ __('guest/auth/sent.subheading') }}</p>

    <div class="vy-notice">{{ __('guest/auth/sent.didnt_get_it') }}</div>

    @if (! empty($email))
        <form method="POST" action="{{ route('guest.claim.store') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="vy-btn">{{ __('guest/auth/sent.resend_link') }}</button>
        </form>
    @else
        <a class="vy-btn" href="{{ route('guest.claim') }}" style="text-decoration:none">{{ __('guest/auth/sent.try_again') }}</a>
    @endif

    <div class="vy-foot"><a class="vy-link" href="{{ route('guest.claim') }}">{{ __('guest/auth/sent.use_different_email') }}</a></div>
@endsection
