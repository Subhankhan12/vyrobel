{{-- Owner Annual Tax Summary PDF (Phase 2 · Sub-gate 4). PDF-SAFE: dompdf (HTML4/CSS2.1), table-based, inline
     CSS, DejaVu Sans (renders € and U+2212). Every figure is aggregated from the owner's REAL committed
     statements and reconciles to the cent (gross + deductions = net). No fabricated totals; no OTA brand.
     Self-only document — the owner's own tax id / legal name appear because it is their own summary.
     WITHHOLDING: this template's original comment said the document carried none. That went stale when the
     tax engine (#73) shipped — OwnerTaxService::annualSummary() emits a real "Withholding tax" bucket, so it
     reaches the categories table like any other deduction, and is omitted only when it is genuinely zero. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; }
        .muted { color: #5F5853; }
        .eyebrow { color: #C2410C; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 20px; margin: 2px 0 0; }
        h2 { font-size: 13px; margin: 24px 0 6px; }
        .hdr td { vertical-align: top; }
        .meta { width: 100%; border-collapse: collapse; margin: 22px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #5F5853; width: 130px; }
        .totals { width: 100%; border-collapse: collapse; margin: 18px 0 2px; }
        .totals td { width: 33%; padding: 12px 14px; border: 1px solid #EFE3D9; }
        .totals .lbl { font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; }
        .totals .val { font-size: 17px; font-weight: bold; margin-top: 3px; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.grid th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.grid th.r, table.grid td.r { text-align: right; }
        table.grid td { padding: 7px 4px; font-size: 12px; border-bottom: 1px solid #F2E9E1; }
        table.grid tr.tot td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; padding-top: 10px; }
        .neg { color: #5F5853; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #6C645F; }
    </style>
</head>
<body>
    <div class="wrap">
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">Vyrobel</span>
                    <div class="muted" style="margin-top:6px; font-size:11px;">on behalf of {{ $operator }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Annual tax summary</div>
                    <h1>{{ $year }}</h1>
                    <div class="muted" style="margin-top:4px; font-size:11px;">{{ $statement_count }} {{ \Illuminate\Support\Str::plural('statement', $statement_count) }} · {{ $currency }}</div>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr><td class="k">Owner</td><td>{{ $legal_name }}@if ($owner_name !== $legal_name) <span class="muted">({{ $owner_name }})</span>@endif</td></tr>
            @if ($tax_country !== '')<tr><td class="k">Tax residency</td><td>{{ $tax_country }}</td></tr>@endif
            @if ($tax_id !== '')<tr><td class="k">Tax ID</td><td>{{ $tax_id }}</td></tr>@endif
            <tr><td class="k">Period</td><td>{{ $period }}</td></tr>
        </table>

        {{-- Summary totals — Gross + Deductions = Net, by construction --}}
        <table class="totals">
            <tr>
                <td><div class="lbl">Gross revenue</div><div class="val">{{ $gross_display }}</div></td>
                <td><div class="lbl">Total deductions</div><div class="val neg">{{ $deductions_display }}</div></td>
                <td><div class="lbl">Net paid to owner</div><div class="val">{{ $net_display }}</div></td>
            </tr>
        </table>

        {{-- By month — one row per real statement --}}
        <h2>By month</h2>
        <table class="grid">
            <thead>
                <tr><th>Month</th><th class="r">Gross</th><th class="r">Commission</th><th class="r">Fees</th><th class="r">Net</th></tr>
            </thead>
            <tbody>
                @foreach ($months as $m)
                    <tr>
                        <td>{{ $m['label'] }}</td>
                        <td class="r">{{ $m['gross'] }}</td>
                        <td class="r neg">{{ $m['commission'] }}</td>
                        <td class="r neg">{{ $m['fees'] }}</td>
                        <td class="r">{{ $m['net'] }}</td>
                    </tr>
                @endforeach
                <tr class="tot">
                    <td>Total {{ $year }}</td>
                    <td class="r">{{ $gross_display }}</td>
                    <td class="r">{{ $total_commission }}</td>
                    <td class="r">{{ $total_fees }}</td>
                    <td class="r">{{ $net_display }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Deductions by category — only real, non-zero categories --}}
        @if ($categories !== [])
            <h2>Deductions by category</h2>
            <table class="grid">
                <tbody>
                    @foreach ($categories as $c)
                        <tr><td>{{ $c['label'] }}</td><td class="r neg">{{ $c['amount'] }}</td></tr>
                    @endforeach
                    <tr class="tot"><td>Total deductions</td><td class="r">{{ $deductions_display }}</td></tr>
                </tbody>
            </table>
        @endif

        <div class="foot">
            For your records. This summary consolidates your {{ $statement_count }} committed {{ \Illuminate\Support\Str::plural('statement', $statement_count) }}
            for the {{ $year }} calendar year and reconciles to the figures in your owner portal. Figures are gross of any income
            tax due, which is the owner's responsibility. This document is not tax advice — please share it with a qualified
            accountant. Issued by Vyrobel on behalf of {{ $operator }}.
        </div>
    </div>
</body>
</html>
