{{-- Operator → guest free-text message (#87). Carries exactly the operator's typed body — no fabricated copy.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', 'A message from your host')
@section('preheader', 'You have a new message from your host.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">A message for you</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Hi {{ $guestName }},</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; color:#4A4038; white-space:pre-line;">{{ $bodyText }}</p>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">Just reply to this email and we’ll see it{{ $propertyName ? ' — '.$propertyName : '' }}.</p>
@endsection

{{-- MARKETING footer: a working, signed opt-out that needs no login. Rendered ONLY when the sender supplied a
     URL, so this template can never claim an unsubscribe it cannot honour. --}}
@if (! empty($unsubscribeUrl))
    @section('footer_marketing')
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">You're receiving this because you opted in to hear from us. <a href="{{ $unsubscribeUrl }}" style="color:#6C645F; text-decoration:underline;">Unsubscribe</a> at any time — you'll still get emails about your bookings.</p>
    @endsection
@endif
