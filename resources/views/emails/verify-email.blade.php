{{-- Guest email verification (Phase 3 · Sub-gate 4). Trigger deferred to Phase 4 (guest accounts).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Confirm your email address')
@section('preheader', 'Confirm your email address to finish setting up your Vyrobel account.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">One quick step</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Confirm your email</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, please confirm this is your email address so we can keep you updated about your bookings.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Confirm email</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#8A8079;">If the button doesn’t work, paste this link into your browser:</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; word-break:break-all;"><a href="{{ $verifyUrl }}" style="color:#C2410C; text-decoration:underline;">{{ $verifyUrl }}</a></p>
@endsection
