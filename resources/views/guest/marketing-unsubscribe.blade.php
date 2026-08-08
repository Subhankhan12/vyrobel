{{-- MARKETING unsubscribe — PUBLIC, no login, no realm (wireframe-parity sweep · HIGH-5).
     Reached only from a signed link in a marketing email. Shows NO guest data (#28) — an unsubscribe link gets
     forwarded and lands in shared inboxes, so it must not disclose a name or address. Says plainly that
     transactional mail continues, rather than implying booking confirmations have been silenced.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', 'Email preferences')

@section('card')
    @if ($done)
        <h1 class="vy-title">You're unsubscribed</h1>
        <p class="vy-sub">We won't send you any more marketing emails.</p>
        <div class="vy-notice">
            You'll still receive emails about your bookings — confirmations, receipts and arrival details. Those
            aren't marketing, so they can't be turned off here.
        </div>
    @else
        <h1 class="vy-title">Unsubscribe from marketing emails</h1>
        <p class="vy-sub">Stop receiving offers and news from us. This takes effect immediately.</p>

        <form method="POST" action="{{ $confirmUrl }}">
            <button class="vy-btn" type="submit" style="width:100%">Unsubscribe</button>
        </form>

        <p class="vy-sub" style="margin-top:14px">
            You'll still receive emails about your bookings — confirmations, receipts and arrival details.
        </p>
    @endif
@endsection
