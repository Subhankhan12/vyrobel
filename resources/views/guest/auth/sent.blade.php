{{-- Neutral "check your inbox" (Phase 4a · SG2). Says the SAME thing whether or not the email had a booking —
     no enumeration. Optional rate-limited resend. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Check your inbox')

@section('card')
    <h1 class="vy-title">Check your inbox</h1>
    <p class="vy-sub">If this email has a booking with us, we’ve just sent a secure link to set up your account. It expires in an hour.</p>

    <div class="vy-notice">Didn’t get it? Check your spam folder, or request another link below.</div>

    @if (! empty($email))
        <form method="POST" action="{{ route('guest.claim.store') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="vy-btn">Resend link</button>
        </form>
    @else
        <a class="vy-btn" href="{{ route('guest.claim') }}" style="text-decoration:none">Try again</a>
    @endif

    <div class="vy-foot"><a class="vy-link" href="{{ route('guest.claim') }}">Use a different email</a></div>
@endsection
