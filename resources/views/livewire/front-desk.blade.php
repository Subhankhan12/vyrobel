{{-- Operator Front Desk — arrivals / departures / in-house (GATE 2 exact-match rebuild).
     Styled on the shared operator design system via <x-operator.shell>. Lists + balances are REAL and
     tenant-scoped (FrontDeskService + folio balance). The board only READS; check-in/out post to the real
     staff action (FrontDeskActionController) and persist. The segmented Arrivals/Departures/In-house tabs are
     Alpine tab-state (presentation); the KPI band + counts are DERIVED from the real rows only. The wireframe's
     invented "Filters"/"Print" affordances are NOT rendered (they were never in the design). Room moves, on-desk
     Collect, the check-in/out modals and Walk-in are wired to their real routes in the following GATE-2 sub-gates.
     RAW-BLADE RULE: Alpine uses x-on:/x-model/x-bind (no `@click`). --}}
<div>
    <x-operator.shell active="front-desk" title="Front desk"
        :subtitle="($property['name'] ?? 'No property yet') . ' · ' . $day['label']" :viewer="$viewer">

        {{-- Flash notice from a real check-in/out (PRG) --}}
        @if (session('desk_notice'))
            <div class="vy-toast vy-fade" role="status">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                {{ session('desk_notice') }}
            </div>
        @endif

        {{-- Toolbar: REAL day navigation (GET links). Walk-in is wired to its real create-and-check-in flow in
             a later GATE-2 sub-gate; until then it is an honest disabled affordance (never a faked booking). --}}
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
                <span class="vy-pill is-soon" aria-disabled="true">Walk-in <span class="vy-soon-tag">Soon</span></span>
            </div>
        </div>

        @if ($property === null)
            <div class="vy-card vy-fade"><div class="vy-empty">No property yet — add a property to run the front desk.</div></div>
        @else
            <div x-data="{ tab: 'arrivals' }">
                {{-- DERIVED KPI band (real rows only): Arriving / Departing / In-house / Unassigned --}}
                <div class="vy-summary vy-fade">
                    <button type="button" class="vy-summary-chip vy-fd-kpi" x-bind:class="tab === 'arrivals' ? 'is-active' : ''" x-on:click="tab = 'arrivals'">
                        <span class="vy-sc-num">{{ $summary['arrivals'] }}</span><span class="vy-sc-label">Arriving</span>
                    </button>
                    <button type="button" class="vy-summary-chip vy-fd-kpi" x-bind:class="tab === 'departures' ? 'is-active' : ''" x-on:click="tab = 'departures'">
                        <span class="vy-sc-num">{{ $summary['departures'] }}</span><span class="vy-sc-label">Departing</span>
                    </button>
                    <button type="button" class="vy-summary-chip vy-fd-kpi" x-bind:class="tab === 'inhouse' ? 'is-active' : ''" x-on:click="tab = 'inhouse'">
                        <span class="vy-sc-num">{{ $summary['in_house'] }}</span><span class="vy-sc-label">In-house</span>
                    </button>
                    <div class="vy-summary-chip @if ($summary['unassigned'] > 0) is-due @endif">
                        <span class="vy-sc-num">{{ $summary['unassigned'] }}</span><span class="vy-sc-label">Unassigned</span>
                    </div>
                </div>

                {{-- Segmented control — Arrivals / Departures / In-house (sliding-pill tab state) --}}
                <div class="vy-fd-seg vy-fade" role="tablist">
                    <button type="button" class="vy-fd-seg-btn" role="tab" x-bind:class="tab === 'arrivals' ? 'is-on' : ''" x-on:click="tab = 'arrivals'">
                        Arrivals <span class="vy-fd-seg-n">{{ $summary['arrivals'] }}</span>
                    </button>
                    <button type="button" class="vy-fd-seg-btn" role="tab" x-bind:class="tab === 'departures' ? 'is-on' : ''" x-on:click="tab = 'departures'">
                        Departures <span class="vy-fd-seg-n">{{ $summary['departures'] }}</span>
                    </button>
                    <button type="button" class="vy-fd-seg-btn" role="tab" x-bind:class="tab === 'inhouse' ? 'is-on' : ''" x-on:click="tab = 'inhouse'">
                        In-house <span class="vy-fd-seg-n">{{ $summary['in_house'] }}</span>
                    </button>
                </div>

                {{-- The active tab's list. One at a time (x-show), each over the REAL rows. --}}
                @foreach (['arrivals' => ['Arrivals', $arrivals], 'departures' => ['Departures', $departures], 'inhouse' => ['In-house', $inHouse]] as $key => [$heading, $rows])
                    <div class="vy-card vy-card--flush vy-fade" role="tabpanel" x-show="tab === '{{ $key }}'" x-cloak>
                        <div class="vy-fd-head">
                            <span class="vy-fd-title">{{ $heading }}</span>
                            <span class="vy-fd-count">{{ count($rows) }}</span>
                        </div>

                        @forelse ($rows as $row)
                            <div class="vy-fd-row">
                                <div class="vy-fd-main">
                                    <div class="vy-fd-guest">
                                        {{ $row['guest'] }}
                                        {{-- #87 — VIP flags the front desk (real is_vip). An operational flag, not contact PII. --}}
                                        @if ($row['is_vip'])
                                            <span class="vy-tag tone-vip" style="margin-left: 6px;"><span class="vy-dot"></span>VIP</span>
                                        @endif
                                    </div>
                                    @if ($row['preferences'] !== [])
                                        <div class="vy-fd-meta" style="margin-top: 4px;">
                                            @foreach ($row['preferences'] as $pref)
                                                <span class="vy-tag tone-tag"><span class="vy-dot"></span>{{ $pref }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($row['expected_arrival'])
                                        <div class="vy-fd-meta" style="margin-top: 4px; font-size: 12px; color: var(--vy-muted);">Arrival · {{ $row['expected_arrival'] }}</div>
                                    @endif
                                    <div class="vy-fd-meta">
                                        <span class="vy-tag tone-{{ $row['tone'] }}"><span class="vy-dot"></span>{{ $row['status_label'] }}</span>
                                        @if ($row['unassigned'])
                                            <span class="vy-unassigned">Unassigned</span>
                                            <a class="vy-assign-link" href="{{ route('app.rack') }}?start={{ $row['check_in'] }}">Assign →</a>
                                        @elseif ($row['unit'])
                                            <span class="vy-unit-chip">{{ $row['unit'] }}</span>
                                        @endif
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['source'] }}</span>
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['nights'] }} {{ \Illuminate\Support\Str::plural('night', $row['nights']) }}</span>
                                        <span class="vy-sep">·</span>
                                        <span>{{ $row['guests'] }} {{ \Illuminate\Support\Str::plural('guest', $row['guests']) }}</span>
                                    </div>
                                </div>

                                <div class="vy-fd-right">
                                    @if ($row['has_balance'])
                                        <span class="vy-balance is-due" title="Outstanding folio balance">{{ $row['balance'] }} due</span>
                                    @else
                                        <span class="vy-balance is-settled">Settled</span>
                                    @endif

                                    <div class="vy-fd-actions">
                                        @if ($row['can_check_in'])
                                            <form method="POST" action="{{ route('app.front-desk.check-in', $row['id']) }}">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $day['date'] }}">
                                                <button type="submit" class="vy-act vy-act--accent">Check in</button>
                                            </form>
                                        @elseif ($row['can_check_out'])
                                            <form method="POST" action="{{ route('app.front-desk.check-out', $row['id']) }}">
                                                @csrf
                                                <input type="hidden" name="date" value="{{ $day['date'] }}">
                                                <button type="submit" class="vy-act vy-act--ghost">Check out</button>
                                            </form>
                                        @endif

                                        {{-- On-desk Collect is wired to the real folio payment route in a later GATE-2 sub-gate;
                                             until then it stays an honest disabled affordance — it never fakes a payment. --}}
                                        @if ($row['has_balance'])
                                            <span class="vy-act is-soon" aria-disabled="true" title="Payment collection is wired next">Collect <span class="vy-soon-tag">Soon</span></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="vy-empty">No {{ \Illuminate\Support\Str::lower($heading) }} on this day.</div>
                        @endforelse
                    </div>
                @endforeach
            </div>

            <div class="vy-deferred-note" style="margin-top: 18px;">
                <span class="vy-soon-tag">Wiring next</span>
                Room moves, on-desk payment collection and walk-ins are wired to their real routes in the next
                sub-gates; check-in and check-out are live. Balances are the real folio figures.
            </div>
        @endif

    </x-operator.shell>

    {{-- GATE-2 board chrome: the segmented control + KPI-as-tab affordance. Presentation only. --}}
    <style>
        .vy-fd-kpi { cursor: pointer; border: 1px solid transparent; background: none; font: inherit; text-align: left; transition: border-color 0.14s, background 0.14s, transform 0.1s; }
        .vy-fd-kpi:hover { transform: translateY(-1px); }
        .vy-fd-kpi.is-active { border-color: var(--vy-accent-border); background: rgba(232, 112, 58, 0.06); box-shadow: 0 0 0 1px var(--vy-accent-border) inset; }
        .vy-fd-seg { display: inline-flex; gap: 4px; padding: 4px; margin: 4px 0 16px; border-radius: 13px; background: rgba(255, 252, 248, 0.62); backdrop-filter: blur(20px) saturate(1.1); -webkit-backdrop-filter: blur(20px) saturate(1.1); border: 1px solid var(--vy-glass-border); }
        .vy-fd-seg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 16px; border: none; border-radius: 10px; background: transparent; color: var(--vy-muted); font-family: var(--vy-sans); font-size: 13.5px; font-weight: 600; cursor: pointer; transition: background 0.16s, color 0.16s, box-shadow 0.16s; }
        .vy-fd-seg-btn:hover { color: var(--vy-ink); }
        .vy-fd-seg-btn.is-on { background: var(--vy-grad); color: #fff; box-shadow: var(--vy-shadow-accent); }
        .vy-fd-seg-n { display: inline-grid; place-items: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px; background: rgba(60, 40, 24, 0.1); color: inherit; font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums; }
        .vy-fd-seg-btn.is-on .vy-fd-seg-n { background: rgba(255, 255, 255, 0.26); }
        [x-cloak] { display: none !important; }
    </style>
</div>
