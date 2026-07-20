{{-- Guest cancellation (Phase 3 · Sub-gate 4). Real reason + real refund (only when one was issued).
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Your booking has been cancelled')
@section('preheader', 'Your booking '.$reference.' at '.$propertyName.' has been cancelled.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#B4443A;">Booking cancelled</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Your booking is cancelled</h1>
    <p style="margin:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, we’re confirming that your booking <strong style="color:#2B2320;">{{ $reference }}</strong> at {{ $propertyName }} has been cancelled.</p>

    @if ($reason)
        <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#4A4038;">Reason: <em style="color:#2B2320;">{{ $reason }}</em></p>
    @endif

    @if ($refund)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
            <tr>
                <td style="padding:16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Refund issued</span><br>
                    <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:18px; font-weight:700; color:#2B2320;">{{ $refund }}</span><br>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#8A8079;">This will appear on your statement within a few business days.</span>
                </td>
            </tr>
        </table>
    @else
        <p style="margin:0 0 20px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; line-height:1.55; color:#8A8079;">If any payment is due to be refunded, we’ll confirm it separately.</p>
    @endif

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">We hope to welcome you another time. If this wasn’t expected, just reply to this email.</p>
@endsection
