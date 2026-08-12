{{-- Owner: confirm your new login email. Opening the link is what switches the email.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Confirm your new email address')
@section('preheader', 'Confirm this address to finish changing your Vyrobel login email.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Email change</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Confirm your new email</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $userName }}, you asked to change your Vyrobel login email to <strong>{{ $newEmail }}</strong>. Confirm below to make the switch — your current email stays active until you do.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Confirm new email</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">This link expires in an hour and can be used once. If you didn’t request this, you can ignore it — nothing changes.</p>
    <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">If the button doesn’t work, paste this link into your browser:</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; word-break:break-all;"><a href="{{ $verifyUrl }}" style="color:#C2410C; text-decoration:underline;">{{ $verifyUrl }}</a></p>
@endsection
