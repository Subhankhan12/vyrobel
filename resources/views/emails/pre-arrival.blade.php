{{-- Guest pre-arrival (transactional-emails exact-match). Matched to "Pre-arrival Email.html" — self check-in
     with the guest's OWN door code + wifi (real, from ArrivalPack; guest-only delivery — the #33 owner-perimeter
     is untouched). Email-safe: inline styles, table layout, bulletproof button. Every secret is real or omitted
     honestly (never fabricated). RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
@extends('emails.layout')
@section('footer_reason', 'booking')

@section('title', __('emails/pre-arrival.page_title'))
@section('preheader', __('emails/pre-arrival.preheader', ['unit' => $unitName]))

@section('content')
    <p style="margin:0 0 6px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; font-weight:600; letter-spacing:1px; text-transform:uppercase; color:#C2410C;">{{ __('emails/pre-arrival.eyebrow') }}</p>
    <h1 style="margin:0 0 6px; font-family:'Sora',Arial,Helvetica,sans-serif; font-size:24px; font-weight:700; line-height:1.25; color:#2B2320;">{{ $heading }}</h1>
    <p style="margin:0 0 22px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; line-height:1.55; color:#4A4038;">{{ $intro }}</p>

    {{-- Door code hero — the guest's OWN access secret (real, timing-released; toggleable block #86) --}}
    @if ($blocks['door_code'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#FBF1E9; border:1px solid #F0DFCF; border-radius:14px; margin:0 0 16px;">
        <tr>
            <td align="center" style="padding:22px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; letter-spacing:.06em; text-transform:uppercase; color:#704204;">{{ __('emails/pre-arrival.door_code_label') }}</span><br>
                @if ($doorCode)
                    <span style="font-family:'Sora',Arial,Helvetica,sans-serif; font-size:34px; font-weight:700; letter-spacing:6px; color:#2B2320;">{{ $doorCode }}</span><br>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">{{ __('emails/pre-arrival.works_from', ['date' => $releasedFrom]) }}</span>
                @else
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; color:#4A4038;">{{ __('emails/pre-arrival.access_later') }}</span>
                @endif
            </td>
        </tr>
    </table>
    <p style="margin:0 0 22px; text-align:center;"><a href="{{ $arrivalUrl }}" style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; font-weight:600; color:#C2410C; text-decoration:none;">{{ __('emails/pre-arrival.open_guide') }}</a></p>
    @endif

    {{-- Getting there (toggleable block #86) --}}
    @if ($directions && ($blocks['directions'] ?? true))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 12px;">
            <tr>
                <td style="padding:14px 16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/pre-arrival.getting_there') }}</span><br>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14.5px; color:#2B2320;">{{ $directions }}</span>
                    @if ($mapsUrl)<br><a href="{{ $mapsUrl }}" style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#C2410C; text-decoration:underline;">{{ __('emails/pre-arrival.open_in_maps') }}</a>@endif
                </td>
            </tr>
        </table>
    @endif

    {{-- WiFi (toggleable block #86) --}}
    @if (($wifiSsid || $wifiPassword) && ($blocks['wifi'] ?? true))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 12px;">
            <tr>
                <td style="padding:14px 16px;">
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/pre-arrival.wifi') }}</span><br>
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14.5px; color:#2B2320;">@if ($wifiSsid && $wifiPassword){!! __('emails/pre-arrival.wifi_network_and_password', ['ssid' => e($wifiSsid), 'password' => e($wifiPassword)]) !!}@elseif ($wifiSsid){!! __('emails/pre-arrival.wifi_network', ['ssid' => e($wifiSsid)]) !!}@else{!! __('emails/pre-arrival.wifi_password_only', ['password' => e($wifiPassword)]) !!}@endif</span>
                </td>
            </tr>
        </table>
    @endif

    {{-- Check-in & out (toggleable block #86) --}}
    @if ($blocks['checkin'] ?? true)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #EFE3D9; border-radius:12px; margin:0 0 20px;">
        <tr>
            <td style="padding:14px 16px;">
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12px; color:#5F5853;">{{ __('emails/pre-arrival.check_in_out') }}</span><br>
                {{-- ⚠️⚠️ A TIME IS A FACT OR NOTHING, AND AN EMAIL CANNOT BE RECALLED (#39). These read
                     `?? '15:00'` / `?? '11:00'`, so a property that publishes no times sent every guest an
                     arrival hour nobody set — and the guest PLANS around it. `properties.check_in_time` is
                     nullable with no schema default and no config default, so those figures were never "the
                     default": they are right for the two demo properties (which publish exactly 15:00/11:00)
                     and silently wrong for anyone else. ArrivalPack::time() already hands us NULL when a time
                     is unpublished — the invention was here, at the last step before sending.
                     ⚠️ VALUE ONLY — the markup, spans and styles below are untouched. --}}
                @php
                    $vyHasIn = ($checkInTime ?? '') !== '';
                    $vyHasOut = ($checkOutTime ?? '') !== '';
                    $vyTimesArgs = ['in_time' => $checkInTime, 'in_date' => $checkInLabel, 'out_time' => $checkOutTime, 'out_date' => $checkOutLabel];
                    $vyTimes = match (true) {
                        $vyHasIn && $vyHasOut => __('emails/pre-arrival.times_in_out', $vyTimesArgs),
                        $vyHasIn => __('emails/pre-arrival.times_in_only', $vyTimesArgs),
                        $vyHasOut => __('emails/pre-arrival.times_out_only', $vyTimesArgs),
                        default => __('emails/pre-arrival.times_none', $vyTimesArgs),
                    };
                @endphp
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14.5px; color:#2B2320;">{{ $vyTimes }}</span>
                @if (($checkInTime ?? '') === '' || ($checkOutTime ?? '') === '')
                    {{-- ⚠️ THE HONEST ABSENCE. Saying nothing about the hour would leave the guest to assume
                         one; this tells them the truth — the property has not published it yet. --}}
                    <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5F5853;"> {{ __('emails/pre-arrival.times_unconfirmed') }}</span>
                @endif
                <span style="font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; color:#5F5853;"> {!! __('emails/pre-arrival.arriving_early', ['url' => e($messagesUrl)]) !!}</span>
            </td>
        </tr>
    </table>
    @endif

    {{-- Balance due — real, only when money is genuinely owed (toggleable block #86) --}}
    @if ($balance && ($blocks['balance'] ?? true))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;">
            <tr>
                <td align="center" style="padding:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:14px; color:#4A4038;">{!! __('emails/pre-arrival.balance_due_line', ['balance' => e($balance)]) !!}</td>
            </tr>
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td align="center" bgcolor="#C2410C" style="background-color:#C2410C; border-radius:30px;">
                            <a href="{{ $payUrl }}" target="_blank" style="display:inline-block; padding:13px 30px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:15px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:30px;">{{ $button }}</a>
                        </td>
                    </tr></table>
                </td>
            </tr>
        </table>
    @endif

    {{-- Message us --}}
    <p style="margin:22px 0 0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px; line-height:1.55; color:#5F5853;">{!! __('emails/pre-arrival.message_us_line', ['url' => e($messagesUrl)]) !!}</p>

    <hr style="border:none; border-top:1px solid #EFE3D9; margin:22px 0 14px;">
    <p style="margin:0 0 10px; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:12.5px; color:#5F5853;">{!! __('emails/pre-arrival.keep_safe', ['reference' => e($reference)]) !!}</p>
    <p style="margin:0; font-family:'Inter',Arial,Helvetica,sans-serif; font-size:13px;"><a href="{{ $viewUrl }}" style="color:#C2410C; text-decoration:none; font-weight:600;">{{ __('emails/pre-arrival.view_booking') }}</a> &nbsp;·&nbsp; <a href="{{ $checkinUrl }}" style="color:#C2410C; text-decoration:none; font-weight:600;">{{ __('emails/pre-arrival.online_checkin') }}</a></p>
@endsection
