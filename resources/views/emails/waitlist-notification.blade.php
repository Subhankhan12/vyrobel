{{-- Guest waitlist notification (Phase 3 · Sub-gate 4). Trigger deferred to Phase 4 (waitlist feature).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'waitlist')

@section('title', 'A room just opened up')
@section('preheader', 'Good news — a room has opened up at '.$propertyName.' for '.$dates.'.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Good news</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">A room just opened up</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, a room you were waiting for at <strong style="color:#2B2320;">{{ $propertyName }}</strong> is now available for <strong style="color:#2B2320;">{{ $dates }}</strong>. These go quickly — book soon to secure it.</p>

    @if ($bookUrl)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
            <tr>
                <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                    <a href="{{ $bookUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Book now</a>
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">If you’re no longer interested, no action is needed.</p>
@endsection
