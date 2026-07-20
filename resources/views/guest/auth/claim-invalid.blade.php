{{-- Generic invalid/expired link (Phase 4a · SG2) — the SAME page for any bad/expired/spent/mismatched link
     (no enumeration). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Link expired')

@section('card')
    <h1 class="vy-title">This link isn’t valid</h1>
    <p class="vy-sub">It may have expired or already been used. Request a fresh link to set up your account.</p>

    <a class="vy-btn" href="{{ route('guest.claim') }}" style="text-decoration:none">Request a new link</a>
@endsection
