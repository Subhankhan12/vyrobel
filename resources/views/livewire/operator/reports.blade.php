{{-- Operator Reports (Phase 6b · SG2) — a period report on the ARRIVAL basis, scoped by the Phase-6a
     property context. Revenue is Σ REAL folio items (reconciles to FolioTotals); the only real operator
     cost captured today is MAINTENANCE; the prototype's other expense lines have NO data source and are
     HONESTLY listed as "not tracked yet", never fabricated. No "best month on record" narrative. --}}
<div>
    <x-operator.shell active="reports" title="Reports"
        :subtitle="$scopeLabel . ' · arrivals in ' . $activeRange['label']" :viewer="$viewer">

        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                @foreach ($ranges as $r)
                    <a class="vy-pill @if ($r['active']) is-on @endif" href="?range={{ $r['key'] }}">{{ $r['label'] }}</a>
                @endforeach
                {{-- GATE 10.1 — the prototype's "Reporting month" picker: jump to a specific calendar month (real
                     ?month=YYYY-MM; the shared ReportPeriod resolves it for the page + both exports). --}}
                <select class="vy-input" style="padding:5px 10px;font-size:12px;max-width:150px" aria-label="Reporting month"
                        onchange="if (this.value) window.location.href='?month='+this.value;">
                    <option value="">Jump to month…</option>
                    @foreach ($months as $mo)
                        <option value="{{ $mo['value'] }}" @selected($mo['active'])>{{ $mo['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vy-right">
                {{-- #104 GATE 2 — real CSV export (was a dead "Soon" pill); the same figures the page shows. --}}
                <a class="vy-pill" href="{{ $exportUrl }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                    Export CSV
                </a>
            </div>
        </div>

        @if ($empty)
            <div class="vy-card vy-fade"><div class="vy-empty">No arrivals or costs in {{ $activeRange['label'] }} for {{ $scopeLabel }} yet. Real data only — nothing is invented for an empty period.</div></div>
        @else
            {{-- GATE 10.1 — the prototype's KPI lead: REAL performance metrics (occupancy · ADR · RevPAR, stay-night
                 basis via PerformanceMetrics) + gross revenue (folio basis). Each performance KPI shows a vs-last-year
                 delta ONLY where the prior-year period has data — never a fabricated trend (#39). --}}
            <div class="vy-dash-kpis vy-dash-kpis--4 vy-fade" style="margin-bottom: 6px;">
                @foreach ($kpis as $c)
                    <div class="vy-dash-kpi">
                        <div class="vy-dk-label">{{ $c['label'] }}</div>
                        <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                            <div class="vy-dk-val">{{ $c['value'] }}</div>
                            @if ($c['delta'] !== null)
                                <span style="font-size: 12px; font-weight: 700; color: {{ $c['delta_dir'] === 'down' ? '#8F3110' : 'var(--vy-accent)' }};">{{ $c['delta'] }}</span>
                            @endif
                        </div>
                        <div class="vy-dk-sub">{{ $c['sub'] }}</div>
                    </div>
                @endforeach
            </div>
            <p class="vy-fade" style="font-size: 11px; color: var(--vy-faint); margin: 0 0 16px;">Occupancy · ADR · RevPAR are on the stay-night basis; deltas are vs the same period last year, shown only where prior-year data exists. Revenue below is on the folio (arrival) basis.</p>

            <div class="vy-ins-grid vy-fade">
                {{-- REVENUE — real folio breakdown --}}
                <div class="vy-card">
                    <div class="vy-card-head"><h3>Revenue</h3><span class="vy-card-note">{{ $arrivals }} {{ \Illuminate\Support\Str::plural('arrival', $arrivals) }}</span></div>
                    <div class="vy-report-line"><span>Room</span><span>{{ $revenue['room'] }}</span></div>
                    <div class="vy-report-line"><span>Ancillary <span class="vy-fl-note">· add-ons, products, fees</span></span><span>{{ $revenue['ancillary'] }}</span></div>
                    <div class="vy-report-line vy-report-total"><span>Gross revenue</span><span>{{ $revenue['gross'] }}</span></div>
                    <div class="vy-report-line vy-report-muted"><span>Tax collected <span class="vy-fl-note">· pass-through</span></span><span>{{ $revenue['tax'] }}</span></div>
                </div>

                {{-- COSTS — maintenance real; the rest honestly not tracked --}}
                <div class="vy-card">
                    <div class="vy-card-head"><h3>Costs</h3><span class="vy-card-note">real data only</span></div>
                    <div class="vy-report-line"><span>Maintenance <span class="vy-fl-note">· resolved work orders</span></span><span>{{ $expenses['maintenance'] }}</span></div>
                    @foreach ($expenses['untracked'] as $cat)
                        <div class="vy-report-line vy-report-muted"><span>{{ $cat }}</span><span class="vy-soon-tag">Not tracked yet</span></div>
                    @endforeach
                    <div class="vy-report-line vy-report-total"><span>Net (revenue − tracked costs)</span><span>{{ $net }}</span></div>
                </div>
            </div>

            {{-- TOP PERFORMERS — real room revenue per unit --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>Top units</h3><span class="vy-card-note">by room revenue · arrivals in {{ $activeRange['label'] }}</span></div>
                @if (count($topUnits) === 0)
                    <div class="vy-empty">No unit revenue in this range yet.</div>
                @else
                    <table class="vy-table">
                        <thead><tr><th>Unit</th><th class="vy-num">Nights</th><th class="vy-num">Room revenue</th></tr></thead>
                        <tbody>
                            @foreach ($topUnits as $u)
                                <tr>
                                    <td>{{ $u['code'] }}@if ($u['name'] !== $u['code'])<span class="vy-fl-note"> · {{ $u['name'] }}</span>@endif</td>
                                    <td class="vy-num">{{ $u['nights'] }}</td>
                                    <td class="vy-num">{{ $u['revenue'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- #104 GATE 2 — REAL revenue-by-channel table (the reservation's real `source`; OTA brand honest) --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>Revenue by channel</h3><span class="vy-card-note">by booking source · reconciles to gross</span></div>
                @if (count($byChannel) === 0)
                    <div class="vy-empty">No channel revenue in this range yet.</div>
                @else
                    <table class="vy-table">
                        <thead><tr><th>Channel</th><th class="vy-num">Bookings</th><th class="vy-num">Revenue</th><th class="vy-num">Share</th></tr></thead>
                        <tbody>
                            @foreach ($byChannel as $c)
                                <tr>
                                    <td>{{ $c['label'] }}</td>
                                    <td class="vy-num">{{ $c['bookings'] }}</td>
                                    <td class="vy-num">{{ $c['revenue'] }}</td>
                                    <td class="vy-num">{{ $c['share'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- #104 GATE 2 — REAL revenue-by-month chart (same folio basis; every month a real bucket) --}}
            <div class="vy-card vy-fade" style="margin-top: 14px;">
                <div class="vy-card-head"><h3>Revenue by month</h3><span class="vy-card-note">{{ $activeRange['label'] }}</span></div>
                <x-charts.bars :bars="$byMonth" height="140px" />
            </div>
        @endif
    </x-operator.shell>
</div>
