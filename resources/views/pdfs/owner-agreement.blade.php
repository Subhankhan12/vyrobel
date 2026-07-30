{{-- Owner management-agreement summary PDF (Owner Agreement exact-match #82). PDF-SAFE: dompdf renders
     HTML4/CSS2.1 — table-based, inline CSS, DejaVu Sans (renders € and U+2212). Same visual language as
     owner-remittance.blade.php. Every figure is a real column; an unset term is OMITTED, never invented.
     Per-villa commission is the SAME resolveFor the attribution engine uses (#17). This is a SUMMARY of
     the operator's terms, NOT a signed legal artifact — stated honestly in the footer. --}}
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
        .sum { width: 100%; border-collapse: collapse; margin-top: 24px; background-color: #FDF7F3; border: 1px solid #EFE3D9; border-radius: 8px; }
        .sum td { padding: 14px 16px; vertical-align: top; }
        .figure { font-size: 22px; font-weight: bold; margin-top: 3px; }
        .pill { display: inline-block; font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .06em; border-radius: 8px; padding: 2px 8px; color: #1B6B48; background-color: #E4F2EA; }
        .meta { width: 100%; border-collapse: collapse; margin: 18px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #8A8079; width: 150px; }
        .sec { font-size: 13px; font-weight: bold; margin: 22px 0 4px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
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
                    <div class="eyebrow">Management agreement</div>
                    <h1>Your terms</h1>
                    <div style="font-size:13px; font-weight:bold; margin-top:3px;">{{ $doc_ref }}</div>
                    <div class="muted" style="margin-top:2px; font-size:11px;">{{ $owner_name }}</div>
                </td>
            </tr>
        </table>

        {{-- Headline — the REAL commission + status --}}
        <table class="sum">
            <tr>
                <td>
                    <div class="eyebrow">Management fee</div>
                    <div class="figure">{{ $commission ?? 'Not set' }}</div>
                    <div class="muted" style="font-size:11px;">{{ $commission ? 'of your gross booking revenue' : 'no commission rate is set' }}</div>
                </td>
                <td style="text-align:right;">
                    <span class="pill">{{ $status }}</span>
                    <div class="muted" style="margin-top:8px; font-size:11px;">{{ $scope }}</div>
                </td>
            </tr>
        </table>

        {{-- Key terms — REAL columns only; an unset term is omitted, never invented --}}
        <div class="sec">Key terms</div>
        <table class="meta">
            <tr><td class="k">Started</td><td>{{ $started }}</td></tr>
            <tr><td class="k">Runs until</td><td>{{ $runs_until ?? 'Open-ended' }}</td></tr>
            @if ($term)<tr><td class="k">Term</td><td>{{ $term }}</td></tr>@endif
            @if ($notice)<tr><td class="k">Notice period</td><td>{{ $notice }}</td></tr>@endif
            @if (! is_null($auto_renews))<tr><td class="k">Renewal</td><td>{{ $auto_renews ? 'Auto-renewing' : 'Does not auto-renew' }}</td></tr>@endif
            <tr><td class="k">Payout schedule</td><td>{{ $schedule ?? 'Not set' }}</td></tr>
            @if ($payout_currency)<tr><td class="k">Payout currency</td><td>{{ $payout_currency }}</td></tr>@endif
            @if ($fee)<tr><td class="k">Fixed fee</td><td>{{ $fee }}{{ $fee_interval ? ' · '.$fee_interval : '' }}</td></tr>@endif
        </table>

        {{-- Terms by villa — REAL per-unit commission (the same rate the statements apply); cleaning at cost --}}
        @if (! empty($villas))
            <div class="sec">Terms by villa</div>
            <table class="lines">
                <thead><tr><th>Villa</th><th class="r">Commission</th><th class="r">Cleaning</th><th class="r">Since</th></tr></thead>
                <tbody>
                    @foreach ($villas as $v)
                        <tr>
                            <td>{{ $v['unit'] }}</td>
                            <td class="r">{{ $v['commission'] ?? 'Not set' }}</td>
                            <td class="r muted">At cost</td>
                            <td class="r muted">{{ $v['since'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="foot">
            Generated {{ $generated_on }} · This is a summary of the commercial terms your operator has set,
            for your reference. It is not a signed legal contract; your binding agreement is held by your
            operator.
        </div>
    </div>
</body>
</html>
