{{-- Guest refund confirmation (transactional-emails exact-match). Matched to "Refund Confirmation Email.html" —
     real amount + a refund-status timeline. Email-safe: inline styles, table layout. HONEST: destination is
     "your original payment method" (no card_last4 stored — #28), reason only when set, ETA a range not a date
     (#39). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/refund-confirmation.page_title'))
@section('preheader', __('emails/refund-confirmation.preheader', ['amount' => $amount]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#1B6B48;">{{ __('emails/refund-confirmation.eyebrow') }}</p>
    <h1 style="margin:0 0 14px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $intro }}</p>

    {{-- Amount --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td align="center" style="padding:22px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/refund-confirmation.amount_refunded') }}</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:30px; font-weight:700; color:#2B2320;">{{ $amount }}</span><br>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">{{ __('emails/refund-confirmation.to_method', ['method' => $method]) }}</span>
            </td>
        </tr>
    </table>

    {{-- Detail rows (toggleable block #86) --}}
    @if ($blocks['detail_rows'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        @if ($reason)
            <tr>
                <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/refund-confirmation.reason') }}</span>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $reason }}</span>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding:12px 16px; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/refund-confirmation.reference') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $reference }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:12px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/refund-confirmation.expected_arrival') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#2B2320; float:right;">{{ $etaLabel }}</span>
            </td>
        </tr>
    </table>
    @endif

    {{-- Refund status timeline (toggleable block #86) — Requested + Processed are real; Arriving is the honest range --}}
    @if ($blocks['status_timeline'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
        <tr>
            <td style="padding:0 0 6px;"><span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; letter-spacing:.06em; text-transform:uppercase; color:#5F5853;">{{ __('emails/refund-confirmation.refund_status') }}</span></td>
        </tr>
        <tr>
            <td style="padding:8px 0; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#1B6B48;">● {{ __('emails/refund-confirmation.status_requested') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5F5853; float:right;">{{ $processedOn }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0; border-bottom:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#1B6B48;">● {{ __('emails/refund-confirmation.status_processed') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5F5853; float:right;">{{ $processedOn }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:8px 0;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#5F5853;">○ {{ __('emails/refund-confirmation.status_arriving') }}</span>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5F5853; float:right;">{{ $etaLabel }}</span>
            </td>
        </tr>
    </table>
    @endif

    <p style="margin:0 0 16px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5F5853;">{{ $propertyName }}@if ($stay) · {{ $stay }}@endif</p>

    <p style="margin:0 0 16px;"><a href="{{ $reservationUrl }}" style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; font-weight:600; color:#C2410C; text-decoration:none;">{{ $button }} ›</a></p>

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{!! __('emails/refund-confirmation.closing', ['url' => e($messagesUrl)]) !!}</p>
@endsection
