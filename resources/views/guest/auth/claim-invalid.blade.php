{{-- Generic invalid link (Phase 4a · SG2) — one page for every reason, so nothing is enumerated.
     ⚠️ G11 CORRECTION — this docblock used to claim it was "the SAME page for any bad/expired/spent/mismatched
     link". It is NOT reached for an EXPIRED or TAMPERED or UNSIGNED link: the `signed` middleware rejects those
     with a 403 BEFORE this controller runs, so the guest gets the shared operator-flavoured 403 page instead
     (see DO-G11-1). What actually lands here is a link whose signature is VALID but whose row is no longer
     claimable — already claimed, the email changed so the hash no longer matches, or the booking is gone.
     ⚠️ Expiry is the COMMON case and it does not arrive here. RAW-BLADE RULE: `@` follows a space/newline/tag. --}}
@extends('guest.auth.layout')
@section('title', 'Link expired')

@section('card')
    <h1 class="vy-title">This link isn’t valid</h1>
    <p class="vy-sub">It may have expired or already been used. Request a fresh link to set up your account.</p>

    <a class="vy-btn" href="{{ route('guest.claim') }}" style="text-decoration:none">Request a new link</a>
@endsection
