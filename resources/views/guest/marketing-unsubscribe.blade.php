{{-- MARKETING unsubscribe — PUBLIC, no login, no realm (wireframe-parity sweep · HIGH-5).
     Reached only from a signed link in a marketing email. Shows NO guest data (#28) — an unsubscribe link gets
     forwarded and lands in shared inboxes, so it must not disclose a name or address. Says plainly that
     transactional mail continues, rather than implying booking confirmations have been silenced.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('guest.auth.layout')
@section('title', __('guest/marketing-unsubscribe.page_title'))

@section('card')
    @if ($done)
        <h1 class="vy-title">{{ __('guest/marketing-unsubscribe.done_heading') }}</h1>
        <p class="vy-sub">{{ __('guest/marketing-unsubscribe.done_sub') }}</p>
        <div class="vy-notice">
            {{ __('guest/marketing-unsubscribe.done_notice') }}
        </div>
    @else
        <h1 class="vy-title">{{ __('guest/marketing-unsubscribe.heading') }}</h1>
        <p class="vy-sub">{{ __('guest/marketing-unsubscribe.sub') }}</p>

        <form method="POST" action="{{ $confirmUrl }}">
            <button class="vy-btn" type="submit" style="width:100%">{{ __('guest/marketing-unsubscribe.unsubscribe_button') }}</button>
        </form>

        <p class="vy-sub" style="margin-top:14px">
            {{ __('guest/marketing-unsubscribe.transactional_note') }}
        </p>
    @endif
@endsection
