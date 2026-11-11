{{-- Guest winback / re-engagement (Phase 3 · Sub-gate 4). Trigger deferred (scheduled sweep). No fake offers.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', __('emails/winback.page_title'))
@section('preheader', __('emails/winback.preheader'))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/winback.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ __('emails/winback.heading') }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ __('emails/winback.body', ['name' => $guestName, 'place' => $place]) }}</p>

    {{-- D-REVIEW-REPLY: only when a reply reaches the property (its Reply-To is set) --}}
    @if (! empty($replyTo))
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{{ __('emails/winback.reply_any_time') }}</p>
    @endif
@endsection

{{-- MARKETING footer: a working, signed opt-out that needs no login. Rendered ONLY when the sender supplied a
     URL, so this template can never claim an unsubscribe it cannot honour. --}}
@if (! empty($unsubscribeUrl))
    @section('footer_marketing')
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">{!! __('emails/winback.unsubscribe_note', ['url' => e($unsubscribeUrl)]) !!}</p>
    @endsection
@endif
