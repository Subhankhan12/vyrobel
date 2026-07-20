{{-- Guest payment reminder (Phase 3 · Sub-gate 4). Real outstanding balance via MoneyFormatter.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'A balance is due before your stay')
@section('preheader', 'A balance of '.$balance.' is due before your stay at '.$propertyName.'.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Payment reminder</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">A balance is due before you arrive</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, we’re looking forward to your stay at <strong style="color:#2B2320;">{{ $propertyName }}</strong> on {{ $checkIn }}. There’s an outstanding balance on your booking <strong style="color:#2B2320;">{{ $reference }}</strong>.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 22px;">
        <tr>
            <td align="center" style="padding:22px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Balance due</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:26px; font-weight:700; color:#2B2320;">{{ $balance }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">You can settle this on arrival, or reply to this email and we’ll send you a secure payment link.</p>
@endsection
