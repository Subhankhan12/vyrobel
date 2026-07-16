{{-- Owner booking summary PDF (Wave 1 · Sub-gate 2). PDF-SAFE: dompdf renders HTML4/CSS2.1, so this is a
     table-based, inline-CSS branded document (NOT the app's frosted glass) — no flex/grid/backdrop-filter,
     no external assets. DejaVu Sans is dompdf's bundled Unicode font — it renders € and the U+2212 minus.
     Every figure is a REAL attributed owner_ledger room_revenue row: the tables sum EXACTLY to the
     statement's gross line, so this reconciles to the on-screen statement to the cent.
     OMITTED on purpose (see OwnerBookingSummaryDocument's docblock): occupancy (no honest owner-scoped
     read), the guest column (owner surfaces show no guest names), and any OTA brand (not modelled). --}}
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
        h2 { font-size: 12px; margin: 22px 0 0; letter-spacing: .04em; text-transform: uppercase; color: #8A8079; }
        .hdr td { vertical-align: top; }
        .meta { width: 100%; border-collapse: collapse; margin: 24px 0 6px; }
        .meta td { padding: 4px 0; font-size: 12px; }
        .meta .k { color: #8A8079; width: 120px; }
        table.kpi { width: 100%; border-collapse: collapse; margin-top: 18px; }
        table.kpi td { width: 33.33%; padding: 12px 14px; border: 1px solid #EFE3D9; vertical-align: top; }
        table.kpi .kpi-k { color: #8A8079; font-size: 9px; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
        table.kpi .kpi-v { font-size: 19px; font-weight: bold; padding-top: 5px; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.lines th { text-align: left; font-size: 9px; letter-spacing: .06em; text-transform: uppercase; color: #8A8079; border-bottom: 1px solid #EFE3D9; padding: 8px 4px; }
        table.lines th.r, table.lines td.r { text-align: right; }
        table.lines td { padding: 8px 4px; font-size: 12px; border-bottom: 1px solid #F2E9E1; }
        table.lines tr.net td { border-top: 2px solid #C2410C; border-bottom: none; font-weight: bold; font-size: 13px; padding-top: 11px; }
        .sub { color: #8A8079; font-size: 10px; }
        .foot { margin-top: 26px; padding-top: 12px; border-top: 1px solid #EFE3D9; font-size: 10px; color: #A69A92; }
        .reconcile { margin-top: 8px; font-size: 11px; color: #4A4038; }
        .note { margin-top: 8px; font-size: 10.5px; color: #4A4038; }
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
                    <div class="eyebrow">Booking summary</div>
                    <h1>{{ $period_label }}</h1>
                    {{-- The REAL statement number (stamped at finalize); omitted when there is none --}}
                    @if ($sequence_no)
                        <div class="muted" style="margin-top:4px; font-size:11px;">Statement #{{ $sequence_no }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Meta --}}
        <table class="meta">
            <tr><td class="k">Owner</td><td>{{ $owner_name }}</td></tr>
            <tr><td class="k">Period</td><td>{{ $period_range }}</td></tr>
            <tr><td class="k">Currency</td><td>{{ $currency }}</td></tr>
        </table>

        {{-- KPI band — REAL counts + DERIVED folds only. NO occupancy: it is not an attributed value and
             the only real read is room-type-wide, which would leak non-owner units' numbers. --}}
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
                    <div class="kpi-k">Gross revenue</div>
                    <div class="kpi-v">{{ $gross_display }}</div>
                </td>
            </tr>
        </table>

        @if ($bookings === [])
            <div class="note" style="margin-top:18px">
                This statement's gross room revenue is not attributed to any booking in this period.
            </div>
        @else
            {{-- ── By unit — totals reconcile to the gross above ── --}}
            <h2>By unit</h2>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th class="r">Bookings</th>
                        <th class="r">Nights</th>
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
                            <td class="r">{{ $row['revenue_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="net">
                        <td>Total</td>
                        <td class="r">{{ $total_bookings_in_units }}</td>
                        <td class="r">{{ $total_nights_display }}</td>
                        <td class="r">{{ $total_display }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ── By source — the REAL reservations.source enum ONLY. The OTA brand is not modelled, so no
                 channel-brand split is shown. ── --}}
            <h2>By source</h2>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Source</th>
                        <th class="r">Bookings</th>
                        <th class="r">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($by_source as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="r">{{ $row['bookings'] }}</td>
                            <td class="r">{{ $row['revenue_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="net">
                        <td>Total</td>
                        <td class="r">{{ $bookings_count }}</td>
                        <td class="r">{{ $total_display }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ── Every booking behind the gross — the full list, never truncated ── --}}
            <h2>Bookings in this period</h2>
            <table class="lines">
                <thead>
                    <tr>
                        <th>Ref</th>
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
                            <td>{{ $row['unit_code'] }}</td>
                            <td>{{ $row['dates'] ?? '—' }}</td>
                            <td class="r">{{ $row['nights'] }}</td>
                            <td class="r">{{ $row['revenue_display'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="net">
                        <td colspan="3">Total</td>
                        <td class="r">{{ $total_nights_display }}</td>
                        <td class="r">{{ $total_display }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <div class="reconcile">
            These bookings generated this period's gross room revenue of <strong>{{ $gross_display }}</strong>.
            Deductions and your net payable are on statement @if ($sequence_no)#{{ $sequence_no }}@else for this period @endif.
        </div>

        {{-- Facts, stated only when they are true — never a placeholder --}}
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

        <div class="foot">
            Guest details are not shown on owner documents.
            This summary is generated from Vyrobel's owner ledger and reconciles to the gross room revenue on your statement.
            Vyrobel · {{ $operator }}.
        </div>
    </div>
</body>
</html>
