{{-- Guest booking confirmation (transactional-emails exact-match #109). Matched to "Booking Confirmation
     Email.html", email-safe (table + inline CSS, web-safe fonts). RECONCILING (#17): the price-breakdown lines
     are the REAL folio items, the grand Total is `total_minor`. HONEST (#28/#39): no masked card (SAQ A) — the
     paid-with row is "your original payment method" + a real receipt link; the door code is a 48h POINTER only
     (#33, never the code); calendar links from real dates; source is the real category (#85). Customizable per
     #86 ($heading/$intro/$button + toggleable blocks). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/booking-confirmation.title'))
@section('preheader', __('emails/booking-confirmation.preheader', ['property' => $propertyName, 'check_in' => $checkIn, 'check_out' => $checkOut]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/booking-confirmation.eyebrow') }}</p>
    <h1 style="margin:0 0 12px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.6; color:#4A4038;">{{ $intro }}</p>

    {{-- Dates block --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:14px; margin:0 0 18px;">
        <tr>
            <td width="50%" style="padding:16px 18px; background-color:#FCF7F2; border-right:1px solid #F2E8DF;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#6C645F;">{{ __('emails/booking-confirmation.check_in') }}</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:16px; font-weight:600; color:#2B2320;">{{ $checkIn }}</span>
                @if ($checkInTime)<br><span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#5F5853;">{{ $checkInTime }}</span>@endif
            </td>
            <td width="50%" style="padding:16px 18px; background-color:#FCF7F2;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#6C645F;">{{ __('emails/booking-confirmation.check_out') }}</span><br>
                <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:16px; font-weight:600; color:#2B2320;">{{ $checkOut }}</span>
                @if ($checkOutTime)<br><span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#5F5853;">{{ $checkOutTime }}</span>@endif
            </td>
        </tr>
    </table>

    {{-- Facts row --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
        <tr>
            <td style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#4A4038;">
                <strong style="color:#2B2320;">{{ $guests }}</strong> &nbsp;·&nbsp; {!! trans_choice('emails/booking-confirmation.nights_html', $nights, ['count' => (int) $nights]) !!} &nbsp;·&nbsp; {!! __('emails/booking-confirmation.reference_html', ['reference' => e($reference)]) !!}
            </td>
        </tr>
    </table>

    {{-- Price breakdown (toggleable block #86) — real folio lines + reconciling Total (#17) --}}
    @if ($blocks['price_breakdown'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8F1EA; border-radius:14px; margin:0 0 20px;">
        <tr>
            <td style="padding:18px 20px;">
                @foreach ($lines as $line)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5C534C; padding:0 0 8px;">{{ $line['label'] }}</td>
                        <td align="right" style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:{{ $line['muted'] ? '#5F5853' : '#2B2320' }}; padding:0 0 8px; font-variant-numeric:tabular-nums;">{{ $line['amount'] }}</td>
                    </tr></table>
                @endforeach
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid rgba(120,70,40,.16);"><tr>
                    <td style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:14px; font-weight:600; color:#2B2320; padding:12px 0 0;">{{ __('emails/booking-confirmation.total') }}</td>
                    <td align="right" style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:19px; font-weight:600; color:#C2410C; padding:12px 0 0; font-variant-numeric:tabular-nums;">{{ $total }}</td>
                </tr></table>
                <p style="margin:12px 0 0; padding-top:12px; border-top:1px dashed rgba(120,70,40,.2); font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{!! __('emails/booking-confirmation.paid_with_html', ['url' => e($receiptUrl)]) !!}</p>
            </td>
        </tr>
    </table>
    @endif

    {{-- Primary + secondary buttons --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 10px;"><tr>
        <td align="center" style="background-color:#C2410C; border-radius:30px;"><a href="{{ $reservationUrl }}" style="display:block; padding:15px 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14.5px; font-weight:600; color:#FFFFFF; text-decoration:none;">{{ $button }}</a></td>
    </tr></table>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;"><tr>
        <td align="center" style="background-color:#FFFFFF; border:1px solid #EFE3D9; border-radius:30px;"><a href="{{ $arrivalUrl }}" style="display:block; padding:13px 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; font-weight:600; color:#2B2320; text-decoration:none;">{{ __('emails/booking-confirmation.arrival_button') }}</a></td>
    </tr></table>

    {{-- Before you arrive (toggleable block #86) — the door code is a POINTER only (#33) --}}
    @if ($blocks['before_you_arrive'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #EFE3D9; margin:0 0 8px;">
        <tr><td style="padding:18px 0 10px;"><span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:10.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#5F5853;">{{ __('emails/booking-confirmation.before_you_arrive') }}</span></td></tr>
        <tr><td style="padding:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; color:#2B2320;">{!! __('emails/booking-confirmation.checkin_online_html', ['url' => e($checkinUrl)]) !!}</td></tr>
        <tr><td style="padding:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13.5px; color:#2B2320;">{!! __('emails/booking-confirmation.door_code_html') !!}</td></tr>
    </table>
    @endif

    {{-- Host signature --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F8F1EA; border-radius:12px; margin:20px 0 0;">
        <tr>
            <td style="padding:14px 16px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#2B2320;">{!! __('emails/booking-confirmation.questions_html', ['url' => e($messagesUrl)]) !!}<br><span style="font-size:11.5px; color:#5F5853;">{{ __('emails/booking-confirmation.signature', ['property' => $propertyName]) }}</span></td>
        </tr>
    </table>

    {{-- Footer note + calendar links --}}
    <p style="margin:20px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; line-height:1.6; color:#5F5853;">{{ $sourceLine }}@if ($freeCancelUntil) · {{ __('emails/booking-confirmation.free_cancel_until', ['date' => $freeCancelUntil]) }}@endif.</p>
    @if ($blocks['calendar_links'] ?? true)
    <p style="margin:8px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:11.5px; color:#6C645F;">{{ __('emails/booking-confirmation.add_to_calendar') }} <a href="{{ $calendar['google'] }}" style="color:#5F5853; font-weight:500; text-decoration:none;">Google</a> · <a href="{{ $calendar['outlook'] }}" style="color:#5F5853; font-weight:500; text-decoration:none;">Outlook</a> · <a href="{{ $calendar['ics'] }}" download="{{ $reference }}.ics" style="color:#5F5853; font-weight:500; text-decoration:none;">Apple</a></p>
    @endif
@endsection
