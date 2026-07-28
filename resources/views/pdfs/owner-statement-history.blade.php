{{-- Owner statement-history PDF. PDF-SAFE: dompdf renders HTML4/CSS2.1, so this is a table-based, inline-CSS
     branded document (NOT the app's frosted glass). DejaVu Sans renders € + the U+2212 minus. Every figure is a
     real owner_statements row; the net total(s) reconcile to the listed rows (Σ per currency, no cross-currency
     sum, #17). No guest data (statements carry none). --}}
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
        .sub { color: #8A8079; margin-top: 4px; font-size: 11px; }
        table.rows { width: 100%; border-collapse: collapse; margin-top: 22px; }
        table.rows th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.rows th.r, table.rows td.r { text-align: right; }
        table.rows td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        table.rows td .m { font-weight: bold; }
        table.rows td .p { color: #8A8079; font-size: 10.5px; }
        table.rows tr.tot td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 13.5px; padding-top: 12px; }
        table.rows tr.tot td .net { color: #C2410C; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #A69A92; }
    </style>
</head>
<body>
    <div class="wrap">
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">{{ $company }}</span>
                    <div class="muted" style="margin-top:6px; font-size:11px;">managed with Vyrobel</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Statement history</div>
                    <h1>{{ $scope_label }}</h1>
                    <div class="sub">{{ $owner_name }} · {{ $count }} {{ \Illuminate\Support\Str::plural('statement', $count) }}</div>
                </td>
            </tr>
        </table>

        <table class="rows">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="r">Gross</th>
                    <th class="r">Net payable</th>
                    <th class="r">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td><span class="m">{{ $r['month_label'] }}</span><br><span class="p">{{ $r['period_range'] }}</span></td>
                        <td class="r">{{ $r['gross_display'] }}</td>
                        <td class="r">{{ $r['net_display'] }}</td>
                        <td class="r">{{ $r['status_label'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted" style="padding:16px 4px;">No finalized statements in this period.</td></tr>
                @endforelse

                @if ($rows !== [])
                    <tr class="tot">
                        <td>Total net payable</td>
                        <td class="r"></td>
                        <td class="r net">{{ implode(' · ', $net_totals) }}</td>
                        <td class="r"></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="foot">
            Generated from Vyrobel's owner ledger; each row reconciles to that month's owner statement. {{ $company }}.
        </div>
    </div>
</body>
</html>
