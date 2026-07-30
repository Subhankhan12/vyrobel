{{-- Guest payment reminder (transactional-emails exact-match). Matched to "Payment Reminder Email.html" — the
     real Total / Paid / Balance (FolioTotals; Total − Paid == Balance, #17) + a real Pay-now button. Email-safe:
     inline styles, table layout, bulletproof button. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'A balance is due before your stay')
@section('preheader', 'A balance of '.$balance.' is due before your stay at '.$unitName.'.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Before you arrive</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Just one thing before you arrive</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">We’re looking forward to welcoming you to <strong style="color:#2B2320;">{{ $unitName }}</strong>. There’s a small balance left on your booking — settling it now means you skip the desk and head straight in.</p>

    {{-- Total / Paid / Balance — the real FolioTotals figures --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        @if ($total !== null)
            <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Total stay</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320; float:right;">{{ $total }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Paid so far</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320; float:right;">{{ $paid }}</span>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:15px 16px; background-color:#FBF1E9;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#B26A08;">Balance due</span>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:19px; font-weight:700; color:#2B2320; float:right;">{{ $balance }}</span><br>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#8A8079;">Otherwise payable at check-in</span>
            </td>
        </tr>
    </table>

    {{-- Pay now --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                    <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                        <a href="{{ $payUrl }}" target="_blank" style="display:inline-block; padding:13px 32px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">Pay {{ $balance }} now · skip the desk</a>
                    </td>
                </tr></table>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:10px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#8A8079;">Secure payment · Visa, Mastercard &amp; Apple Pay · receipt by email</td>
        </tr>
    </table>

    {{-- Booking detail --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:20px 0 20px;">
        <tr>
            <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Arriving</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $arriving }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Unit</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $unitName }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Reference</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $reference }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">No rush — if you’d rather pay on arrival, that’s completely fine. Card and cash are both welcome at the desk. Questions about your balance? Just <a href="{{ $messagesUrl }}" style="color:#C2410C; text-decoration:underline;">message us</a>.</p>
@endsection
