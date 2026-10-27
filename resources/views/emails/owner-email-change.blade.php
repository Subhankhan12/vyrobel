{{-- Owner: confirm your new login email. Opening the link is what switches the email.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'email_change')

@section('title', __('emails/owner-email-change.title'))
@section('preheader', __('emails/owner-email-change.preheader'))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/owner-email-change.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ __('emails/owner-email-change.heading') }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{!! __('emails/owner-email-change.body_html', ['name' => e($userName), 'email' => e($newEmail)]) !!}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">{{ __('emails/owner-email-change.button') }}</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 14px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">{{ __('emails/owner-email-change.expiry') }}</p>
    <p style="margin:0 0 4px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; color:#5F5853;">{{ __('emails/owner-email-change.paste_link') }}</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.5; word-break:break-all;"><a href="{{ $verifyUrl }}" style="color:#C2410C; text-decoration:underline;">{{ $verifyUrl }}</a></p>
@endsection
