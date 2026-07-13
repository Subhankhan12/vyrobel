{{-- Owner statement PDF (Demo-readiness · Part B · Step B1). PDF-SAFE: dompdf renders HTML4/CSS2.1, so
     this is a table-based, inline-CSS branded document (NOT the app's frosted glass). DejaVu Sans is
     dompdf's bundled Unicode font — it renders € and the U+2212 minus correctly. Every figure comes from
     the real owner_statement_lines (which sum to the net) — no invented lines, reconciles to the cent. --}}
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
        .meta { width: 100%; border-collapse: collapse; margin: 24px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #8A8079; width: 120px; }
        .prov { display: inline-block; font-size: 8.5px; font-weight: bold; text-transform: uppercase; color: #B26A08; background-color: #FBEFD8; border-radius: 8px; padding: 1px 7px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        table.lines tr.net td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 14px; padding-top: 12px; }
        .neg { color: #8A8079; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #A69A92; }
        .reconcile { margin-top: 6px; font-size: 11px; color: #4A4038; }
    </style>
</head>
<body>
    <div class="wrap">
        {{-- Brand / title header --}}
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">Vyrobel</span>
                    <div class="muted" style="margin-top:6px; font-size:11px;">{{ $operator }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Owner statement</div>
                    <h1>{{ $period_label }}</h1>
                    <div class="muted" style="margin-top:4px; font-size:11px;">Statement #{{ $sequence_no }} · {{ ucfirst($status) }}</div>
                </td>
            </tr>
        </table>

        {{-- Statement meta --}}
        <table class="meta">
            <tr><td class="k">Owner</td><td>{{ $owner_name }}</td></tr>
            <tr><td class="k">Period</td><td>{{ $period_range }}</td></tr>
            <tr><td class="k">Currency</td><td>{{ $currency }}</td></tr>
            @if ($fx)
                <tr><td class="k">FX</td><td>1 {{ $fx['source'] }} = {{ $fx['rate'] }} {{ $currency }} <span class="muted">(source: {{ $fx['src'] }})</span></td></tr>
            @endif
        </table>

        {{-- The REAL reconciling breakdown — only engine-computed lines; the net = sum of components --}}
        <table class="lines">
            <thead>
                <tr><th>Breakdown</th><th class="r">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr class="{{ $line['is_net'] ? 'net' : '' }}">
                        <td>
                            {{ $line['description'] }}
                            @if ($line['unverified'])<span class="prov">Provisional</span>@endif
                        </td>
                        <td class="r {{ $line['is_negative'] ? 'neg' : '' }}">{{ $line['amount_display'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="reconcile">Net payable to owner: <strong>{{ $net_display }}</strong>@if ($has_unverified) <span class="muted">— includes provisional lines pending verification.</span>@endif</div>

        <div class="foot">
            This statement is generated from Vyrobel's owner ledger and reconciles to the figures shown in the owner portal.
            Vyrobel · {{ $operator }}.
        </div>
    </div>
</body>
</html>
