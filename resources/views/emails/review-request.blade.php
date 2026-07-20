{{-- Guest review request (Phase 3 · Sub-gate 4). Trigger deferred to Phase 5 (Reviews). Link only when it exists.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'How was your stay?')
@section('preheader', 'We’d love to hear how your stay at '.$propertyName.' went.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Your feedback</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">How was your stay, {{ $guestName }}?</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Thank you for staying at <strong style="color:#2B2320;">{{ $propertyName }}</strong>. We’d love to hear how it went — your feedback helps us and future guests.</p>

    @if ($reviewUrl)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
            <tr>
                <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                    <a href="{{ $reviewUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Leave a review</a>
                </td>
            </tr>
        </table>
    @else
        <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#4A4038;">Simply reply to this email and tell us about your stay — we read every message.</p>
    @endif

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">Thank you for choosing us.</p>
@endsection
