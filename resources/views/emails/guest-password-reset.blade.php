{{-- Guest password-reset email (Phase 4a · SG3). Token-only link (no email in the URL); the reset form asks for
     the email. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Reset your password')
@section('preheader', 'Reset the password for your Vyrobel guest account.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Password reset</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Reset your password</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, we received a request to reset the password for your Vyrobel guest account. Click below to choose a new one — the link expires in an hour.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Choose a new password</a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">If you didn’t request this, you can safely ignore this email — your password won’t change.</p>
@endsection
