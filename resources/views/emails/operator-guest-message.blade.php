{{-- Operator → guest free-text message (#87). Carries exactly the operator's typed body — no fabricated copy.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'A message from your host')
@section('preheader', 'You have a new message from your host.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">A message for you</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Hi {{ $guestName }},</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; color:#4A4038; white-space:pre-line;">{{ $bodyText }}</p>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">Just reply to this email and we’ll see it{{ $propertyName ? ' — '.$propertyName : '' }}.</p>
@endsection
