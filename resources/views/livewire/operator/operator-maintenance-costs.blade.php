{{-- Operator MAINTENANCE COSTS (Phase 1 · Sub-gate 4) — every figure derived from real resolved tickets
     (MaintenanceCostService). No fabricated history; the design's Labor/Parts split is omitted (schema has one
     cost per ticket); empty → "—". Integer money throughout. RAW-BLADE RULE: every `@` follows a space/newline/tag. --}}
<div>
    <x-operator.shell active="maintenance" title="Maintenance costs" :subtitle="'Derived from resolved work orders'" :viewer="$viewer">

        <div style="margin-bottom: 16px;">
            <a href="{{ route('app.maintenance') }}" style="font-size: 12.5px; color: var(--vy-accent); text-decoration: none;">← Back to the board</a>
        </div>

        {{-- KPI cards --}}
        <div class="vymc-kpis vy-fade">
            <div class="vymc-kpi">
                <span class="vymc-kpi-label">Total spend</span>
                <span class="vymc-kpi-val">{{ $hasData ? $money($report['total_minor']) : '—' }}</span>
                <span class="vymc-kpi-sub">across all resolved work orders</span>
            </div>
            <div class="vymc-kpi">
                <span class="vymc-kpi-label">Orders closed</span>
                <span class="vymc-kpi-val">{{ $report['orders_closed'] ?: '—' }}</span>
                <span class="vymc-kpi-sub">resolved tickets</span>
            </div>
            <div class="vymc-kpi">
                <span class="vymc-kpi-label">Average / order</span>
                <span class="vymc-kpi-val">{{ $money($report['avg_minor']) }}</span>
                <span class="vymc-kpi-sub">mean cost per closed order</span>
            </div>
            <div class="vymc-kpi">
                <span class="vymc-kpi-label">On owner units</span>
                <span class="vymc-kpi-val">{{ $hasData ? $money($report['owner_units_minor']) : '—' }}</span>
                <span class="vymc-kpi-sub">rechargeable · operator: {{ $hasData ? $money($report['operator_units_minor']) : '—' }}</span>
            </div>
        </div>

        @unless ($hasData)
            <div class="vy-card vy-fade vymc-empty">No resolved work orders yet — cost figures appear here once maintenance tickets are closed with a cost.</div>
        @else
            <div class="vymc-cols">
                {{-- Spend by category --}}
                <div class="vy-card vy-fade">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Spend by category</h2>
                    @foreach ($report['by_category'] as $row)
                        <div class="vymc-row">
                            <span class="vymc-row-name">{{ $categoryLabel($row['category']) }}</span>
                            <span class="vymc-row-meta">
                                <span class="vymc-count">{{ $row['count'] }} {{ $row['count'] === 1 ? 'order' : 'orders' }}</span>
                                <strong>{{ $money($row['total_minor']) }}</strong>
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- Top units --}}
                <div class="vy-card vy-fade">
                    <h2 class="vy-card-title" style="margin-bottom: 12px;">Top units by spend</h2>
                    @forelse ($report['top_units'] as $row)
                        <div class="vymc-row">
                            <span class="vymc-row-name"><strong>{{ $row['code'] }}</strong> <span style="color: var(--vy-muted); font-size: 12px;">{{ $row['name'] }}</span></span>
                            <span class="vymc-row-meta">
                                <span class="vymc-count">{{ $row['count'] }} {{ $row['count'] === 1 ? 'order' : 'orders' }}</span>
                                <strong>{{ $money($row['total_minor']) }}</strong>
                            </span>
                        </div>
                    @empty
                        <div style="color: var(--vy-muted); font-size: 12.5px;">—</div>
                    @endforelse
                </div>
            </div>

            {{-- Spend by month --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <h2 class="vy-card-title" style="margin-bottom: 12px;">Spend by month</h2>
                @foreach ($report['by_month'] as $row)
                    <div class="vymc-row">
                        <span class="vymc-row-name">{{ $monthLabel($row['month']) }}</span>
                        <span class="vymc-row-meta">
                            <span class="vymc-count">{{ $row['count'] }} {{ $row['count'] === 1 ? 'order' : 'orders' }}</span>
                            <strong>{{ $money($row['total_minor']) }}</strong>
                        </span>
                    </div>
                @endforeach
            </div>
        @endunless

        {{-- Honest deferrals --}}
        <div class="vy-card vy-fade" style="margin-top: 16px;">
            <div style="font-size: 11.5px; color: var(--vy-faint); line-height: 1.6;">
                A labour-vs-parts breakdown isn’t shown — a work order carries a single cost, not a split, so that
                figure would be invented. PDF / Excel export and “email to accountant” are coming soon.
            </div>
        </div>

    </x-operator.shell>

    <style>
        .vymc-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
        .vymc-kpi { background: var(--vy-glass); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md, 12px); padding: 14px 16px; display: flex; flex-direction: column; gap: 4px; }
        .vymc-kpi-label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--vy-muted); }
        .vymc-kpi-val { font-size: 22px; font-weight: 700; color: var(--vy-ink); font-family: var(--vy-display, inherit); }
        .vymc-kpi-sub { font-size: 11px; color: var(--vy-faint); }
        .vymc-empty { text-align: center; padding: 32px 24px; color: var(--vy-muted); font-size: 13px; }
        .vymc-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .vymc-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 0; font-size: 13px; color: var(--vy-ink); }
        .vymc-row + .vymc-row { border-top: 1px solid var(--vy-hairline); }
        .vymc-row-meta { display: flex; align-items: center; gap: 12px; }
        .vymc-count { font-size: 11.5px; color: var(--vy-muted); }
        @media (max-width: 860px) { .vymc-kpis { grid-template-columns: repeat(2, 1fr); } .vymc-cols { grid-template-columns: 1fr; } }
    </style>
</div>
