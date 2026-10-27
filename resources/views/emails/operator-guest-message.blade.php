{{-- Operator → guest free-text message (#87). Carries exactly the operator's typed body — no fabricated copy.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/operator-guest-message.title'))
@section('preheader', __('emails/operator-guest-message.preheader'))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/operator-guest-message.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ __('emails/operator-guest-message.greeting', ['name' => $guestName]) }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; color:#4A4038; white-space:pre-line;">{{ $bodyText }}</p>

    {{-- ⚠️ 2026-10-05: only when a reply reaches the property (its reply-to address) — without one it went to Vyrobel's sender --}}
    @if (! empty($replyTo))
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{{ $propertyName ? __('emails/operator-guest-message.reply_hint_property', ['property' => $propertyName]) : __('emails/operator-guest-message.reply_hint') }}</p>
    @endif
@endsection

{{-- MARKETING footer: a working, signed opt-out that needs no login. Rendered ONLY when the sender supplied a
     URL, so this template can never claim an unsubscribe it cannot honour. --}}
@if (! empty($unsubscribeUrl))
    @section('footer_marketing')
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">{!! __('emails/operator-guest-message.marketing_footer_html', ['url' => e($unsubscribeUrl)]) !!}</p>
    @endsection
@endif
