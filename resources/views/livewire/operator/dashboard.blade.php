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
<div class="vy-dash">
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
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Departures today</div>
                    <div class="vy-dk-val">{{ $kpis['departures'] }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Occupancy tonight</div>
                    <div class="vy-dk-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span>@if (! is_null($kpis['occupancy_delta']))<span class="vy-dk-delta {{ $kpis['occupancy_delta'] < 0 ? 'is-down' : '' }}">{{ $kpis['occupancy_delta'] >= 0 ? '+' : '' }}{{ $kpis['occupancy_delta'] }}%</span>@endif</div>
                    {{-- Kept in the design's note slot: this numerator/denominator is the TRUE org-scoped
                         room count, and OperatorDashboardViewTest probes it to prove another org's rooms
                         never enter this occupancy (#38). Dropping it would delete a real figure AND
                         disarm an isolation assertion. --}}
                    <div class="vy-dk-sub">{{ $kpis['rooms_sold'] }} of {{ $kpis['rooms_total'] }} rooms sold</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">Revenue today</div>
                    <div class="vy-dk-val">{{ $kpis['payments'] }}</div>
                    {{-- The prototype keeps a note slot under this figure. "collected" is retained on purpose:
                         the figure is payments taken today, not revenue booked, and dropping the qualifier
                         would leave the label ambiguous (the honesty rule the sweep spent WAVE 2 on). --}}
                    <div class="vy-dk-sub">collected</div>
                </div>
                {{-- The prototype makes this tile a link through to Maintenance. --}}
                <a class="vy-dash-kpi" href="{{ route('app.maintenance') }}" style="text-decoration:none; color:inherit; display:block;">
                    <div class="vy-dk-label">Open work orders</div>
                    <div class="vy-dk-val">{{ $kpis['work_orders'] }}@if ($kpis['work_orders_urgent'] > 0)<span class="vy-dk-urgent">{{ $kpis['work_orders_urgent'] }} urgent</span>@endif</div>
                </a>
            </div>

            <div class="vy-dash-cols">
                <div class="vy-stack">
                    {{-- Today's tasks — REAL derived (urgent work orders · rooms to clean · arrivals) --}}
                    <div class="vy-card">
                        @php
                            // Presentation-only regrouping: the design groups these rows by PRIORITY, which
                            // each item already carries. No new data is read or computed here.
                            $vyPriOrder = ['urgent' => 'Urgent', 'high' => 'High', 'normal' => 'Normal'];
                            $vyTaskSecs = collect($tasks['items'])
                                ->groupBy('priority')
                                ->sortBy(fn ($rows, $key) => array_search($key, array_keys($vyPriOrder), true));
                        @endphp
                        <div class="vy-detail-head" style="margin-bottom: 4px;">
                            <h2 class="vy-card-title">Today’s tasks</h2>
                            @if ($vyTaskSecs->isEmpty())
                                <span class="vy-task-clear">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                    All caught up
                                </span>
                            @endif
                        </div>
                        {{-- D1-1 — YOUR OWN progress through the list. ⚠️ "Ticked", never "done": every row
                             here has a live count greater than zero (that is the only reason it renders), so
                             the product cannot claim the work is finished. Two genuinely different states:
                             an EMPTY list means the real counts reached zero — "All caught up", true; a
                             fully-ticked list means the work is still open and you have been through it. --}}
                        @if ($tasks['total_count'] > 0)
                            <div class="vy-task-prog">
                                <div class="vy-task-prog-bar" aria-hidden="true">
                                    <span style="width: {{ (int) round($tasks['ticked_count'] / max($tasks['total_count'], 1) * 100) }}%"></span>
                                </div>
                                <span class="vy-task-prog-label">
                                    @if ($tasks['ticked_count'] === $tasks['total_count'])
                                        You’ve ticked off all {{ $tasks['total_count'] }} — the work itself is still open below
                                    @else
                                        {{ $tasks['ticked_count'] }} of {{ $tasks['total_count'] }} ticked off
                                    @endif
                                </span>
                            </div>
                        @endif
                        {{-- ACTIONABLE — each task links to the page where it's resolved; it auto-clears from
                             REAL state when its count hits 0 (no fabricated "done" flag, #39). --}}
                        @forelse ($vyTaskSecs as $vyPri => $vyRows)
                            <div class="vy-task-sec">
                                <div class="vy-task-sec-head">
                                    <span class="vy-task-sec-dot vy-task-pri is-{{ $vyPri }}"></span>
                                    <span class="vy-task-sec-label vy-task-pri-ink is-{{ $vyPri }}">{{ $vyPriOrder[$vyPri] ?? $vyPri }}</span>
                                    <span class="vy-task-sec-count">{{ $vyRows->count() }}</span>
                                </div>
                                @foreach ($vyRows as $t)
                                    <div class="vy-task-line {{ $t['ticked'] ? 'is-ticked' : '' }}">
                                        {{-- The tick is a PERSONAL note. It never removes the row and never
                                             changes the count beside it — the count is real and stays. --}}
                                        <form method="POST" action="{{ $t['toggle_url'] }}" data-guard-submit style="margin:0;display:flex">
                                            @csrf
                                            <input type="hidden" name="task_key" value="{{ $t['key'] }}">
                                            <input type="hidden" name="task_date" value="{{ $day['date'] ?? '' }}">
                                            <input type="hidden" name="ticked" value="{{ $t['ticked'] ? '0' : '1' }}">
                                            <button type="submit" class="vy-task-tick" role="checkbox" aria-checked="{{ $t['ticked'] ? 'true' : 'false' }}"
                                                    aria-label="{{ $t['ticked'] ? 'Un-tick' : 'Tick off' }} “{{ $t['label'] }}” on your own list">
                                                @if ($t['ticked'])
                                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                                @endif
                                            </button>
                                        </form>
                                        <a class="vy-task-row is-actionable" href="{{ $t['link'] }}">
                                            <span class="vy-task-label">{{ $t['label'] }}</span>
                                            <span class="vy-task-meta">{{ $t['meta'] }}</span>
                                            <span class="vy-task-go" aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
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
                                <div class="vy-arr-who">
                                    {{-- The design leads each arrival with an initials avatar. Derived from the
                                         name already displayed — no new data, and name-only per #28. --}}
                                    <span class="vy-arr-av" aria-hidden="true">{{ \Illuminate\Support\Str::of($a['guest'])->explode(' ')->filter()->take(2)->map(fn ($p) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->implode('') }}</span>
                                    <div style="min-width:0;">
                                        <div class="vy-arr-name">{{ $a['guest'] }}</div>
                                        {{-- #104 GATE 3c — the wireframe's per-arrival detail: reference · N nights · unit (real). --}}
                                        <div class="vy-arr-ref">{{ $a['reference'] }} · {{ $a['nights'] }} {{ \Illuminate\Support\Str::plural('night', $a['nights']) }}@if ($a['unit']) · {{ $a['unit'] }}@else · Unassigned @endif</div>
                                        {{-- D1-3 — the guest's own estimated arrival, matching FRONT DESK's rendering
                                             character-for-character: the same "Arrival · " label, and the same @if that
                                             OMITS the line entirely when none was given.
                                             ⚠️ FREE TEXT, NOT A CLOCK — printed exactly as the guest typed it. Never
                                             parsed, never formatted as a time, and never defaulted to an hour: a guest
                                             who wrote "afternoon" has not told us 12:00, and inventing one would be a
                                             fabricated fact about a real person's plans.
                                             ⚠️ NULL OMITS THE LINE rather than printing a placeholder - a "—" sitting
                                             where a time belongs reads as a time that was given and then lost. --}}
                                        @if ($a['expected_arrival'])
                                            <div class="vy-arr-ref">Arrival · {{ $a['expected_arrival'] }}</div>
                                        @endif
                                    </div>
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
                            <span class="vy-week-chip">This week</span>
                        </div>
                        <div class="vy-occ7">
                            @foreach ($occupancyWeek as $d)
                                <div class="vy-occ7-col">
                                    <span class="vy-occ7-pct">{{ $d['pct'] }}%</span>
                                    <div class="vy-occ7-bar" style="height: {{ max(3, $d['pct']) }}%"></div>
                                    <span class="vy-occ7-day {{ $d['date'] === $day['today'] ? 'is-today' : '' }}">{{ $d['day'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Right rail --}}
                <div class="vy-stack">
                    {{-- TONIGHT — REAL occupancy + rooms sold + ADR --}}
                    <div class="vy-tonight-wrap">
                        <div class="vy-tonight-glow" aria-hidden="true"></div>
                        <div class="vy-tonight">
                            <span class="vy-eyebrow">Tonight</span>
                            <div class="vy-tonight-head">
                                <div class="vy-tonight-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span></div>
                                <div class="vy-tonight-sub">occupancy</div>
                            </div>
                            <div class="vy-tonight-row">
                                <div class="vy-tonight-cell"><div class="vy-tc-k">Rooms sold</div><div class="vy-tc-v">{{ $kpis['rooms_sold'] }}/{{ $kpis['rooms_total'] }}</div></div>
                                <div class="vy-tonight-cell"><div class="vy-tc-k">ADR</div><div class="vy-tc-v">{{ $kpis['adr'] ?? '—' }}</div></div>
                            </div>
                        </div>
                    </div>

                    {{-- Housekeeping — REAL status counts + progress --}}
                    <div class="vy-card">
                        @php
                            $hkTotal = $housekeeping['pending'] + $housekeeping['in_progress'] + $housekeeping['clean'] + $housekeeping['inspected'];
                            $hkDone = $housekeeping['clean'] + $housekeeping['inspected'];
                            $hkPct = $hkTotal > 0 ? (int) round($hkDone / $hkTotal * 100) : 0;
                        @endphp
                        <div class="vy-rail-head"><span class="vy-eyebrow">Housekeeping</span></div>
                        {{-- Per-status rail rows — the REAL housekeeping_tasks.status counts. The design lists
                             the statuses first and closes the card with the progress bar. --}}
                        <div class="vy-stat-list">
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#B4400F;"></span><span class="vy-stat-k">Dirty</span><span class="vy-stat-v">{{ $housekeeping['pending'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#E0921E;"></span><span class="vy-stat-k">In progress</span><span class="vy-stat-v">{{ $housekeeping['in_progress'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#33A0AC;"></span><span class="vy-stat-k">Clean</span><span class="vy-stat-v">{{ $housekeeping['clean'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#43A06B;"></span><span class="vy-stat-k">Inspected</span><span class="vy-stat-v">{{ $housekeeping['inspected'] }}</span></div>
                        </div>
                        <div class="vy-hk-progress-label" style="margin-top: 14px;"><span>{{ $housekeeping['to_clean'] }} to clean</span><span>{{ $hkDone }}/{{ $hkTotal }} done</span></div>
                        <div class="vy-hk-progress" style="margin-top: 5px;"><span style="width: {{ $hkPct }}%"></span></div>
                        <a class="vy-arr-link" href="{{ route('app.housekeeping') }}" style="display:inline-block; margin-top:12px;">Open board →</a>
                    </div>

                    {{-- Maintenance — REAL out-of-order units + on-hold nightly rate (honest omit if no rate) --}}
                    <div class="vy-card">
                        <div class="vy-rail-head">
                            <span class="vy-eyebrow">Maintenance</span>
                            <a class="vy-arr-link" href="{{ route('app.maintenance') }}">View all →</a>
                        </div>
                        {{-- Per-status rail cards — REAL active-ticket counts (open/urgent/awaiting-parts/out-of-order). --}}
                        <div class="vy-stat-list">
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#8A8079;"></span><span class="vy-stat-k">Open</span><span class="vy-stat-v">{{ $kpis['work_orders'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#E8703A;"></span><span class="vy-stat-k">Urgent</span><span class="vy-stat-v">{{ $kpis['work_orders_urgent'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#33A0AC;"></span><span class="vy-stat-k">Awaiting parts</span><span class="vy-stat-v">{{ $maintenance['awaiting_parts'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#B4400F;"></span><span class="vy-stat-k">Out of order</span><span class="vy-stat-v">{{ $maintenance['out_of_order'] }}</span></div>
                        </div>
                        {{-- The design closes this card with a highlighted callout through to Maintenance.
                             Both figures are REAL (out-of-order unit count + the nightly rate genuinely on
                             hold); the callout renders only when there is something on hold. --}}
                        @if ($maintenance['on_hold'])
                            <a class="vy-oo-cta" href="{{ route('app.maintenance') }}">
                                <span class="vy-oo-ico" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>
                                </span>
                                <span style="min-width:0; flex:1;">
                                    <span class="vy-oo-t">{{ $maintenance['out_of_order'] }} {{ \Illuminate\Support\Str::plural('unit', $maintenance['out_of_order']) }} out of order</span>
                                    <span class="vy-oo-s">{{ $maintenance['on_hold'] }} / night on hold</span>
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--vy-muted);"><path d="M9 6l6 6-6 6"/></svg>
                            </a>
                        @endif
                    </div>

                    {{-- Channels — HONEST status (real; "Not connected" when nothing is synced). F-OP-2: shown ONLY
                         when the org's plan grants channels (the page itself is Pro-gated); null → omitted. --}}
                    @if (! empty($channels))
                    <div class="vy-card">
                        <div class="vy-rail-head"><span class="vy-eyebrow">Channels</span></div>
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
                    @endif
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
