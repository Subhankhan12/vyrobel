{{-- Operator Reports PDF (GATE 10.2) — the wireframe's "PDF report" (revenue · KPIs · channel mix). PDF-SAFE:
     dompdf renders HTML4/CSS2.1 — table-based, inline CSS, DejaVu Sans, no flex/grid/external assets. Built from
     the SAME App\Support\Reports\OperatorReportData the screen renders, so the printed report reconciles with the
     page to the cent. HONEST (#39): KPI deltas print only where prior-year data exists; the untracked expense
     lines print "not tracked yet", never fabricated; no narrative. Scope + period are the SELECTED context. --}}
@php
    $brand = \App\Support\Branding\PropertyBrand::default();
    $money = fn (int $m): string => \App\Support\Money\MoneyFormatter::display($m, $currency);
    $signMoney = fn (?int $m): ?string => $m === null ? null : (($m >= 0 ? '+' : '−').\App\Support\Money\MoneyFormatter::display(abs($m), $currency));
    $signPp = fn (?int $n): ?string => $n === null ? null : (($n > 0 ? '+' : ($n < 0 ? '−' : '')).abs($n).'pp');
    $monthMax = max(1, ...array_map(fn ($b): int => $b['revenue_minor'], $data['by_month'] ?: [['revenue_minor' => 0]]));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { margin: 0; color: #2B2320; font-size: 11px; }
        .wrap { padding: 30px 34px; }
        .brand-mark { display: inline-block; width: 28px; height: 28px; background-color: {{ $brand->accent }}; color: #fff; text-align: center; font-size: 16px; font-weight: bold; border-radius: 6px; }
        .eyebrow { color: {{ $brand->accent }}; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { font-size: 18px; margin: 3px 0 0; }
        .muted { color: #5F5853; }
        h2 { font-size: 12px; margin: 20px 0 6px; }
        table.sum { width: 100%; border-collapse: collapse; margin: 14px 0 4px; }
        table.sum td { border: 1px solid #EFE3D9; padding: 8px 10px; text-align: center; width: 25%; }
        .sum-n { font-size: 16px; font-weight: bold; color: #2B2320; }
        .sum-l { font-size: 8.5px; letter-spacing: .04em; text-transform: uppercase; color: #5F5853; }
        .sum-d { font-size: 9px; font-weight: bold; }
        table.pl { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.pl td { padding: 6px 4px; font-size: 11px; border-bottom: 1px solid #F2E8DF; }
        table.pl td.n { text-align: right; }
        table.pl tr.total td { font-weight: bold; border-top: 1px solid #D9CBBE; border-bottom: none; }
        .soon { color: #6C645F; font-size: 9px; font-style: italic; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.grid th, table.grid td { border-bottom: 1px solid #F2E8DF; padding: 6px 5px; font-size: 10px; text-align: right; }
        table.grid th { background-color: #FBF5EF; color: #5F5853; font-size: 8.5px; letter-spacing: .04em; text-transform: uppercase; }
        table.grid th.l, table.grid td.l { text-align: left; }
        .bar-cell { width: 120px; }
        .bar-track { background-color: #F2E8DF; height: 8px; }
        .bar-fill { background-color: {{ $brand->accent }}; height: 8px; }
        .foot { margin-top: 22px; padding-top: 10px; border-top: 1px solid #EFE3D9; font-size: 9px; color: #6C645F; }
    </style>
</head>
<body>
<div class="wrap">
    <table style="width:100%;border-collapse:collapse"><tr>
        <td style="vertical-align:top"><span class="brand-mark">{{ $brand->initial }}</span></td>
        <td style="vertical-align:top;padding-left:10px">
            <div class="eyebrow">Reports</div>
            <h1>{{ $scopeLabel }}</h1>
            <div class="muted" style="font-size:10px;margin-top:2px">Arrivals in {{ $range['label'] }}</div>
        </td>
        <td style="vertical-align:top;text-align:right" class="muted">
            <div>{{ $data['arrivals'] }} {{ \Illuminate\Support\Str::plural('arrival', $data['arrivals']) }}</div>
        </td>
    </tr></table>

    {{-- KPIs — occupancy · ADR · RevPAR (stay-night basis) + gross (folio basis); deltas only where LY has data --}}
    <table class="sum"><tr>
        <td>
            <div class="sum-n">{{ $data['occupancy_pct'] }}%
                @if ($signPp($data['occupancy_delta']) !== null)<span class="sum-d" style="color:{{ $data['occupancy_delta'] < 0 ? '#8F3110' : $brand->accent }}">{{ $signPp($data['occupancy_delta']) }}</span>@endif
            </div>
            <div class="sum-l">Occupancy</div>
        </td>
        <td>
            <div class="sum-n">{{ $money($data['adr_minor']) }}
                @if ($signMoney($data['adr_delta']) !== null)<span class="sum-d" style="color:{{ $data['adr_delta'] < 0 ? '#8F3110' : $brand->accent }}">{{ $signMoney($data['adr_delta']) }}</span>@endif
            </div>
            <div class="sum-l">ADR</div>
        </td>
        <td>
            <div class="sum-n">{{ $money($data['revpar_minor']) }}
                @if ($signMoney($data['revpar_delta']) !== null)<span class="sum-d" style="color:{{ $data['revpar_delta'] < 0 ? '#8F3110' : $brand->accent }}">{{ $signMoney($data['revpar_delta']) }}</span>@endif
            </div>
            <div class="sum-l">RevPAR</div>
        </td>
        <td>
            <div class="sum-n">{{ $money($data['gross_minor']) }}</div>
            <div class="sum-l">Gross revenue</div>
        </td>
    </tr></table>
    <div class="muted" style="font-size:8.5px;margin-bottom:4px">Occupancy · ADR · RevPAR are the stay-night basis, vs the same period last year where prior-year data exists. Revenue below is the folio (arrival) basis.</div>

    {{-- Revenue P&L (folio basis) + Costs (maintenance real, the rest honestly not tracked) --}}
    <table style="width:100%;border-collapse:collapse"><tr>
        <td style="width:50%;vertical-align:top;padding-right:12px">
            <h2>Revenue</h2>
            <table class="pl">
                <tr><td>Room</td><td class="n">{{ $money($data['room_minor']) }}</td></tr>
                <tr><td>Ancillary <span class="muted">· add-ons, products, fees</span></td><td class="n">{{ $money($data['ancillary_minor']) }}</td></tr>
                <tr class="total"><td>Gross revenue</td><td class="n">{{ $money($data['gross_minor']) }}</td></tr>
                <tr><td class="muted">Tax collected · pass-through</td><td class="n muted">{{ $money($data['tax_minor']) }}</td></tr>
            </table>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:12px">
            <h2>Costs <span class="muted" style="font-size:9px">· real data only</span></h2>
            <table class="pl">
                <tr><td>Maintenance <span class="muted">· resolved work orders</span></td><td class="n">{{ $money($data['maintenance_minor']) }}</td></tr>
                @foreach ($data['untracked'] as $cat)
                    <tr><td class="muted">{{ $cat }}</td><td class="n"><span class="soon">not tracked yet</span></td></tr>
                @endforeach
                <tr class="total"><td>Net (revenue − tracked costs)</td><td class="n">{{ $money($data['net_minor']) }}</td></tr>
            </table>
        </td>
    </tr></table>

    {{-- Revenue by channel (real source) --}}
    <h2>Revenue by channel</h2>
    <table class="grid">
        <thead><tr><th class="l">Channel</th><th>Bookings</th><th>Revenue</th><th>Share</th></tr></thead>
        <tbody>
            @forelse ($data['by_channel'] as $c)
                <tr>
                    <td class="l">{{ \App\Support\Reports\OperatorRevenueBreakdown::sourceLabel($c['source']) }}</td>
                    <td>{{ $c['bookings'] }}</td>
                    <td>{{ $money($c['revenue_minor']) }}</td>
                    <td>{{ $c['share'] }}%</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="4">No channel revenue in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Top units by real room revenue --}}
    <h2>Top units</h2>
    <table class="grid">
        <thead><tr><th class="l">Unit</th><th>Nights</th><th>Room revenue</th></tr></thead>
        <tbody>
            @forelse ($data['top_units'] as $u)
                <tr>
                    <td class="l">{{ $u['code'] }}@if ($u['name'] !== $u['code']) <span class="muted">· {{ $u['name'] }}</span>@endif</td>
                    <td>{{ $u['nights'] }}</td>
                    <td>{{ $money($u['revenue_minor']) }}</td>
                </tr>
            @empty
                <tr><td class="l muted" colspan="3">No unit revenue in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Revenue by month (real buckets, proportional bar) --}}
    <h2>Revenue by month</h2>
    <table class="grid">
        <thead><tr><th class="l">Month</th><th class="l">&nbsp;</th><th>Revenue</th></tr></thead>
        <tbody>
            @foreach ($data['by_month'] as $b)
                <tr>
                    <td class="l">{{ $b['label'] }}</td>
                    <td class="l bar-cell"><div class="bar-track"><div class="bar-fill" style="width:{{ (int) round($b['revenue_minor'] * 100 / $monthMax) }}%">&nbsp;</div></div></td>
                    <td>{{ $money($b['revenue_minor']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="foot">
        Computed from real folio &amp; maintenance data for {{ $scopeLabel }} · {{ $range['label'] }}. Occupancy/ADR/RevPAR are on the stay-night basis; revenue is on the folio (arrival) basis. Figures reconcile to the Reports screen. Vyrobel.
    </div>
</div>
</body>
</html>
