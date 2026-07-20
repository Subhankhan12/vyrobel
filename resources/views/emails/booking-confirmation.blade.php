{{-- Guest booking confirmation (Phase 3 · Sub-gate 4). Real reservation data; integer money via MoneyFormatter.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Your booking is confirmed')
@section('preheader', 'Your stay at '.$propertyName.' is confirmed — '.$checkIn.' to '.$checkOut.'.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Booking confirmed</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">You’re all set, {{ $guestName }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">We’ve confirmed your stay at <strong style="color:#2B2320;">{{ $propertyName }}</strong>. Here are the details — keep this for your records.</p>

    {{-- Stay details --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Confirmation</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#2B2320;">{{ $reference }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Check-in</span><br>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320;">{{ $checkIn }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Check-out</span><br>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320;">{{ $checkOut }} · {{ $nights }} {{ \Illuminate\Support\Str::plural('night', $nights) }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Total</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:18px; font-weight:700; color:#2B2320;">{{ $total }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">We look forward to welcoming you. If you need to change anything, just reply to this email.</p>
@endsection
