{{-- Guest Arrival Guide PDF (Guest Portal · GATE 2). PDF-SAFE (dompdf HTML4/CSS2.1): table-based, inline
     CSS, DejaVu Sans. Built from the SAME window-gated ArrivalPack as the on-screen page + the #83 email
     (no drift). 🔒 #33 — the door code + wifi print ONLY when the arrival window is open ($pack.released);
     a pre-window guide carries the honest "appears closer to arrival" state, NEVER a code. No fabricated
     house guide / checkout steps / local picks. RAW-BLADE RULE: every `@` follows a space, newline or tag. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; line-height: 30px; }
        .muted { color: #8A8079; }
        .faint { color: #A69A92; }
        .eyebrow { color: #C2410C; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 20px; margin: 2px 0 0; }
        .meta { width: 100%; border-collapse: collapse; margin: 24px 0 8px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #8A8079; width: 130px; }
        .card { border: 1px solid #EFE3D9; border-radius: 10px; padding: 16px 18px; margin-top: 14px; }
        .card .lbl { font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; }
        .code { font-size: 30px; font-weight: bold; letter-spacing: 6px; color: #2B2320; margin-top: 4px; }
        .wifi td { padding: 6px 0; font-size: 13px; }
        .wifi .k { color: #8A8079; width: 130px; }
        .withheld { background-color: #FBEFD8; border-color: #F0DDBC; }
        .section-h { font-size: 13px; font-weight: bold; margin: 22px 0 6px; }
        .body { font-size: 12.5px; line-height: 1.55; }
        .foot { margin-top: 30px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #A69A92; }
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
                <div class="eyebrow">Arrival guide</div>
                <h1>{{ $property }}</h1>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr><td class="k">Guest</td><td>{{ $guestName }}</td></tr>
        <tr><td class="k">Reference</td><td>{{ $reference }}</td></tr>
        <tr><td class="k">Check-in</td><td>{{ $checkIn }}@if ($pack['check_in_time']) · from {{ $pack['check_in_time'] }}@endif</td></tr>
        <tr><td class="k">Check-out</td><td>{{ $checkOut }}@if ($pack['check_out_time']) · by {{ $pack['check_out_time'] }}@endif</td></tr>
    </table>

    {{-- Access — door code + wifi, printed ONLY within the arrival window (#33). --}}
    @if ($pack['released'])
        @if ($pack['door_code'])
            <div class="card">
                <div class="lbl">Door code</div>
                <div class="code">{{ $pack['door_code'] }}</div>
            </div>
        @endif
        @if ($pack['wifi_ssid'])
            <div class="card">
                <table class="wifi" style="width:100%; border-collapse:collapse;">
                    <tr><td class="k">Wi-Fi network</td><td>{{ $pack['wifi_ssid'] }}</td></tr>
                    @if ($pack['wifi_password'])
                        <tr><td class="k">Password</td><td>{{ $pack['wifi_password'] }}</td></tr>
                    @endif
                </table>
            </div>
        @endif
    @else
        <div class="card withheld">
            <div class="lbl">Your access details</div>
            <div class="body" style="margin-top:4px;">Your door code and Wi-Fi unlock as your stay approaches — from {{ $pack['released_from'] }}. Save this guide and re-download it closer to arrival.</div>
        </div>
    @endif

    {{-- Getting here — the property's real address (SAME source as the pre-arrival email). --}}
    @if ($pack['directions'])
        <div class="section-h">Getting here</div>
        <div class="body">{{ $pack['directions'] }}</div>
    @endif

    {{-- House notes — the operator's real note for this unit (never a fabricated multi-section guide). --}}
    @if ($pack['released'] && $pack['arrival_notes'])
        <div class="section-h">House notes</div>
        <div class="body">{{ $pack['arrival_notes'] }}</div>
    @endif

    <div class="foot">
        {{ $property }} · {{ $reference }} · The door code is live from check-in. Please keep this guide to yourself.
    </div>
</div>
</body>
</html>
