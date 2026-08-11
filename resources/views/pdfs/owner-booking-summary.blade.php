{{-- Owner booking summary PDF (GATE 2 · wireframe-exact). PDF-SAFE: dompdf renders HTML4/CSS2.1, so this is a
     table-based, inline-CSS branded document (NOT the app's frosted glass) — no flex/grid/backdrop-filter, no
     external assets. DejaVu Sans is dompdf's bundled Unicode font — it renders € and the U+2212 minus.
     Every revenue figure is a REAL attributed owner_ledger room_revenue row: the tables sum EXACTLY to the
     statement's gross line, so this reconciles to the on-screen statement to the cent. GATE 2 added REAL
     occupancy (owner-unit-scoped), the guest NAME (name-only, #28), the per-channel commission (real ota_cost),
     and the operator's real VAT/country letterhead. Still refused: the OTA brand (not modelled).
     All composed strings (letterhead address, subtitle, reconciliation note, footer) are built in the document
     builder (OwnerBookingSummaryDocument), so this view stays logic-free. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 12px; }
        .wrap { padding: 36px 40px 0; }
        .brand-mark { display: inline-block; width: 30px; height: 30px; background-color: #C2410C; color: #fff; text-align: center; font-size: 18px; font-weight: bold; border-radius: 7px; }
        .muted { color: #5F5853; }
        .eyebrow { color: #5F5853; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 12px 0 0; }
        .subtitle { color: #5F5853; font-size: 12px; margin-top: 5px; }
        h2 { font-size: 12px; margin: 22px 0 0; letter-spacing: .04em; text-transform: uppercase; color: #5F5853; }
        .hdr td { vertical-align: top; }
        .lead-co { font-size: 17px; font-weight: bold; }
        .lead-addr { color: #5F5853; margin-top: 5px; font-size: 10.5px; }
        .doc-ref { font-size: 13px; font-weight: bold; margin-top: 3px; }

        table.kpi { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.kpi td { width: 25%; padding: 12px 14px; border: 1px solid #EFE3D9; vertical-align: top; }
        table.kpi td.hl { background-color: #FBF0E9; border-color: #F3D8C7; }
        table.kpi .kpi-k { color: #5F5853; font-size: 9px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
        table.kpi .kpi-v { font-size: 19px; font-weight: bold; padding-top: 5px; }
        table.kpi .kpi-v.hl { color: #C2410C; }

        table.lines { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #5F5853; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 8px 4px; font-size: 12px; border-bottom: 1px solid #F2E9E1; vertical-align: top; }
        table.lines tr.net td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 13px; padding-top: 11px; }
        table.lines tr.net td.hl { color: #C2410C; }
        .sub { color: #5F5853; font-size: 10px; }

        .callout { margin-top: 24px; background-color: #FBF0E9; border-left: 3px solid #C2410C; padding: 14px 16px; font-size: 11px; color: #4A4038; }
        .callout .note { margin-top: 8px; }
        .foot { margin-top: 18px; padding-top: 12px; border-top: 1px solid #EFE3D9; }
        .foot td { font-size: 10px; color: #6C645F; }
    </style>
</head>
<body>
    <div class="wrap">
        {{-- Letterhead — the operator's REAL registered identity (billing_company + country + VAT). --}}
        <table class="hdr" width="100%">
            <tr>
                <td>
                    <span class="brand-mark">V</span>
                    <span class="lead-co" style="padding-left:6px;">{{ $operator_company }}</span>
                    <div class="lead-addr">{{ $letterhead_addr }}</div>
                </td>
                <td style="text-align:right;">
                    <div class="eyebrow">Booking summary</div>
                    <div class="doc-ref">{{ $doc_ref }}</div>
                </td>
            </tr>
        </table>

        <h1>Booking summary</h1>
        <div class="subtitle">{{ $subtitle }}</div>

        {{-- KPI band — REAL counts + DERIVED folds + REAL owner-scoped occupancy; the gross cell is highlighted. --}}
        <table class="kpi">
            <tr>
                <td>
                    <div class="kpi-k">Bookings</div>
                    <div class="kpi-v">{{ $bookings_count }}</div>
                </td>
                <td>
                    <div class="kpi-k">Nights</div>
                    <div class="kpi-v">{{ $nights_total }}</div>
                </td>
                <td>
                    <div class="kpi-k">Occupancy</div>
                    <div class="kpi-v">{{ $occupancy_pct }}%</div>
                </td>
                <td class="hl">
                    <div class="kpi-k">Gross revenue</div>
                    <div class="kpi-v hl">{{ $gross_display }}</div>
                </td>
            </tr>
        </table>

        @if ($bookings === [])
            <div class="callout" style="margin-top:20px">
                This statement's gross room revenue is not attributed to any booking in this period.
            </div>
        @else
            {{-- ── By unit — totals reconcile to the gross; occupancy is a REAL per-unit owner-scoped read ── --}}
            <h2>By unit</h2>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th class="r">Bookings</th>
                        <th class="r">Nights</th>
                        <th class="r">Occupancy</th>
                        <th class="r">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($by_unit as $row)
                        <tr>
                            <td>
                                {{ $row['unit_code'] }}
                                @if ($row['unit_name'])<span class="sub">· {{ $row['unit_name'] }}</span>@endif
                            </td>
                            <td class="r">{{ $row['bookings'] }}</td>
                            <td class="r">{{ $row['nights'] }}</td>
                            <td class="r">{{ $row['occupancy_pct'] }}%</td>
                            <td class="r">{{ $row['revenue_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="net">
                        <td>Total</td>
                        <td class="r">{{ $total_bookings_in_units }}</td>
                        <td class="r">{{ $total_nights_display }}</td>
                        <td class="r">{{ $total_occupancy_pct }}%</td>
                        <td class="r hl">{{ $total_display }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ── By channel — the REAL source enum + the REAL channel commission (Σ ota_cost). No OTA brand. ── --}}
            <h2>By channel</h2>
            <table class="lines">
                <tbody>
                    @foreach ($by_source as $row)
                        <tr>
                            <td>
                                {{ $row['label'] }}
                                <div class="sub">{{ $row['bookings'] }} {{ \Illuminate\Support\Str::plural('booking', $row['bookings']) }} · {{ $row['commission_note'] }}</div>
                            </td>
                            <td class="r">{{ $row['revenue_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="net">
                        <td>Total</td>
                        <td class="r hl">{{ $total_display }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ── Every booking behind the gross — the full list (never truncated) with the guest NAME (name-only). ── --}}
            <h2>Bookings this period</h2>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Ref</th>
                        <th>Guest</th>
                        <th>Unit</th>
                        <th>Dates</th>
                        <th class="r">Nights</th>
                        <th class="r">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $row)
                        <tr>
                            <td>{{ $row['reference'] }}<div class="sub">{{ $row['source_label'] }}</div></td>
                            <td>{{ $row['guest'] }}</td>
                            <td>{{ $row['unit_code'] }}</td>
                            <td>{{ $row['dates'] ?? '—' }}</td>
                            <td class="r">{{ $row['nights'] }}</td>
                            <td class="r">{{ $row['revenue_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="net">
                        <td colspan="4">Total</td>
                        <td class="r">{{ $total_nights_display }}</td>
                        <td class="r hl">{{ $total_display }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Reconciliation callout — references the statement by its REAL number; facts only, never a placeholder. --}}
        <div class="callout">
            {{ $reconcile_note }}
            @if ($reversed_count > 0)
                <div class="note">
                    {{ $reversed_count }} further {{ \Illuminate\Support\Str::plural('booking', $reversed_count) }} claimed by this
                    statement {{ $reversed_count === 1 ? 'was' : 'were' }} fully reversed (for example, cancelled), so
                    {{ $reversed_count === 1 ? 'it' : 'they' }} contributed nothing to the gross above and
                    {{ $reversed_count === 1 ? 'is' : 'are' }} not listed.
                </div>
            @endif
            @if ($prior_count > 0)
                <div class="note">
                    {{ $prior_count }} {{ \Illuminate\Support\Str::plural('booking', $prior_count) }} claimed by this statement
                    {{ $prior_count === 1 ? 'has' : 'have' }} room revenue dated before this period. That revenue is reported on
                    the statement within “Prior-period adjustments”, not in the gross above.
                </div>
            @endif
        </div>

        <table class="foot" width="100%">
            <tr>
                <td>{{ $footer_left }}</td>
                <td style="text-align:right;">Booking summary · {{ $period_label }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
