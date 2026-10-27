{{-- Guest payment reminder (transactional-emails exact-match). Matched to "Payment Reminder Email.html" — the
     real Total / Paid / Balance (FolioTotals; Total − Paid == Balance, #17) + a real Pay-now button. Email-safe:
     inline styles, table layout, bulletproof button. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/payment-reminder.page_title'))
@section('preheader', __('emails/payment-reminder.preheader', ['balance' => $balance, 'unit' => $unitName]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/payment-reminder.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $intro }}</p>

    {{-- Total / Paid / Balance — the real FolioTotals figures (toggleable block #86) --}}
    @if ($blocks['totals'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        @if ($total !== null)
            <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/payment-reminder.total_stay') }}</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320; float:right;">{{ $total }}</span>
                </td>
            </tr>
            <tr>
                <td style="padding:13px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/payment-reminder.paid_so_far') }}</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#2B2320; float:right;">{{ $paid }}</span>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:15px 16px; background-color:#FBF1E9;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#704204;">{{ __('emails/payment-reminder.balance_due') }}</span>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:19px; font-weight:700; color:#2B2320; float:right;">{{ $balance }}</span><br>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#5F5853;">{{ __('emails/payment-reminder.payable_at_checkin') }}</span>
            </td>
        </tr>
    </table>
    @endif

    {{-- Pay now --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                    <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                        <a href="{{ $payUrl }}" target="_blank" style="display:inline-block; padding:13px 32px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">{{ $button }}</a>
                    </td>
                </tr></table>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:10px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#5F5853;">{{ __('emails/payment-reminder.secure_payment') }}</td>
        </tr>
    </table>

    {{-- Booking detail (toggleable block #86) --}}
    @if ($blocks['booking_detail'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:20px 0 20px;">
        <tr>
            <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/payment-reminder.arriving') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $arriving }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/payment-reminder.unit') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $unitName }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/payment-reminder.reference') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $reference }}</span>
            </td>
        </tr>
    </table>
    @endif

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{!! __('emails/payment-reminder.no_rush', ['url' => e($messagesUrl)]) !!}</p>
@endsection
