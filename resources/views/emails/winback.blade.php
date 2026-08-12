{{-- Guest winback / re-engagement (Phase 3 · Sub-gate 4). Trigger deferred (scheduled sweep). No fake offers.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'We’d love to welcome you back')
@section('preheader', 'It’s been a while — we’d love to welcome you back.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">We’ve missed you</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">We’d love to welcome you back</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, it’s been a while since your last stay{{ $place }}. Whenever you’re planning your next trip, we’d be delighted to host you again.</p>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">Reply any time and we’ll help you find the perfect dates.</p>
@endsection

{{-- MARKETING footer: a working, signed opt-out that needs no login. Rendered ONLY when the sender supplied a
     URL, so this template can never claim an unsubscribe it cannot honour. --}}
@if (! empty($unsubscribeUrl))
    @section('unsubscribe')
        <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.5; color:#6C645F;">You're receiving this because you opted in to hear from us. <a href="{{ $unsubscribeUrl }}" style="color:#6C645F; text-decoration:underline;">Unsubscribe</a> at any time — you'll still get emails about your bookings.</p>
    @endsection
@endif
