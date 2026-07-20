{{-- Guest refund confirmation (Phase 3 · Sub-gate 4). Real refund amount via MoneyFormatter.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')

@section('title', 'Your refund has been processed')
@section('preheader', 'We’ve processed a refund of '.$amount.' to your original payment method.')

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">Refund processed</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">Your refund is on its way</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">Hi {{ $guestName }}, we’ve processed your refund for booking <strong style="color:#2B2320;">{{ $reference }}</strong>. It will be returned to your original payment method.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td align="center" style="padding:22px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#8A8079;">Amount refunded</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:26px; font-weight:700; color:#2B2320;">{{ $amount }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#8A8079;">Refunds typically appear within 5–10 business days, depending on your bank. Any questions? Just reply to this email.</p>
@endsection
