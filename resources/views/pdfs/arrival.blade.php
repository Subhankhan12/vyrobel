{{-- Guest Arrival Guide PDF (Guest Portal · GATE 2). PDF-SAFE (dompdf HTML4/CSS2.1): table-based, inline
     CSS, DejaVu Sans. Built from the SAME window-gated ArrivalPack as the on-screen page + the #83 email
     (no drift). 🔒 #33 — the door code + wifi print ONLY when the arrival window is open ($pack.released);
     a pre-window guide carries the honest "appears closer to arrival" state, NEVER a code. No fabricated
     house guide / checkout steps / local picks.
     ⚠️ The footer used to add "The door code is live from check-in." — the engine releases it
     ArrivalPack::LEAD_DAYS BEFORE check-in and keeps it until check-out, so that line was wrong in the
     understating direction AND printed on the in-window guide where the code is already visible days
     early, contradicting itself on the page. The timing is stated ONCE, from the gate's own
     released_from, in the withheld-state block. Same family as the "48h" literal fixed at G2.
     RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<!DOCTYPE html>
<html {!! \App\Support\I18n\Locales::htmlAttributes() !!}>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; line-height: 30px; }
        .muted { color: #5F5853; }
        .faint { color: #6C645F; }
        .eyebrow { color: #C2410C; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 20px; margin: 2px 0 0; }
        .meta { width: 100%; border-collapse: collapse; margin: 24px 0 8px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #5F5853; width: 130px; }
        .card { border: 1px solid #EFE3D9; border-radius: 10px; padding: 16px 18px; margin-top: 14px; }
        .card .lbl { font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; }
        .code { font-size: 30px; font-weight: bold; letter-spacing: 6px; color: #2B2320; margin-top: 4px; }
        .wifi td { padding: 6px 0; font-size: 13px; }
        .wifi .k { color: #5F5853; width: 130px; }
        .withheld { background-color: #FBEFD8; border-color: #F0DDBC; }
        .section-h { font-size: 13px; font-weight: bold; margin: 22px 0 6px; }
        .body { font-size: 12.5px; line-height: 1.55; }
        .foot { margin-top: 30px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #6C645F; }
    </style>
</head>
<body>
<div class="wrap">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="vertical-align:top;">
                <span class="brand-mark">{{ mb_substr($property, 0, 1) }}</span>
            </td>
            <td style="vertical-align:top; padding-left:12px;">
                <div class="eyebrow">{{ __('pdfs/arrival.eyebrow') }}</div>
                <h1>{{ $property }}</h1>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr><td class="k">{{ __('pdfs/arrival.guest') }}</td><td>{{ $guestName }}</td></tr>
        <tr><td class="k">{{ __('pdfs/arrival.reference') }}</td><td>{{ $reference }}</td></tr>
        <tr><td class="k">{{ __('pdfs/arrival.check_in') }}</td><td>@if ($pack['check_in_time']){{ __('pdfs/arrival.check_in_with_time', ['date' => $checkIn, 'time' => $pack['check_in_time']]) }}@else{{ $checkIn }}@endif</td></tr>
        <tr><td class="k">{{ __('pdfs/arrival.check_out') }}</td><td>@if ($pack['check_out_time']){{ __('pdfs/arrival.check_out_with_time', ['date' => $checkOut, 'time' => $pack['check_out_time']]) }}@else{{ $checkOut }}@endif</td></tr>
    </table>

    {{-- Access — door code + wifi, printed ONLY within the arrival window (#33). --}}
    @if ($pack['released'])
        @if ($pack['door_code'])
            <div class="card">
                <div class="lbl">{{ __('pdfs/arrival.door_code') }}</div>
                <div class="code">{{ $pack['door_code'] }}</div>
            </div>
        @endif
        @if ($pack['wifi_ssid'])
            <div class="card">
                <table class="wifi" style="width:100%; border-collapse:collapse;">
                    <tr><td class="k">{{ __('pdfs/arrival.wifi_network') }}</td><td>{{ $pack['wifi_ssid'] }}</td></tr>
                    @if ($pack['wifi_password'])
                        <tr><td class="k">{{ __('pdfs/arrival.password') }}</td><td>{{ $pack['wifi_password'] }}</td></tr>
                    @endif
                </table>
            </div>
        @endif
    @else
        <div class="card withheld">
            <div class="lbl">{{ __('pdfs/arrival.access_details') }}</div>
            <div class="body" style="margin-top:4px;">{{ __('pdfs/arrival.access_withheld', ['date' => $pack['released_from']]) }}</div>
        </div>
    @endif

    {{-- Getting here — the property's real address (SAME source as the pre-arrival email). --}}
    @if ($pack['directions'])
        <div class="section-h">{{ __('pdfs/arrival.getting_here') }}</div>
        <div class="body">{{ $pack['directions'] }}</div>
    @endif

    {{-- House notes — the operator's real note for this unit (never a fabricated multi-section guide). --}}
    @if ($pack['released'] && $pack['arrival_notes'])
        <div class="section-h">{{ __('pdfs/arrival.house_notes') }}</div>
        <div class="body">{{ $pack['arrival_notes'] }}</div>
    @endif

    <div class="foot">
        {{ $property }} · {{ $reference }} · {{ __('pdfs/arrival.keep_private') }}
    </div>
</div>
</body>
</html>
