{{-- Operator Dashboard — the front-desk command center (exact-match). Matched to "Operator Dashboard.html"
     on REAL tenant-scoped data via <x-operator.shell> (which also renders the shared read-only banner).
     Every figure is a certified read: occupancy/ADR from the Availability spine + PerformanceMetrics, counts
     from FrontDeskService/HousekeepingTask/MaintenanceTicket, revenue from the payments ledger. Channels are
     HONEST (real status — "Not connected" when nothing is genuinely synced, never a fake "Synced"). --}}
@php
    // #104 GATE 2 — the wireframe's personalized greeting header. Presentation-only: a time-of-day salutation
    // + the authenticated staff member's real first name (no data/route/behaviour change).
    $vyHour = (int) now()->format('G');
    $vyFirst = trim((string) \Illuminate\Support\Str::before((string) ($viewer['name'] ?? ''), ' ')) ?: __('livewire/operator/dashboard.greeting_no_name');
    // one key per greeting, so each language places the name where its grammar needs it
    $vyGreet = __($vyHour < 12 ? 'livewire/operator/dashboard.greeting_morning' : ($vyHour < 18 ? 'livewire/operator/dashboard.greeting_afternoon' : 'livewire/operator/dashboard.greeting_evening'), ['name' => $vyFirst]);
