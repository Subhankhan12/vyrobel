{{-- Operator Dashboard — daily property overview (design pass · Operator Screen 5, completes the
     operator core). Styled on the shared operator design system via <x-operator.shell>. Every KPI is a
     TRUE tenant-scoped figure computed from certified data (occupancy from the Availability spine,
     counts from FrontDeskService/HousekeepingService, payments from the ledger). Read-only. --}}
<div>
    <x-operator.shell active="dashboard" title="Dashboard"
        :subtitle="($property['name'] ?? 'No property yet') . ' · ' . $day['label']" :viewer="$viewer">

        {{-- REAL day navigation (GET links) + honest NO-BACKEND affordances --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-icobtn" href="?date={{ $day['prev'] }}" aria-label="Previous day" title="Previous day">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $day['label'] }}
                </span>
                <a class="vy-icobtn" href="?date={{ $day['next'] }}" aria-label="Next day" title="Next day">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                @unless ($day['is_today'])
                    <a class="vy-pill" href="?date={{ $day['today'] }}">Today</a>
                @endunless
            </div>
            <div class="vy-right">
                <span class="vy-pill is-soon" aria-disabled="true">Custom range <span class="vy-soon-tag">Soon</span></span>
                <span class="vy-pill is-soon" aria-disabled="true">Export <span class="vy-soon-tag">Soon</span></span>
            </div>
        </div>

        @if ($property === null)
            <div class="vy-card vy-fade"><div class="vy-empty">No property yet — add a property to see its dashboard.</div></div>
        @else
            {{-- KPI TILES — every figure is REAL + tenant-scoped --}}
            <div class="vy-dash-kpis vy-fade">
                <div class="vy-dash-kpi is-feature">
                    <div class="vy-dk-label">Occupancy tonight</div>
                    <div class="vy-dk-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span></div>
                    <div class="vy-dk-track"><span style="width: {{ min(100, $kpis['occupancy_pct']) }}%"></span></div>
                    <div class="vy-dk-sub">{{ $kpis['rooms_sold'] }} of {{ $kpis['rooms_total'] }} rooms sold</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Rooms available</div>
                    <div class="vy-dk-val">{{ $kpis['rooms_available'] }}</div>
                    <div class="vy-dk-sub">of {{ $kpis['rooms_total'] }} total</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Arrivals · departures</div>
                    <div class="vy-dk-val">{{ $kpis['arrivals'] }} <span class="vy-dk-unit">/ {{ $kpis['departures'] }}</span></div>
                    <div class="vy-dk-sub">expected today</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">In-house</div>
                    <div class="vy-dk-val">{{ $kpis['in_house'] }}</div>
                    <div class="vy-dk-sub">staying tonight</div>
                </div>
            </div>

            <div class="vy-summary vy-fade" style="margin-top: 14px;">
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $housekeeping['to_clean'] }}</span><span class="vy-sc-label">To clean</span></div>
                <div class="vy-summary-chip"><span class="vy-sc-num">{{ $kpis['payments'] }}</span><span class="vy-sc-label">Payments today</span></div>
            </div>

            <div class="vy-dash-cols">
                {{-- Today's arrivals — REAL, names only (no PII) --}}
                <div class="vy-card">
                    <div class="vy-detail-head" style="margin-bottom: 6px;">
                        <h2 class="vy-card-title">Today’s arrivals</h2>
                        <a class="vy-arr-link" href="{{ route('app.front-desk') }}?date={{ $day['date'] }}">Front desk →</a>
                    </div>
                    @forelse ($arrivals as $a)
                        <div class="vy-arr-row">
                            <div>
                                <div class="vy-arr-name">{{ $a['guest'] }}</div>
                                <div class="vy-arr-ref">{{ $a['reference'] }}</div>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="vy-tag tone-{{ $a['tone'] }}"><span class="vy-dot"></span>{{ $a['status_label'] }}</span>
                                <a class="vy-arr-link" href="{{ route('app.reservations.show', $a['id']) }}">Open →</a>
                            </div>
                        </div>
                    @empty
                        <div class="vy-empty">No arrivals for this day.</div>
                    @endforelse
                </div>

                {{-- Right rail: housekeeping snapshot (REAL) + deferred analytics --}}
                <div class="vy-stack">
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">Housekeeping</h2>
                        <div class="vy-hk-snapshot">
                            <div class="vy-hk-line"><span class="vy-hk-k"><span class="vy-hk-swatch" style="background: var(--vy-amber-2)"></span>Pending</span><span class="vy-hk-v">{{ $housekeeping['pending'] }}</span></div>
                            <div class="vy-hk-line"><span class="vy-hk-k"><span class="vy-hk-swatch" style="background: var(--vy-accent-3)"></span>In progress</span><span class="vy-hk-v">{{ $housekeeping['in_progress'] }}</span></div>
                            <div class="vy-hk-line"><span class="vy-hk-k"><span class="vy-hk-swatch" style="background: var(--vy-accent)"></span>Clean</span><span class="vy-hk-v">{{ $housekeeping['clean'] }}</span></div>
                            <div class="vy-hk-line"><span class="vy-hk-k"><span class="vy-hk-swatch" style="background: var(--vy-dark)"></span>Inspected</span><span class="vy-hk-v">{{ $housekeeping['inspected'] }}</span></div>
                        </div>
                        <a class="vy-arr-link" href="{{ route('app.housekeeping') }}" style="display:inline-block; margin-top:12px;">Open board →</a>
                    </div>

                    {{-- DEFERRED: a revenue/ADR/RevPAR trend needs a series aggregation this pass doesn't build.
                         Flagged honestly rather than shown as a fabricated chart. --}}
                    <div class="vy-card">
                        <div class="vy-detail-head"><span class="vy-eyebrow">Revenue trend</span><span class="vy-soon-tag">Not yet available</span></div>
                        <div class="vy-empty" style="padding: 26px 12px;">
                            ADR · RevPAR · revenue-over-time are not computed yet — no fabricated series is shown.
                            Today’s collected payments appear above.
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
