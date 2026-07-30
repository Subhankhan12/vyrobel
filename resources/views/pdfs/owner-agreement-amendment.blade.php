{{-- Owner agreement-AMENDMENT PDF (Owner Agreement exact-match #82). PDF-SAFE (dompdf HTML4/CSS2.1, DejaVu
     Sans). Shows the REAL amendment content + the real acknowledgment record (who/when) when acknowledged.
     ⚠️ This documents an IN-PORTAL ACKNOWLEDGMENT, NOT a legally-binding e-signature — stated honestly in
     the footer; nothing here claims legal signature validity. Unset fields are omitted, never invented. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; }
        .muted { color: #8A8079; }
        .eyebrow { color: #C2410C; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 20px; margin: 2px 0 0; }
        .hdr td { vertical-align: top; }
        .lead { margin-top: 22px; font-size: 13px; line-height: 1.55; }
        .sec { font-size: 13px; font-weight: bold; margin: 22px 0 4px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        .ack { margin-top: 22px; background-color: #E4F2EA; border: 1px solid #CFE6D9; border-radius: 8px; padding: 14px 16px; font-size: 12px; color: #1B5B3E; }
        .ack .eyebrow { color: #1B6B48; }
        .await { margin-top: 22px; background-color: #FBEFD8; border: 1px solid #F1DFBE; border-radius: 8px; padding: 14px 16px; font-size: 12px; color: #8A5A08; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #A69A92; }
    </style>
</head>
<body>
    <div class="wrap">
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">{{ $operator }}</span>
                    <div class="muted" style="margin-top:6px; font-size:10.5px;">{{ $operator_addr }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Agreement amendment</div>
                    <h1>{{ $version }}</h1>
                    <div style="font-size:13px; font-weight:bold; margin-top:3px;">{{ $doc_ref }}</div>
                    <div class="muted" style="margin-top:2px; font-size:11px;">{{ $owner_name }}</div>
                </td>
            </tr>
        </table>

        <div class="lead">
            <strong>{{ $title }}</strong>
            @if ($summary)<div class="muted" style="margin-top:6px;">{{ $summary }}</div>@endif
            <div class="muted" style="margin-top:8px;">Effective {{ $effective_date }}.</div>
        </div>

        @if (! empty($changes))
            <div class="sec">What changes</div>
            <table class="lines">
                <tbody>
                    @foreach ($changes as $c)
                        <tr><td class="muted" style="width:180px;">{{ $c['label'] }}</td><td style="font-weight:600;">{{ $c['value'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($acknowledged_by)
            <div class="ack">
                <div class="eyebrow">Acknowledged in your portal</div>
                <div style="margin-top:4px;">Acknowledged by {{ $acknowledged_by }} on {{ $acknowledged_at }} · takes effect {{ $effective_date }}.</div>
            </div>
        @else
            <div class="await">
                <div class="eyebrow">Awaiting acknowledgment</div>
                <div style="margin-top:4px;">This amendment has not yet been acknowledged in the portal.</div>
            </div>
        @endif

        <div class="foot">
            Generated {{ $generated_on }} · Acknowledging an amendment in your portal records your review and
            consent (who and when) and applies the new terms from the effective date. It is a portal
            acknowledgment, not a legally-binding electronic signature.
        </div>
    </div>
</body>
</html>
