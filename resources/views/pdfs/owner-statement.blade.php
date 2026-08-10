{{-- Owner statement PDF (exact-match pass). PDF-SAFE: dompdf renders HTML4/CSS2.1, so this is a table-based,
     inline-CSS branded LETTERHEAD document (NOT the app's frosted glass). DejaVu Sans renders € + the U+2212 minus.
     Every figure comes from the real owner_statement_lines (which sum to the net) + the shared OwnerStatementBreakdown
     (Σ per-unit gross == the statement gross) — reconciles to the cent (#17). No guest data (#28); the real
     "Payment & ops fees" (#70) + "Withholding tax" (#73) lines print when non-zero (€0 → omitted, never faked
     #39). The masked IBAN appears only when the exporter is payout-capable (set upstream).
     Logic-free by design — all strings are composed in OwnerStatementDocument (a top-of-file @php once dropped the
     <head> at compile time). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 34px 40px; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; }
        {{-- ⚠️ POST-AA GREYS — measured ON WHITE PAPER, which is how a statement is actually read.
             The document carried the pre-AA greys (3.86 and 2.74) — the same survival the operator
             Schedule PDF was fixed for. The darker of the two was carrying the NEGATIVE MONEY (.neg =
             the commission/deduction figures) and every per-line detail. Now 6.98 / 5.79.
             ⚠️ The .band .dark greys are NOT touched: they sit on #2A211C and measure 8.21 / 7.05 —
             darkening them would be backwards, exactly as on the screen's dark payout rail.
             ⚠️ This is a BLADE comment, not a CSS one: a CSS comment is RENDERED, so quoting the old
             hex values here shipped them inside every generated statement and made the regression
             guard below impossible to write. --}}
        .muted { color: #5F5853; }
        .eyebrow { color: #C2410C; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 26px; margin: 14px 0 0; letter-spacing: -0.5px; }
        .sub { color: #5F5853; margin-top: 5px; font-size: 11.5px; }
        .hdr td { vertical-align: top; }

        /* Net-payable headline band (dark left + light-peach stats right) */
        .band { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 20px; }
        .band .dark { background-color: #2A211C; color: #fff; padding: 18px 20px; border-radius: 12px 0 0 12px; width: 56%; vertical-align: top; }
        .band .dark .k { color: #C9B8AC; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .band .dark .v { font-size: 27px; font-weight: bold; margin: 5px 0 6px; }
        .band .dark .m { color: #B9ABA0; font-size: 10.5px; }
        .band .stats { background-color: #FCF3EC; padding: 14px 20px; border-radius: 0 12px 12px 0; vertical-align: middle; }
        .band .stats table { width: 100%; border-collapse: collapse; }
        .band .stats td { padding: 5px 0; font-size: 12px; }
        .band .stats td.r { text-align: right; font-weight: bold; }

        h2 { font-size: 14px; margin: 26px 0 0; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.grid th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.grid th.r, table.grid td.r { text-align: right; }
        table.grid td { padding: 9px 4px; font-size: 12.5px; border-bottom: 1px solid #F2E9E1; }
        table.grid td .d { color: #5F5853; }
        table.grid tr.net td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 14px; padding-top: 12px; }
        table.grid tr.net td.r { color: #C2410C; }
        table.grid tr.tot td { border-top: 1.5px solid #E4D6CB; border-bottom: none; font-weight: bold; padding-top: 11px; }
        .neg { color: #5F5853; }
        .u-name { font-weight: bold; }
        .u-kind { color: #5F5853; font-weight: normal; }

        .callout { margin-top: 24px; background-color: #FBF0E8; border-left: 3px solid #C2410C; border-radius: 0 10px 10px 0; padding: 13px 16px; font-size: 11.5px; color: #5A4A40; line-height: 1.6; }
        .callout b { color: #C2410C; }
        .foot { margin-top: 22px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #6C645F; }
        .foot td { font-size: 10px; color: #6C645F; }
    </style>
</head>
<body>
    <div class="wrap">
        {{-- Letterhead --}}
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span style="font-size:16px; font-weight:bold; padding-left:6px;">{{ $company }}</span>
                    <div class="muted" style="margin-top:6px; font-size:11px;">@if ($country){{ $country }} · @endif managed with Vyrobel</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Owner statement</div>
                    <div style="font-size:13px; font-weight:bold; margin-top:3px;">{{ $doc_ref }}</div>
                </td>
            </tr>
        </table>

        {{-- Title + subtitle --}}
        <h1>{{ $period_label }}</h1>
        <div class="sub">{{ $owner_name }} · {{ $company }} · {{ $unit_count }} {{ \Illuminate\Support\Str::plural('unit', $unit_count) }} · issued {{ $issued }}</div>

        {{-- Recipient block — the owner's REAL registered address (GATE 3), shown only when set (never fabricated) --}}
        @if (! empty($owner_address_lines))
            <div class="muted" style="margin-top:8px; font-size:11px; line-height:1.5;">
                <div class="eyebrow">Prepared for</div>
                <div style="font-weight:bold; color:#111;">{{ $owner_name }}</div>
                @foreach ($owner_address_lines as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        @endif

        {{-- Net-payable headline band --}}
        <table class="band">
            <tr>
                <td class="dark">
                    <div class="k">Net payable to owner</div>
                    <div class="v">{{ $net_display }}</div>
                    <div class="m">{{ $status_label }}@if ($iban_masked) · IBAN {{ $iban_masked }}@endif</div>
                </td>
                <td class="stats">
                    <table>
                        <tr><td>Occupancy</td><td class="r">{{ $occupancy_pct }}%</td></tr>
                        <tr><td>Nights sold</td><td class="r">{{ $nights }}</td></tr>
                        <tr><td>Avg. nightly rate</td><td class="r">{{ $adr_display }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Breakdown --}}
        <h2>Breakdown</h2>
        <table class="grid">
            <thead>
                <tr><th>Item</th><th>Detail</th><th class="r">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr class="{{ $line['is_net'] ? 'net' : '' }}">
                        <td>{{ $line['description'] }}@if ($line['unverified']) <span class="d">(provisional)</span>@endif</td>
                        <td class="d">{{ $line['detail'] }}</td>
                        <td class="r {{ $line['is_negative'] ? 'neg' : '' }}">{{ $line['amount_display'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Performance by unit — REAL, reconciles to the statement gross --}}
        @if ($units !== [])
            <h2>Performance by unit</h2>
            <table class="grid">
                <thead>
                    <tr><th>Unit</th><th class="r">Nights</th><th class="r">Occupancy</th><th class="r">Revenue</th></tr>
                </thead>
                <tbody>
                    @foreach ($units as $u)
                        <tr>
                            <td><span class="u-name">{{ $u['name'] }}</span>@if ($u['kind']) <span class="u-kind">· {{ $u['kind'] }}</span>@endif</td>
                            <td class="r">{{ $u['nights'] }}</td>
                            <td class="r">{{ $u['occupancy_pct'] }}%</td>
                            <td class="r">{{ $u['gross_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="tot">
                        <td>Total gross room revenue</td>
                        <td class="r">{{ $unit_total['nights'] }}</td>
                        <td class="r">{{ $unit_total['occupancy_pct'] }}%</td>
                        <td class="r">{{ $unit_total['gross_display'] }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Payout note --}}
        <div class="callout">
            <b>Payout.</b> The net amount of {{ $net_display }} settles by bank transfer{{ $iban_masked ? ' to IBAN '.$iban_masked : '' }}.
            @if ($commission_pct !== null)Management commission is charged at {{ $commission_pct }}% of gross room revenue; @endif
            payment and platform fees are passed through at cost. All amounts are in {{ $currency }} and include applicable taxes collected on your behalf.
            @if ($fx) Converted from {{ $fx['source'] }} at {{ $fx['rate'] }} (source: {{ $fx['src'] }}).@endif
            @if ($has_unverified) Some lines are provisional, pending channel reconciliation.@endif
        </div>

        {{-- Footer. The two cells share one row, so without an explicit split dompdf broke the date across
             lines ("generated 9 Aug / 2026") and hyphenated the contact address mid-domain. The right cell is
             sized to its own content and kept unbreakable; the left takes the remainder. --}}
        <table class="foot" width="100%">
            <tr>
                <td style="width:62%;">{{ $company }} · statement {{ $doc_ref }}@if ($sequence_no) · no. {{ $sequence_no }}@endif · generated <span style="white-space:nowrap;">{{ $issued }}</span></td>
                <td style="text-align:right; white-space:nowrap;">@if ($billing_email)Questions? {{ $billing_email }}@endif</td>
            </tr>
        </table>
    </div>
</body>
</html>
