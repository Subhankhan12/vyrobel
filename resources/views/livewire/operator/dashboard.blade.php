{{-- Operator Dashboard — the front-desk command center (exact-match). Matched to "Operator Dashboard.html"
     on REAL tenant-scoped data via <x-operator.shell> (which also renders the shared read-only banner).
     Every figure is a certified read: occupancy/ADR from the Availability spine + PerformanceMetrics, counts
     from FrontDeskService/HousekeepingTask/MaintenanceTicket, revenue from the payments ledger. Channels are
     HONEST (real status — "Not connected" when nothing is genuinely synced, never a fake "Synced"). --}}
@php
    // #104 GATE 2 — the wireframe's personalized greeting header. Presentation-only: a time-of-day salutation
    // + the authenticated staff member's real first name (no data/route/behaviour change).
    $vyHour = (int) now()->format('G');
    $vyGreet = $vyHour < 12 ? 'Good morning' : ($vyHour < 18 ? 'Good afternoon' : 'Good evening');
    $vyFirst = trim((string) \Illuminate\Support\Str::before((string) ($viewer['name'] ?? ''), ' ')) ?: 'there';
@endphp
<div>
    <x-operator.shell active="dashboard" :title="$vyGreet . ', ' . $vyFirst"
        :subtitle="($property['name'] ?? 'No property yet') . ' · ' . $day['label']" :viewer="$viewer">

        {{-- REAL day navigation (GET links) --}}
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
            {{-- GATE 0 — the inline search entry moved to the bespoke-chrome topbar (shared shell); the
                 redundant in-content search pill was removed here to avoid a duplicate. --}}
        </div>

        @if ($property === null)
            <div class="vy-card vy-fade"><div class="vy-empty">No property yet — add a property to see its dashboard.</div></div>
        @else
            {{-- KPI TILES (design's 5): Arrivals · Departures · Occupancy tonight (+Δ) · Revenue today · Open work orders --}}
            <div class="vy-dash-kpis vy-dash-kpis--5 vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Arrivals today</div>
                    <div class="vy-dk-val">{{ $kpis['arrivals'] }}</div>
                    <div class="vy-dk-sub">expected today</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Departures today</div>
                    <div class="vy-dk-val">{{ $kpis['departures'] }}</div>
                    <div class="vy-dk-sub">expected today</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Occupancy tonight</div>
                    <div class="vy-dk-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span>@if (! is_null($kpis['occupancy_delta']))<span class="vy-dk-delta {{ $kpis['occupancy_delta'] < 0 ? 'is-down' : '' }}">{{ $kpis['occupancy_delta'] >= 0 ? '+' : '' }}{{ $kpis['occupancy_delta'] }}%</span>@endif</div>
                    <div class="vy-dk-sub">{{ $kpis['rooms_sold'] }} of {{ $kpis['rooms_total'] }} rooms sold</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Revenue today</div>
                    <div class="vy-dk-val">{{ $kpis['payments'] }}</div>
                    <div class="vy-dk-sub">collected</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Open work orders</div>
                    <div class="vy-dk-val">{{ $kpis['work_orders'] }}@if ($kpis['work_orders_urgent'] > 0)<span class="vy-dk-urgent">{{ $kpis['work_orders_urgent'] }} urgent</span>@endif</div>
                    <div class="vy-dk-sub">maintenance</div>
                </div>
            </div>

            <div class="vy-dash-cols">
                <div class="vy-stack">
                    {{-- Today's tasks — REAL derived (urgent work orders · rooms to clean · arrivals) --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">Today’s tasks</h2>
                        <div class="vy-task-groups">
                            @foreach ($tasks['groups'] as $g)
                                <span class="vy-task-group">{{ $g['label'] }} <b>{{ $g['count'] }}</b></span>
                            @endforeach
                        </div>
                        {{-- ACTIONABLE — each task links to the page where it's resolved; it auto-clears from
                             REAL state when its count hits 0 (no fabricated "done" flag, #39). --}}
                        @forelse ($tasks['items'] as $t)
                            <a class="vy-task-row is-actionable" href="{{ $t['link'] }}">
                                <span class="vy-task-pri is-{{ $t['priority'] }}"></span>
                                <span class="vy-task-label">{{ $t['label'] }}</span>
                                <span class="vy-task-meta">{{ $t['meta'] }}</span>
                                <span class="vy-task-go" aria-hidden="true">→</span>
                            </a>
                        @empty
                            <div class="vy-empty" style="padding: 20px 12px;">All caught up — nothing needs attention right now.</div>
                        @endforelse
                    </div>

                    {{-- Today's arrivals — REAL (operator sees full guest data; names shown, contact not) --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 6px;">
                            <h2 class="vy-card-title">Arrivals · today</h2>
                            <a class="vy-arr-link" href="{{ route('app.front-desk') }}?date={{ $day['date'] }}">View all →</a>
                        </div>
                        @forelse ($arrivals as $a)
                            <div class="vy-arr-row">
                                <div>
                                    <div class="vy-arr-name">{{ $a['guest'] }}</div>
                                    {{-- #104 GATE 3c — the wireframe's per-arrival detail: reference · N nights · unit (real). --}}
                                    <div class="vy-arr-ref">{{ $a['reference'] }} · {{ $a['nights'] }} {{ \Illuminate\Support\Str::plural('night', $a['nights']) }}@if ($a['unit']) · {{ $a['unit'] }}@else · Unassigned @endif</div>
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

                    {{-- Occupancy · next 7 days — REAL per-night % (certified spine) --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">Occupancy · next 7 days</h2>
                            <span class="vy-eyebrow">This week</span>
                        </div>
                        <div class="vy-occ7">
                            @foreach ($occupancyWeek as $d)
                                <div class="vy-occ7-col">
                                    <span class="vy-occ7-pct">{{ $d['pct'] }}%</span>
                                    <div class="vy-occ7-bar" style="height: {{ max(3, $d['pct']) }}%"></div>
                                    <span class="vy-occ7-day">{{ $d['day'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right rail --}}
                <div class="vy-stack">
                    {{-- TONIGHT — REAL occupancy + rooms sold + ADR --}}
                    <div class="vy-tonight">
                        <span class="vy-eyebrow">Tonight</span>
                        <div class="vy-tonight-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span></div>
                        <div class="vy-tonight-sub">occupancy</div>
                        <div class="vy-tonight-row">
                            <div class="vy-tonight-cell"><div class="vy-tc-k">Rooms sold</div><div class="vy-tc-v">{{ $kpis['rooms_sold'] }}/{{ $kpis['rooms_total'] }}</div></div>
                            <div class="vy-tonight-cell"><div class="vy-tc-k">ADR</div><div class="vy-tc-v">{{ $kpis['adr'] ?? '—' }}</div></div>
                        </div>
                    </div>

                    {{-- Housekeeping — REAL status counts + progress --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 12px;">Housekeeping</h2>
                        @php
                            $hkTotal = $housekeeping['pending'] + $housekeeping['in_progress'] + $housekeeping['clean'] + $housekeeping['inspected'];
                            $hkDone = $housekeeping['clean'] + $housekeeping['inspected'];
                            $hkPct = $hkTotal > 0 ? (int) round($hkDone / $hkTotal * 100) : 0;
                        @endphp
                        <div class="vy-hk-progress"><span style="width: {{ $hkPct }}%"></span></div>
                        <div class="vy-hk-progress-label"><span>{{ $housekeeping['to_clean'] }} to clean</span><span>{{ $hkDone }}/{{ $hkTotal }} done</span></div>
                        {{-- Per-status rail cards — the REAL housekeeping_tasks.status counts. --}}
                        <div class="vy-stat-list" style="margin-top: 14px;">
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#B4400F;"></span><span class="vy-stat-k">Dirty</span><span class="vy-stat-v">{{ $housekeeping['pending'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#E0921E;"></span><span class="vy-stat-k">In progress</span><span class="vy-stat-v">{{ $housekeeping['in_progress'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#33A0AC;"></span><span class="vy-stat-k">Clean</span><span class="vy-stat-v">{{ $housekeeping['clean'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#43A06B;"></span><span class="vy-stat-k">Inspected</span><span class="vy-stat-v">{{ $housekeeping['inspected'] }}</span></div>
                        </div>
                        <a class="vy-arr-link" href="{{ route('app.housekeeping') }}" style="display:inline-block; margin-top:12px;">Open board →</a>
                    </div>

                    {{-- Maintenance — REAL out-of-order units + on-hold nightly rate (honest omit if no rate) --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 8px;">
                            <h2 class="vy-card-title">Maintenance</h2>
                            <a class="vy-arr-link" href="{{ route('app.maintenance') }}">View all →</a>
                        </div>
                        {{-- Per-status rail cards — REAL active-ticket counts (open/urgent/awaiting-parts/out-of-order). --}}
                        <div class="vy-stat-list">
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#8A8079;"></span><span class="vy-stat-k">Open</span><span class="vy-stat-v">{{ $kpis['work_orders'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#E8703A;"></span><span class="vy-stat-k">Urgent</span><span class="vy-stat-v">{{ $kpis['work_orders_urgent'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#33A0AC;"></span><span class="vy-stat-k">Awaiting parts</span><span class="vy-stat-v">{{ $maintenance['awaiting_parts'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#B4400F;"></span><span class="vy-stat-k">Out of order</span><span class="vy-stat-v">{{ $maintenance['out_of_order'] }}</span></div>
                        </div>
                        @if ($maintenance['on_hold'])
                            <div class="vy-mini-row" style="margin-top: 10px; border-top: 1px solid var(--vy-hairline); padding-top: 10px;">
                                <span class="vy-mini-k">Revenue on hold</span>
                                <span class="vy-mini-v">{{ $maintenance['on_hold'] }} / night</span>
                            </div>
                        @endif
                    </div>

                    {{-- Channels — HONEST status (real; "Not connected" when nothing is synced) --}}
                    <div class="vy-card">
                        <h2 class="vy-card-title" style="margin-bottom: 10px;">Channels</h2>
                        @foreach ($channels as $c)
                            <div class="vy-mini-row">
                                <span class="vy-mini-k">{{ $c['name'] }}</span>
                                @if ($c['connected'])
                                    <span class="vy-chan-status is-synced"><span class="vy-dot"></span>Synced{{ $c['last_sync'] ? ' · '.$c['last_sync'] : '' }}</span>
                                @else
                                    <span class="vy-chan-status is-off"><span class="vy-dot"></span>Not connected</span>
                                @endif
                            </div>
                        @endforeach
                        <div class="vy-line-sub" style="margin-top:8px;font-size:11.5px;color:var(--vy-muted)">Channel sync activates with your OTA connection.</div>
                    </div>
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