@endphp
<div class="vy-dash">
    <x-operator.shell active="dashboard" :title="$vyGreet"
        :subtitle="__('livewire/operator/dashboard.subtitle', ['property' => $property['name'] ?? __('livewire/operator/dashboard.no_property_yet'), 'date' => $day['label']])" :viewer="$viewer">

        {{-- 2026-09-29 — arriving from the Portfolio's "Open": say which property you are now working in --}}
        @if (session('property_notice'))
            <div class="vy-flash is-ok vy-fade" role="status">{{ session('property_notice') }}</div>
        @endif

        {{-- REAL day navigation (GET links) --}}
        <div class="vy-rack-toolbar vy-fade">
            <div class="vy-left">
                <a class="vy-icobtn" href="?date={{ $day['prev'] }}" aria-label="{{ __('livewire/operator/dashboard.previous_day') }}" title="{{ __('livewire/operator/dashboard.previous_day') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                </a>
                <span class="vy-range">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $day['label'] }}
                </span>
                <a class="vy-icobtn" href="?date={{ $day['next'] }}" aria-label="{{ __('livewire/operator/dashboard.next_day') }}" title="{{ __('livewire/operator/dashboard.next_day') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                @unless ($day['is_today'])
                    <a class="vy-pill" href="?date={{ $day['today'] }}">{{ __('common.today') }}</a>
                @endunless
            </div>
            {{-- GATE 0 — the inline search entry moved to the bespoke-chrome topbar (shared shell); the
                 redundant in-content search pill was removed here to avoid a duplicate. --}}
        </div>

        @if ($property === null)
            <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/operator/dashboard.no_property_empty') }}</div></div>
        @else
            {{-- KPI TILES (design's 5): Arrivals · Departures · Occupancy tonight (+Δ) · Revenue today · Open work orders --}}
            <div class="vy-dash-kpis vy-dash-kpis--5 vy-fade">
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/dashboard.kpi_arrivals_today') }}</div>
                    <div class="vy-dk-val">{{ $kpis['arrivals'] }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/dashboard.kpi_departures_today') }}</div>
                    <div class="vy-dk-val">{{ $kpis['departures'] }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/dashboard.kpi_occupancy_tonight') }}</div>
                    <div class="vy-dk-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span>@if (! is_null($kpis['occupancy_delta']))<span class="vy-dk-delta {{ $kpis['occupancy_delta'] < 0 ? 'is-down' : '' }}">{{ $kpis['occupancy_delta'] >= 0 ? '+' : '' }}{{ $kpis['occupancy_delta'] }}%</span>@endif</div>
                    {{-- Kept in the design's note slot: this numerator/denominator is the TRUE org-scoped
                         room count, and OperatorDashboardViewTest probes it to prove another org's rooms
                         never enter this occupancy (#38). Dropping it would delete a real figure AND
                         disarm an isolation assertion. --}}
                    <div class="vy-dk-sub">{{ trans_choice('livewire/operator/dashboard.rooms_sold_of', $kpis['rooms_total'], ['sold' => $kpis['rooms_sold'], 'count' => $kpis['rooms_total']]) }}</div>
                </div>
                <div class="vy-dash-kpi">
                    <div class="vy-dk-label">{{ __('livewire/operator/dashboard.kpi_revenue_today') }}</div>
                    <div class="vy-dk-val">{{ $kpis['payments'] }}</div>
                    {{-- The prototype keeps a note slot under this figure. "collected" is retained on purpose:
                         the figure is payments taken today, not revenue booked, and dropping the qualifier
                         would leave the label ambiguous (the honesty rule the sweep spent WAVE 2 on). --}}
                    <div class="vy-dk-sub">{{ __('livewire/operator/dashboard.revenue_collected') }}</div>
                </div>
                {{-- The prototype makes this tile a link through to Maintenance. --}}
                <a class="vy-dash-kpi" href="{{ route('app.maintenance') }}" style="text-decoration:none; color:inherit; display:block;">
                    <div class="vy-dk-label">{{ __('livewire/operator/dashboard.kpi_open_work_orders') }}</div>
                    <div class="vy-dk-val">{{ $kpis['work_orders'] }}@if ($kpis['work_orders_urgent'] > 0)<span class="vy-dk-urgent">{{ trans_choice('livewire/operator/dashboard.urgent_count', $kpis['work_orders_urgent'], ['count' => $kpis['work_orders_urgent']]) }}</span>@endif</div>
                </a>
            </div>

            <div class="vy-dash-cols">
                <div class="vy-stack">
                    {{-- Today's tasks — REAL derived (urgent work orders · rooms to clean · arrivals) --}}
                    <div class="vy-card">
                        @php
                            // Presentation-only regrouping: the design groups these rows by PRIORITY, which
                            // each item already carries. No new data is read or computed here.
                            // D1-2 — the same rows, grouped by whichever dimension this person chose. Nothing
                            // is filtered or dropped: the SET of rows is identical either way, only the
                            // headings differ.
                            $vyPriOrder = ['urgent' => __('livewire/operator/dashboard.priority_urgent'), 'high' => __('livewire/operator/dashboard.priority_high'), 'normal' => __('livewire/operator/dashboard.priority_normal')];
                            $vyByCategory = ($tasks['grouping'] ?? 'priority') === 'category';
                            $vyTaskSecs = $vyByCategory
                                ? collect($tasks['items'])->groupBy('category')->sortBy(fn ($rows, $key) => \App\Models\OperatorTaskState::categorySortIndex((string) $key))
                                : collect($tasks['items'])
                                    ->groupBy('priority')
                                    ->sortBy(fn ($rows, $key) => array_search($key, array_keys($vyPriOrder), true));
                        @endphp
                        <div class="vy-detail-head" style="margin-bottom: 4px;">
                            <h2 class="vy-card-title">{{ __('livewire/operator/dashboard.tasks_title') }}</h2>
                            @if ($vyTaskSecs->isEmpty())
                                <span class="vy-task-clear">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                    {{ __('livewire/operator/dashboard.all_caught_up') }}
                                </span>
                            @endif
                        </div>
                        {{-- D1-2 — the design's two-button segmented control. A PERSONAL arrangement choice:
                             it re-groups the same rows and cannot hide, filter or re-rank any of them. --}}
                        @if ($tasks['total_count'] > 0)
                            <form method="POST" action="{{ $tasks['grouping_url'] }}" class="vy-task-group-sw" data-guard-submit>
                                @csrf
                                <span class="vy-task-group-lbl">{{ __('livewire/operator/dashboard.group_by') }}</span>
                                <button type="submit" name="task_grouping" value="priority"
                                        class="vy-task-group-opt {{ $vyByCategory ? '' : 'is-on' }}"
                                        aria-pressed="{{ $vyByCategory ? 'false' : 'true' }}">{{ __('livewire/operator/dashboard.group_by_priority') }}</button>
                                <button type="submit" name="task_grouping" value="category"
                                        class="vy-task-group-opt {{ $vyByCategory ? 'is-on' : '' }}"
                                        aria-pressed="{{ $vyByCategory ? 'true' : 'false' }}">{{ __('livewire/operator/dashboard.group_by_category') }}</button>
                            </form>
                        @endif
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
                                        {{ trans_choice('livewire/operator/dashboard.ticked_all', $tasks['total_count'], ['count' => $tasks['total_count']]) }}
                                    @else
                                        {{ trans_choice('livewire/operator/dashboard.ticked_of', $tasks['total_count'], ['ticked' => $tasks['ticked_count'], 'count' => $tasks['total_count']]) }}
                                    @endif
                                </span>
                            </div>
                        @endif
                        {{-- ACTIONABLE — each task links to the page where it's resolved; it auto-clears from
                             REAL state when its count hits 0 (no fabricated "done" flag, #39). --}}
                        @forelse ($vyTaskSecs as $vyPri => $vyRows)
                            @php
                                // ⚠️ D1-2's HONESTY INVARIANT. Grouped by CATEGORY, a section heading no longer
                                // says how urgent its contents are — so the urgency has to travel WITH the
                                // rows, or re-arranging the list would quietly bury which work is urgent. The
                                // section carries the real urgent count, and each row keeps its own priority
                                // mark below. Read from the rows themselves, never from a preference.
                                $vySecUrgent = $vyByCategory ? $vyRows->where('priority', 'urgent')->count() : 0;
                                $vySecPri = $vyByCategory ? 'normal' : $vyPri;
                            @endphp
                            <div class="vy-task-sec">
                                <div class="vy-task-sec-head">
                                    <span class="vy-task-sec-dot vy-task-pri is-{{ $vySecPri }}"></span>
                                    <span class="vy-task-sec-label vy-task-pri-ink is-{{ $vySecPri }}">{{ $vyByCategory ? $vyRows->first()['category_label'] : ($vyPriOrder[$vyPri] ?? $vyPri) }}</span>
                                    <span class="vy-task-sec-count">{{ $vyRows->count() }}</span>
                                    @if ($vySecUrgent > 0)
                                        <span class="vy-task-sec-urgent">{{ trans_choice('livewire/operator/dashboard.urgent_count', $vySecUrgent, ['count' => $vySecUrgent]) }}</span>
                                    @endif
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
                                                    aria-label="{{ __($t['ticked'] ? 'livewire/operator/dashboard.untick_aria' : 'livewire/operator/dashboard.tick_aria', ['task' => $t['label']]) }}">
                                                @if ($t['ticked'])
                                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 12.5 9.5 17.5 19.5 7"/></svg>
                                                @endif
                                            </button>
                                        </form>
                                        <a class="vy-task-row is-actionable" href="{{ $t['link'] }}">
                                            {{-- ⚠️ Under CATEGORY grouping the priority is no longer in the
                                                 heading, so it rides the row — the urgent signal survives the
                                                 re-arrangement instead of being lost with the old heading. --}}
                                            @if ($vyByCategory)
                                                <span class="vy-task-pri vy-task-row-dot is-{{ $t['priority'] }}"
                                                      title="{{ $vyPriOrder[$t['priority']] ?? $t['priority'] }}"></span>
                                                <span class="vy-sr-only">{{ __('livewire/operator/dashboard.priority_sr', ['priority' => $vyPriOrder[$t['priority']] ?? $t['priority']]) }}</span>
                                            @endif
                                            <span class="vy-task-label">{{ $t['label'] }}</span>
                                            <span class="vy-task-meta">{{ $t['meta'] }}</span>
                                            <span class="vy-task-go vy-flip-rtl" aria-hidden="true">→</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="vy-empty" style="padding: 20px 12px;">{{ __('livewire/operator/dashboard.all_caught_up_empty') }}</div>
                        @endforelse
                    </div>

                    {{-- Today's arrivals — REAL (operator sees full guest data; names shown, contact not) --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 6px;">
                            <h2 class="vy-card-title">{{ __('livewire/operator/dashboard.arrivals_title') }}</h2>
                            <a class="vy-arr-link" href="{{ route('app.front-desk') }}?date={{ $day['date'] }}">{{ __('livewire/operator/dashboard.view_all') }}</a>
                        </div>
                        @forelse ($arrivals as $a)
                            <div class="vy-arr-row">
                                <div class="vy-arr-who">
                                    {{-- The design leads each arrival with an initials avatar. Derived from the
                                         name already displayed — no new data, and name-only per #28. --}}
                                    <span class="vy-arr-av" aria-hidden="true">{{ \Illuminate\Support\Str::of($a['guest'])->explode(' ')->filter()->take(2)->map(fn ($p) => \App\Support\I18n\LocalText::upper(\Illuminate\Support\Str::substr($p, 0, 1)))->implode('') }}</span>
                                    <div style="min-width:0;">
                                        <div class="vy-arr-name">{{ $a['guest'] }}</div>
                                        {{-- #104 GATE 3c — the wireframe's per-arrival detail: reference · N nights · unit (real). --}}
                                        <div class="vy-arr-ref">{{ trans_choice('livewire/operator/dashboard.arrival_detail', $a['nights'], ['reference' => $a['reference'], 'count' => $a['nights'], 'unit' => $a['unit'] ?: __('livewire/operator/dashboard.unassigned')]) }}</div>
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
                                            <div class="vy-arr-ref">{{ __('livewire/operator/dashboard.expected_arrival', ['time' => $a['expected_arrival']]) }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="vy-tag tone-{{ $a['tone'] }}"><span class="vy-dot"></span>{{ $a['status_label'] }}</span>
                                    <a class="vy-arr-link" href="{{ route('app.reservations.show', $a['id']) }}">{{ __('livewire/operator/dashboard.open_link') }}</a>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">{{ __('livewire/operator/dashboard.no_arrivals') }}</div>
                        @endforelse
                    </div>

                    {{-- Occupancy · next 7 days — REAL per-night % (certified spine) --}}
                    <div class="vy-card">
                        <div class="vy-detail-head" style="margin-bottom: 10px;">
                            <h2 class="vy-card-title">{{ __('livewire/operator/dashboard.occupancy_week_title') }}</h2>
                            <span class="vy-week-chip">{{ __('livewire/operator/dashboard.this_week') }}</span>
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
                            <span class="vy-eyebrow">{{ __('livewire/operator/dashboard.tonight') }}</span>
                            <div class="vy-tonight-head">
                                <div class="vy-tonight-val">{{ $kpis['occupancy_pct'] }}<span class="vy-dk-unit">%</span></div>
                                <div class="vy-tonight-sub">{{ __('livewire/operator/dashboard.occupancy') }}</div>
                            </div>
                            <div class="vy-tonight-row">
                                <div class="vy-tonight-cell"><div class="vy-tc-k">{{ __('livewire/operator/dashboard.rooms_sold') }}</div><div class="vy-tc-v">{{ $kpis['rooms_sold'] }}/{{ $kpis['rooms_total'] }}</div></div>
                                <div class="vy-tonight-cell"><div class="vy-tc-k">{{ __('livewire/operator/dashboard.adr') }}</div><div class="vy-tc-v">{{ $kpis['adr'] ?? '—' }}</div></div>
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
                        <div class="vy-rail-head"><span class="vy-eyebrow">{{ __('livewire/operator/dashboard.housekeeping') }}</span></div>
                        {{-- Per-status rail rows — the REAL housekeeping_tasks.status counts. The design lists
                             the statuses first and closes the card with the progress bar. --}}
                        <div class="vy-stat-list">
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#B4400F;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.hk_dirty') }}</span><span class="vy-stat-v">{{ $housekeeping['pending'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#E0921E;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.hk_in_progress') }}</span><span class="vy-stat-v">{{ $housekeeping['in_progress'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#33A0AC;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.hk_clean') }}</span><span class="vy-stat-v">{{ $housekeeping['clean'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#43A06B;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.hk_inspected') }}</span><span class="vy-stat-v">{{ $housekeeping['inspected'] }}</span></div>
                        </div>
                        <div class="vy-hk-progress-label" style="margin-top: 14px;"><span>{{ trans_choice('livewire/operator/dashboard.to_clean', $housekeeping['to_clean'], ['count' => $housekeeping['to_clean']]) }}</span><span>{{ trans_choice('livewire/operator/dashboard.done_of', $hkTotal, ['done' => $hkDone, 'count' => $hkTotal]) }}</span></div>
                        <div class="vy-hk-progress" style="margin-top: 5px;"><span style="width: {{ $hkPct }}%"></span></div>
                        <a class="vy-arr-link" href="{{ route('app.housekeeping') }}" style="display:inline-block; margin-top:12px;">{{ __('livewire/operator/dashboard.open_board') }}</a>
                    </div>

                    {{-- Maintenance — REAL out-of-order units + on-hold nightly rate (honest omit if no rate) --}}
                    <div class="vy-card">
                        <div class="vy-rail-head">
                            <span class="vy-eyebrow">{{ __('livewire/operator/dashboard.maintenance') }}</span>
                            <a class="vy-arr-link" href="{{ route('app.maintenance') }}">{{ __('livewire/operator/dashboard.view_all') }}</a>
                        </div>
                        {{-- Per-status rail cards — REAL active-ticket counts (open/urgent/awaiting-parts/out-of-order). --}}
                        <div class="vy-stat-list">
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#8A8079;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.mt_open') }}</span><span class="vy-stat-v">{{ $kpis['work_orders'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#E8703A;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.mt_urgent') }}</span><span class="vy-stat-v">{{ $kpis['work_orders_urgent'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#33A0AC;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.mt_awaiting_parts') }}</span><span class="vy-stat-v">{{ $maintenance['awaiting_parts'] }}</span></div>
                            <div class="vy-stat"><span class="vy-stat-dot" style="background:#B4400F;"></span><span class="vy-stat-k">{{ __('livewire/operator/dashboard.mt_out_of_order') }}</span><span class="vy-stat-v">{{ $maintenance['out_of_order'] }}</span></div>
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
                                    <span class="vy-oo-t">{{ trans_choice('livewire/operator/dashboard.units_out_of_order', $maintenance['out_of_order'], ['count' => $maintenance['out_of_order']]) }}</span>
                                    <span class="vy-oo-s">{{ __('livewire/operator/dashboard.on_hold_per_night', ['amount' => $maintenance['on_hold']]) }}</span>
                                </span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color: var(--vy-muted);"><path d="M9 6l6 6-6 6"/></svg>
                            </a>
                        @endif
                    </div>

                    {{-- Channels — HONEST status (real; "Not connected" when nothing is synced). F-OP-2: shown ONLY
                         when the org's plan grants channels (the page itself is Pro-gated); null → omitted. --}}
                    @if (! empty($channels))
                    <div class="vy-card">
                        <div class="vy-rail-head"><span class="vy-eyebrow">{{ __('livewire/operator/dashboard.channels') }}</span></div>
                        @foreach ($channels as $c)
                            <div class="vy-mini-row">
                                <span class="vy-mini-k">{{ $c['name'] }}</span>
                                @if ($c['connected'])
                                    <span class="vy-chan-status is-synced"><span class="vy-dot"></span>{{ $c['last_sync'] ? __('livewire/operator/dashboard.synced_ago', ['when' => $c['last_sync']]) : __('livewire/operator/dashboard.synced') }}</span>
                                @else
                                    <span class="vy-chan-status is-off"><span class="vy-dot"></span>{{ __('livewire/operator/dashboard.not_connected') }}</span>
                                @endif
                            </div>
                        @endforeach
                        <div class="vy-line-sub" style="margin-top:8px;font-size:11.5px;color:var(--vy-muted)">{{ __('livewire/operator/dashboard.channel_sync_note') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        @endif

    </x-operator.shell>
</div>
