{{-- Owner/operator NOTIFICATION email (Phase 3 · Sub-gate 5) — the email form of an in-app feed row, gated by
     the user's `email` preference. Same title/context/link, on the shared brand layout.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', $title)
@section('preheader', $context)

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ \App\Support\Notifications\NotificationFeed::categoryLabel($category) }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:23px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $title }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $context }}</p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
        <tr>
            <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                <a href="{{ $url }}" target="_blank" style="display:inline-block; padding:12px 28px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">{{ __('emails/notification.button') }}</a>
            </td>
        </tr>
    </table>

    <p style="margin:18px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; line-height:1.5; color:#5F5853;">{{ __('emails/notification.reason') }}</p>
@endsection
