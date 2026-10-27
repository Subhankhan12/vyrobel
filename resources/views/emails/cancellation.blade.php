{{-- Guest cancellation (transactional-emails exact-match #109). Matched to "Cancellation Email.html", email-safe.
     HONEST (#28/#39): TWO cases — a real recorded refund, or an honest "no refund due" (a real refund fires its
     own RefundConfirmationMail; the deposit/non-refundable/waived split is not modelled and refused). No masked
     card (SAQ A) → "your original payment method". No fabricated promo. Source is the real category (#85).
     Customizable per #86 ($heading/$intro/$button + blocks). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/cancellation.title'))
@section('preheader', __('emails/cancellation.preheader', ['reference' => $reference, 'property' => $propertyName]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#B4443A;">{{ __('emails/cancellation.eyebrow') }}</p>
    <h1 style="margin:0 0 12px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 18px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; color:#4A4038;">{{ $intro }}</p>

    {{-- Cancelled-stay strip --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:14px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#5F5853; text-decoration:line-through;">{{ $propertyName }} · {{ $stay }}</span><br>
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#6C645F;">{{ trans_choice('emails/cancellation.nights', $nights, ['count' => $nights]) }} · {{ $guests }} · {{ $reference }}</span>
            </td>
        </tr>
    </table>

    @if ($reason)
        <p style="margin:0 0 16px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; line-height:1.55; color:#4A4038;">{!! __('emails/cancellation.reason_html', ['reason' => e($reason)]) !!}</p>
    @endif

    {{-- Refund summary (toggleable block #86) — TWO honest cases --}}
    @if ($blocks['refund_summary'] ?? true)
        @if ($refund)
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
                <tr>
                    <td align="center" style="padding:22px 16px;">
                        <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/cancellation.refund_issued') }}</span><br>
                        <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:30px; font-weight:700; color:#C2410C;">{{ $refund }}</span><br>
                        <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">{{ __('emails/cancellation.refund_to', ['method' => $refundMethod, 'eta' => $refundEta]) }}</span>
                    </td>
                </tr>
            </table>
        @else
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
                <tr>
                    <td style="padding:16px;">
                        <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#2B2320;">{{ __('emails/cancellation.no_refund') }}</span><br>
                        <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">{{ __('emails/cancellation.no_refund_body') }}</span>
                    </td>
                </tr>
            </table>
        @endif
    @endif

    <p style="margin:0 0 18px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#5F5853;">{{ $sourceLine }}.</p>

    {{-- Come-back invitation (toggleable block #86) — a warm invite + real link, never a fabricated promo --}}
    @if ($blocks['return_offer'] ?? true)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px dashed rgba(120,70,40,.22); border-radius:12px; margin:0 0 18px;">
            <tr>
                <td style="padding:16px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; color:#4A4038;">{{ __('emails/cancellation.return_offer') }}</td>
            </tr>
        </table>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;"><tr>
            <td align="center" style="background-color:#C2410C; border-radius:30px;"><a href="{{ $browseUrl }}" style="display:block; padding:15px 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14.5px; font-weight:600; color:#FFFFFF; text-decoration:none;">{{ $button }}</a></td>
        </tr></table>
    @endif

    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{!! __('emails/cancellation.help_html', ['url' => e($messagesUrl), 'property' => e($propertyName)]) !!}</p>
@endsection
