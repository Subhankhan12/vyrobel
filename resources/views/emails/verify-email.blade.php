{{-- Guest email verification (transactional-emails exact-match). Matched to "Verify Email.html" — the real signed
     claim link (booking-tied). Email-safe: inline styles, table layout, bulletproof button. HONEST: the expiry is
     the REAL 1 hour (not the wireframe's 24h), and the "€X credit is waiting" teaser is OMITTED — there is no
     welcome-credit feature, so nothing is fabricated (#39). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Confirm your email address')
@section('preheader', 'Confirm your email to activate your account.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Confirm to activate</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $intro }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 12px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:13px 34px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">{{ $button }}</a>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">This link expires in {{ $expiresLabel }}.</p>

    {{-- Copy-paste link (toggleable block #86) --}}
    @if ($blocks['copy_link'] ?? true)
        <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">Button not working? Copy and paste this link into your browser:</p>
        <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; word-break:break-all;"><a href="{{ $verifyUrl }}" style="color:#C2410C; text-decoration:underline;">{{ $verifyUrl }}</a></p>
    @endif

    <hr style="border:none; border-top:1px solid #EFE3D9; margin:0 0 14px;">
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.55; color:#5F5853;">Didn’t request this? You can safely ignore this email — nothing will be activated and no account is created until it’s verified.</p>
@endsection
