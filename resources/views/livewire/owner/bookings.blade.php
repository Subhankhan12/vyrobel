{{-- Owner Bookings (GATE 1 · exact-match) — the owner's reservations across their OWN units, owner-scoped and
     read-only. Guest NAME only (no contact — invariant #28); real source enum (OTA, never a brand); real status
     enum; "Net to you" via the ONE shared OwnerLedgerAttribution. The "Bookings over time" chart + "Monthly goal"
     ring are REAL (status counts + real net + the owner's OWN target — #39, never fabricated). Filters/sort/
     pagination ride GET so the owner scope middleware re-runs (a wire-action would bypass it — invariant #20);
     the two writes (Export, set Target) are CONTROLLER routes. RAW-BLADE RULE: every `@` follows space/newline/tag. --}}
@php
    use Illuminate\Support\Str;
    use App\Support\Money\MoneyFormatter;
    use App\Support\Owner\OwnerTeamRole;

    // $preserved is built by the component (OwnerBookings::preservedQuery) — it carries the active filters +
    // ONLY an explicitly-chosen sort/dir (never a tab-default), so switching tabs re-applies each tab's natural
    // order. `page` is never preserved, so any filter/sort/tab change resets to page 1.
    $qs = function (array $over) use ($preserved) {
        $merged = array_filter(array_merge($preserved, $over), fn ($v) => $v !== null && $v !== '');

        return route('owner.bookings').($merged === [] ? '' : '?'.http_build_query($merged));
    };

    // A sortable header: toggles asc/desc on the active column, else asc.
    $sortHead = function (string $col) use ($filters, $qs) {
        $active = $filters['sort'] === $col;
        $next = ($active && $filters['dir'] === 'asc') ? 'desc' : 'asc';

        return ['url' => $qs(['sort' => $col, 'dir' => $next]), 'active' => $active, 'dir' => $filters['dir']];
    };

    $anyFilter = ($filters['source'] ?? null) !== null || ($filters['status'] ?? null) !== null
        || ($filters['unit'] ?? null) !== null || ($filters['q'] ?? null) !== null
        || ($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null || ($filters['range'] ?? null) !== null;

    // Date-range control label.
    $dateLabel = 'Any dates';
    if (($filters['range'] ?? null) !== null) {
        $dateLabel = $ranges[$filters['range']] ?? 'Any dates';
    } elseif (($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null) {
        $dateLabel = ($filters['from'] ?? '…').' → '.($filters['to'] ?? '…');
    }

    // Chart geometry — points for the net-revenue line, in a 0–100 viewBox.
    $cMonths = $chart['months'];
    $cN = count($cMonths);
    $pts = [];
    foreach ($cMonths as $i => $m) {
        $pts[] = [
            'x' => $cN > 0 ? round(($i + 0.5) / $cN * 100, 2) : 50,
            'y' => round(100 - (float) $m['net_pct'], 2),
        ];
    }
    $polyline = implode(' ', array_map(fn ($p) => $p['x'].','.$p['y'], $pts));
    $goalY = $chart['goal_pct'] !== null ? round(100 - (float) $chart['goal_pct'], 2) : null;
    $goalDisplay = $chart['goal_minor'] !== null ? MoneyFormatter::compact($chart['goal_minor'], $chart['currency']) : null;
@endphp

<x-owner.shell active="bookings" title="Bookings"
    :breadcrumb="[['label' => 'Overview', 'url' => route('owner.dashboard')], ['label' => 'Bookings', 'url' => null]]"
    :subtitle="$reservationsCount.' '.Str::plural('reservation', $reservationsCount).' · '.$unitsCount.' '.Str::plural('unit', $unitsCount)"
    :viewer="$viewer">

    {{-- PAGE-SCOPED styles — kept here (not in the shared owner layout) so these class names/comments never
         appear in OTHER owner pages' <style>, where an isolation test asserts a non-linked Balance shows no
         "Monthly" and a Dashboard shows no fabricated "goal". The shared glass surfaces are reused as-is. --}}
    <style>
        .vy-crumbs { display: flex; align-items: center; gap: 7px; margin-bottom: 6px; font-size: 12.5px; color: var(--vy-muted); }
        .vy-crumbs .vy-crumb { color: var(--vy-on-grad-soft); transition: color .14s ease; }
        .vy-crumbs a.vy-crumb:hover { color: var(--vy-accent-text); }
        .vy-crumbs .vy-crumb.is-current { color: var(--vy-ink); font-weight: 500; }
        .vy-crumb-sep { color: var(--vy-on-grad-soft); }

        .vy-bk-cols { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 18px; align-items: stretch; margin-bottom: 18px; }
        @media (max-width: 1000px) { .vy-bk-cols { grid-template-columns: minmax(0, 1fr); } }

        .vy-bk-legend { display: flex; flex-wrap: wrap; gap: 16px; margin: 12px 0 6px; font-size: 12px; color: var(--vy-muted); }
        .vy-bk-leg { display: inline-flex; align-items: center; gap: 7px; }
        .vy-bk-leg b { color: var(--vy-ink); font-weight: 600; }
        .vy-bk-key { width: 10px; height: 10px; border-radius: 3px; flex: none; }
        .vy-bk-key--confirmed { background: var(--vy-accent); }
        .vy-bk-key--pending { background: var(--vy-amber-2); }
        .vy-bk-key--other { background: #c9b6a8; }
        .vy-bk-key--net { width: 16px; height: 0; border: none; border-top: 2px dotted var(--vy-accent); border-radius: 0; }

        .vy-bk-plot { position: relative; height: 190px; margin-top: 6px; }
        .vy-bk-bars { position: absolute; inset: 0; display: flex; align-items: flex-end; gap: 10px; }
        .vy-bk-col { position: relative; flex: 1; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; min-width: 0; }
        .vy-bk-netlabel { font-size: 10.5px; font-weight: 600; color: var(--vy-muted); margin-bottom: 6px; white-space: nowrap; }
        .vy-bk-col.is-current .vy-bk-netlabel { color: var(--vy-accent-tint-text); }
        .vy-bk-stack { width: 100%; max-width: 46px; display: flex; flex-direction: column-reverse; border-radius: 8px 8px 4px 4px; overflow: hidden; }
        .vy-bk-seg--confirmed { background: var(--vy-grad); }
        .vy-bk-seg--pending { background: linear-gradient(180deg, #E0921E, #B26A08); }
        .vy-bk-seg--other { background: #c9b6a8; }
        .vy-bk-svg { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; overflow: visible; }
        .vy-bk-goal-label { position: absolute; right: 0; transform: translateY(-50%); font-size: 10px; font-weight: 600; color: var(--vy-accent-text); background: rgba(255,252,248,.82); padding: 1px 5px; border-radius: 5px; white-space: nowrap; }
        .vy-bk-xlabels { display: flex; gap: 10px; margin-top: 8px; }
        .vy-bk-xlabel { flex: 1; text-align: center; font-size: 10.5px; color: var(--vy-muted); min-width: 0; }
        .vy-bk-xlabel.is-current { color: var(--vy-accent-text); font-weight: 600; }

        .vy-goal-card { display: flex; flex-direction: column; align-items: center; text-align: center; }
        .vy-goal-ring { --p: 0; position: relative; width: 168px; height: 168px; border-radius: 50%; display: grid; place-items: center; margin: 6px 0 4px; background: conic-gradient(var(--vy-accent) calc(var(--p) * 1%), rgba(194,65,12,.12) 0); }
        .vy-goal-ring::after { content: ''; position: absolute; inset: 15px; border-radius: 50%; background: #FCF6F0; box-shadow: inset 0 1px 3px rgba(120,70,40,.08); }
        .vy-goal-ring.is-empty { background: repeating-conic-gradient(rgba(194,65,12,.14) 0 6deg, transparent 6deg 12deg); }
        .vy-goal-ring-inner { position: relative; z-index: 1; }
        .vy-goal-ring-val { font-family: var(--vy-display); font-weight: 700; font-size: 34px; letter-spacing: -0.02em; color: var(--vy-accent-text); line-height: 1; }
        .vy-goal-ring-cap { font-size: 9.5px; font-weight: 600; letter-spacing: .11em; text-transform: uppercase; color: var(--vy-muted); margin-top: 4px; }
        .vy-goal-net-lg { font-family: var(--vy-display); font-weight: 600; font-size: 14px; margin-top: 10px; }
        .vy-goal-of { font-size: 12px; color: var(--vy-muted); margin-top: 3px; }
        .vy-goal-delta { display: inline-flex; align-items: center; gap: 4px; margin-top: 9px; padding: 4px 11px; border-radius: var(--vy-r-pill); font-size: 12px; font-weight: 600; }
        .vy-goal-delta--over { background: rgba(31,122,90,.12); color: var(--vy-green-text); }
        .vy-goal-delta--under { background: rgba(224,146,30,.14); color: var(--vy-amber-text); }
        .vy-goal-sep { width: 100%; height: 1px; background: var(--vy-hairline); margin: 16px 0 12px; }
        .vy-goal-target { width: 100%; }
        .vy-goal-target-view { display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; padding: 9px 14px; border-radius: var(--vy-r-pill); background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); font-size: 13px; }
        .vy-goal-target-view b { font-family: var(--vy-display); font-weight: 600; }
        .vy-goal-target-edit { display: flex; align-items: center; gap: 6px; width: 100%; }
        .vy-goal-target-edit .vy-input { padding: 8px 11px; }
        .vy-goal-icobtn { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 8px; background: transparent; border: none; color: var(--vy-muted); cursor: pointer; flex: none; }
        .vy-goal-icobtn:hover { color: var(--vy-accent-text); }

        .vy-status--confirmed { background: rgba(194, 65, 12, 0.12); color: var(--vy-accent-tint-text); }
        .vy-status--confirmed .vy-dot { background: var(--vy-accent-tint-text); }

        .vy-bk-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .vy-bk-select { width: auto; flex: none; padding: 8px 30px 8px 12px; cursor: pointer; }
        .vy-bk-dates { position: relative; }
        .vy-bk-dates-panel { position: absolute; z-index: 40; top: calc(100% + 8px); left: 0; min-width: 230px; padding: 10px; background: rgba(255,252,248,.98); border: 1px solid var(--vy-glass-border); border-radius: var(--vy-r-md); box-shadow: 0 24px 60px rgba(60,30,10,.2); }
        .vy-bk-preset { display: block; width: 100%; text-align: left; padding: 8px 10px; border-radius: var(--vy-r-sm); font-size: 13px; color: var(--vy-ink); }
        .vy-bk-preset:hover { background: rgba(120,70,40,.06); }
        .vy-bk-preset.is-active { background: rgba(194,65,12,.1); color: var(--vy-accent-text); font-weight: 600; }
        .vy-bk-dates-custom { display: flex; flex-direction: column; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--vy-hairline); }
        .vy-bk-dates-custom label { font-size: 10.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--vy-muted); }
        .vy-bk-clear { display: block; margin-top: 8px; font-size: 12px; color: var(--vy-accent-text); }

        .vy-th-sort { display: inline-flex; align-items: center; gap: 4px; color: var(--vy-faint); }
        a.vy-th-sort:hover { color: var(--vy-accent-text); }
        .vy-th-sort.is-active { color: var(--vy-accent-text); }
        .vy-th-caret { font-size: 9px; }
        .vy-row-clickable { cursor: pointer; }

        .vy-bk-foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 14px; }
        .vy-pager { display: inline-flex; align-items: center; gap: 8px; }
        .vy-pager-btn { display: inline-grid; place-items: center; min-width: 34px; height: 34px; padding: 0 10px; border-radius: var(--vy-r-sm); background: rgba(255,255,255,.6); border: 1px solid var(--vy-glass-border); box-shadow: var(--vy-shadow-sm); color: var(--vy-ink); font-size: 12.5px; }
        .vy-pager-btn:hover { background: rgba(255,255,255,.85); }
        .vy-pager-btn[aria-disabled="true"] { opacity: .4; pointer-events: none; }
    </style>

    @if (session('status'))
        <div class="vy-card vy-fade" style="margin-bottom:14px;border-color:var(--vy-accent-border);padding:14px 18px;font-size:13px;color:var(--vy-ink)">
            {{ session('status') }}
        </div>
    @endif

    {{-- ── Stat cards ─────────────────────────────────────────────────────────────────────────── --}}
    <div class="vy-kpi-grid vy-kpi-grid--4 vy-fade" style="margin-bottom:18px">
        <div class="vy-kpi">
            <div class="vy-kpi-label">Upcoming</div>
            <div class="vy-kpi-val">{{ $summary['upcoming'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Guests</div>
            <div class="vy-kpi-val">{{ $summary['guests'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Nights</div>
            <div class="vy-kpi-val">{{ $summary['nights'] }}</div>
        </div>
        <div class="vy-kpi">
            <div class="vy-kpi-label">Net to you</div>
            @forelse ($summary['net'] as $net)
                <div class="vy-kpi-val vy-mono">{{ $net['display'] }}</div>
            @empty
                <div class="vy-kpi-val" style="color:var(--vy-faint)">—</div>
            @endforelse
            @if (count($summary['net']) > 1)
                <div class="vy-kpi-sub">per payout currency</div>
            @endif
        </div>
    </div>

    {{-- ── Chart + Monthly goal ───────────────────────────────────────────────────────────────── --}}
    <div class="vy-bk-cols vy-fade" style="animation-delay:.03s">
        {{-- Bookings over time --}}
        <section class="vy-card">
            <div class="vy-card-title">Bookings over time</div>
            <div class="vy-line-sub" style="margin-top:2px">Reservations by check-in month, with net revenue</div>

            <div class="vy-bk-legend">
                <span class="vy-bk-leg"><span class="vy-bk-key vy-bk-key--confirmed"></span>Confirmed <b>{{ $chart['legend']['confirmed'] }}</b></span>
                <span class="vy-bk-leg"><span class="vy-bk-key vy-bk-key--pending"></span>Pending <b>{{ $chart['legend']['pending'] }}</b></span>
                @if ($chart['legend']['other'] > 0)
                    <span class="vy-bk-leg"><span class="vy-bk-key vy-bk-key--other"></span>Other <b>{{ $chart['legend']['other'] }}</b></span>
                @endif
                <span class="vy-bk-leg"><span class="vy-bk-key vy-bk-key--net"></span>Net revenue</span>
            </div>

            @if ($cN === 0)
                <div class="vy-empty">No bookings to chart yet.</div>
            @else
                <div class="vy-bk-plot">
                    <div class="vy-bk-bars">
                        @foreach ($cMonths as $m)
                            <div @class(['vy-bk-col', 'is-current' => $m['is_current']])>
                                @if ($m['net_minor'] !== 0)
                                    <div class="vy-bk-netlabel">{{ $m['net_display'] }}</div>
                                @endif
                                <div class="vy-bk-stack" style="height:{{ (int) $m['count_pct'] }}%{{ (int) $m['count_pct'] > 0 ? ';min-height:3px' : '' }}">
                                    @if ($m['confirmed'] > 0)
                                        <div class="vy-bk-seg--confirmed" style="flex:{{ $m['confirmed'] }} 0 0"></div>
                                    @endif
                                    @if ($m['pending'] > 0)
                                        <div class="vy-bk-seg--pending" style="flex:{{ $m['pending'] }} 0 0"></div>
                                    @endif
                                    @if ($m['other'] > 0)
                                        <div class="vy-bk-seg--other" style="flex:{{ $m['other'] }} 0 0"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <svg class="vy-bk-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                        @if ($goalY !== null)
                            <line x1="0" y1="{{ $goalY }}" x2="100" y2="{{ $goalY }}" stroke="var(--vy-accent)" stroke-width="1" stroke-dasharray="2 2" opacity="0.5" vector-effect="non-scaling-stroke"></line>
                        @endif
                        @if ($cN > 1)
                            <polyline points="{{ $polyline }}" fill="none" stroke="var(--vy-accent)" stroke-width="2" stroke-dasharray="1 2.5" stroke-linecap="round" vector-effect="non-scaling-stroke"></polyline>
                        @endif
                        @foreach ($pts as $p)
                            <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="2.4" fill="#fff" stroke="var(--vy-accent)" stroke-width="1.6" vector-effect="non-scaling-stroke"></circle>
                        @endforeach
                    </svg>

                    @if ($goalY !== null && $goalDisplay !== null)
                        <span class="vy-bk-goal-label" style="top:{{ $goalY }}%">{{ $goalDisplay }} /mo goal</span>
                    @endif
                </div>

                <div class="vy-bk-xlabels">
                    @foreach ($cMonths as $m)
                        <span @class(['vy-bk-xlabel', 'is-current' => $m['is_current']])>{{ $m['label'] }}</span>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Monthly goal --}}
        <section class="vy-card vy-goal-card">
            <div class="vy-card-title" style="align-self:flex-start">Monthly goal</div>

            @if ($goal['has_target'])
                <div class="vy-goal-ring" style="--p:{{ min(100, max(0, $goal['pct'])) }}">
                    <div class="vy-goal-ring-inner">
                        <div class="vy-goal-ring-val">{{ $goal['pct'] }}%</div>
                        <div class="vy-goal-ring-cap">of goal</div>
                    </div>
                </div>
                <div class="vy-goal-net-lg">{{ $goal['month_label'] }} net</div>
                <div class="vy-goal-of">{{ $goal['month_net_display'] }} of {{ $goal['target_display'] }}</div>
                <div @class(['vy-goal-delta', 'vy-goal-delta--over' => $goal['over'], 'vy-goal-delta--under' => ! $goal['over']])>
                    {{ $goal['over'] ? '+' : '' }}{{ $goal['delta_display'] }} {{ $goal['over'] ? 'over' : 'to go' }}
                </div>
            @else
                <div class="vy-goal-ring is-empty">
                    <div class="vy-goal-ring-inner">
                        <div class="vy-goal-ring-val" style="font-size:22px;color:var(--vy-faint)">—</div>
                        <div class="vy-goal-ring-cap">no target</div>
                    </div>
                </div>
                <div class="vy-goal-net-lg">{{ $goal['month_label'] }} net</div>
                <div class="vy-goal-of">{{ $goal['month_net_display'] }} so far</div>
                <div class="vy-line-sub" style="margin-top:9px">Set a monthly target to track progress.</div>
            @endif

            <div class="vy-goal-sep"></div>

            @ownercan(OwnerTeamRole::CAP_ACTIONS)
                <div class="vy-goal-target" x-data="{ edit: {{ $errors->has('target') ? 'true' : 'false' }} }">
                    <div class="vy-goal-target-view" x-show="!edit">
                        @if ($goal['has_target'])
                            <span>Target <b>{{ $goal['target_display'] }}</b> /mo</span>
                        @else
                            <span class="vy-muted">Set a monthly target</span>
                        @endif
                        <button type="button" class="vy-goal-icobtn" x-on:click="edit = true" aria-label="Edit monthly target">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M4 20h4L18.5 9.5a2 2 0 0 0-3-3L5 17z"/></svg>
                        </button>
                    </div>
                    {{-- The target's currency is DERIVED server-side from the owner's ledger (never a form field),
                         so the ring + the stored target can never disagree and a tampered POST can't inject one. --}}
                    <form method="POST" action="{{ route('owner.bookings.target') }}" class="vy-goal-target-edit" x-show="edit" x-cloak>
                        @csrf
                        <span class="vy-muted" style="font-size:13px">{{ MoneyFormatter::symbol($goal['currency']) }}</span>
                        <input type="text" name="target" value="{{ old('target', $goal['target_input']) }}" inputmode="decimal" placeholder="6000" class="vy-input" aria-label="Monthly target amount">
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:8px 14px">Save</button>
                        <button type="button" class="vy-goal-icobtn" x-on:click="edit = false" aria-label="Cancel">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" style="width:14px;height:14px"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </form>
                    @error('target')
                        <div class="vy-line-sub" style="color:var(--vy-accent-text);margin-top:7px">{{ $message }}</div>
                    @enderror
                </div>
            @else
                <div class="vy-goal-target-view">
                    @if ($goal['has_target'])
                        <span>Target <b>{{ $goal['target_display'] }}</b> /mo</span>
                    @else
                        <span class="vy-muted">No monthly target set</span>
                    @endif
                </div>
            @endownercan
        </section>
    </div>

    {{-- ── Filters ────────────────────────────────────────────────────────────────────────────── --}}
    <div class="vy-card vy-fade" style="animation-delay:.06s;margin-bottom:16px">
        <div class="vy-bk-filters">
            @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $value => $label)
                <a href="{{ $qs(['tab' => $value]) }}" class="vy-btn {{ $filters['tab'] === $value ? 'vy-btn--accent' : 'vy-btn--ghost' }}" style="padding:7px 15px">{{ $label }}</a>
            @endforeach

            <span style="flex:1"></span>

            <form method="GET" action="{{ route('owner.bookings') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                @foreach ($preserved as $key => $val)
                    @if (! in_array($key, ['source', 'status', 'unit', 'q'], true))
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach

                <span style="position:relative;display:inline-flex;align-items:center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" style="position:absolute;left:11px;width:15px;height:15px;color:var(--vy-faint);pointer-events:none"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search guest, unit or confirmation" aria-label="Search bookings" class="vy-input" style="width:250px;flex:none;padding-left:34px">
                </span>

                <select name="source" onchange="this.form.submit()" aria-label="Filter by source" class="vy-input vy-bk-select">
                    <option value="">All sources</option>
                    @foreach ($sourceOptions as $opt)
                        <option value="{{ $opt['value'] }}" @selected($filters['source'] === $opt['value'])>{{ $opt['label'] }} ({{ $opt['count'] }})</option>
                    @endforeach
                </select>

                <select name="status" onchange="this.form.submit()" aria-label="Filter by status" class="vy-input vy-bk-select">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $opt)
                        <option value="{{ $opt['value'] }}" @selected($filters['status'] === $opt['value'])>{{ $opt['label'] }} ({{ $opt['count'] }})</option>
                    @endforeach
                </select>

                <select name="unit" onchange="this.form.submit()" aria-label="Filter by unit" class="vy-input vy-bk-select">
                    <option value="">All units</option>
                    @foreach ($unitOptions as $opt)
                        <option value="{{ $opt['id'] }}" @selected($filters['unit'] === $opt['id'])>{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="vy-bk-filters" style="margin-top:10px">
            {{-- Date range popover --}}
            <div class="vy-bk-dates" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
                <button type="button" class="vy-btn vy-btn--ghost" style="padding:8px 14px" x-on:click="open = !open" x-bind:aria-expanded="open ? 'true' : 'false'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M4 6h16v14H4zM4 10h16M8 3v4M16 3v4"/></svg>
                    {{ $dateLabel }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="width:13px;height:13px;color:var(--vy-faint)"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-cloak x-show="open" x-transition.opacity.duration.150ms x-on:click.outside="open = false" class="vy-bk-dates-panel">
                    @foreach ($ranges as $key => $label)
                        <a href="{{ $qs(['range' => $key, 'from' => null, 'to' => null]) }}" class="vy-bk-preset {{ ($filters['range'] ?? null) === $key ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                    <form method="GET" action="{{ route('owner.bookings') }}" class="vy-bk-dates-custom">
                        @foreach ($preserved as $key => $val)
                            @if (! in_array($key, ['from', 'to', 'range'], true))
                                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                            @endif
                        @endforeach
                        <label for="bk-from">From</label>
                        <input id="bk-from" type="date" name="from" value="{{ $filters['from'] }}" class="vy-input">
                        <label for="bk-to">To</label>
                        <input id="bk-to" type="date" name="to" value="{{ $filters['to'] }}" class="vy-input">
                        <button type="submit" class="vy-btn vy-btn--accent" style="padding:8px 14px;margin-top:4px;justify-content:center">Apply dates</button>
                    </form>
                    @if (($filters['from'] ?? null) !== null || ($filters['to'] ?? null) !== null || ($filters['range'] ?? null) !== null)
                        <a href="{{ $qs(['range' => null, 'from' => null, 'to' => null]) }}" class="vy-bk-clear">Clear dates</a>
                    @endif
                </div>
            </div>

            @ownercan(OwnerTeamRole::CAP_EXPORTS)
                <a href="{{ route('owner.bookings.export') }}" class="vy-btn vy-btn--ghost" style="padding:8px 14px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
                    Export
                </a>
            @endownercan

            @if ($anyFilter)
                <a href="{{ route('owner.bookings', ['tab' => $filters['tab']]) }}" class="vy-btn vy-btn--ghost" style="padding:8px 14px">Clear all</a>
            @endif
        </div>
    </div>

    {{-- ── Bookings table ─────────────────────────────────────────────────────────────────────── --}}
    <section class="vy-card vy-fade" style="animation-delay:.09s">
        @if ($rows === [])
            <div class="vy-empty">
                @if ($unitsCount === 0)
                    No units are linked to your account yet, so there are no bookings to show.
                @else
                    No bookings match this view.
                    <div class="vy-line-sub" style="margin-top:6px">Try “All”, or clear the filters above.</div>
                @endif
            </div>
        @else
            @php
                $th = function (array $h, string $label) {
                    $caret = $h['active'] ? ($h['dir'] === 'asc' ? '▲' : '▼') : '';

                    return '<a class="vy-th-sort'.($h['active'] ? ' is-active' : '').'" href="'.e($h['url']).'">'.e($label).' <span class="vy-th-caret">'.$caret.'</span></a>';
                };
            @endphp
            <div style="overflow-x:auto">
                <table class="vy-table">
                    <thead>
                        <tr>
                            <th>{!! $th($sortHead('guest'), 'Booking') !!}</th>
                            <th>Unit</th>
                            <th>{!! $th($sortHead('dates'), 'Dates') !!}</th>
                            <th class="vy-num">Nts</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th class="vy-num">{!! $th($sortHead('net'), 'Net to you') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="vy-row-clickable" onclick="window.location='{{ route('owner.bookings.show', $row['id']) }}'">
                                <td>
                                    <a class="vy-row-link" href="{{ route('owner.bookings.show', $row['id']) }}">
                                        <div class="vy-row-month">{{ $row['guest'] ?? 'Guest' }}</div>
                                        <div class="vy-row-sub">{{ $row['reference'] }}</div>
                                    </a>
                                </td>
                                <td>{{ $row['unit_label'] }}</td>
                                <td style="white-space:nowrap">{{ $row['stay'] }}</td>
                                <td class="vy-num">{{ $row['nights'] }}</td>
                                <td>{{ $row['source_label'] }}</td>
                                <td>
                                    <span class="vy-status vy-status--{{ $row['status_badge']['tone'] }}"><span class="vy-dot"></span>{{ $row['status_badge']['label'] }}</span>
                                </td>
                                <td class="vy-num vy-mono">
                                    @forelse ($row['net_displays'] as $display)
                                        <div>{{ $display }}</div>
                                    @empty
                                        <span class="vy-line-sub" title="No ledger entries for this booking yet">Not yet attributed</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="vy-bk-foot">
                <div class="vy-line-sub">
                    Showing {{ $page['from'] }}–{{ $page['to'] }} of {{ $page['total'] }} {{ Str::plural('booking', $page['total']) }}{{ $filters['tab'] === 'upcoming' ? ' · upcoming' : ($filters['tab'] === 'past' ? ' · past' : '') }}.
                </div>
                <div style="display:flex;align-items:center;gap:14px">
                    @if ($page['pages'] > 1)
                        <div class="vy-pager">
                            @if ($page['has_prev'])
                                <a class="vy-pager-btn" href="{{ $qs(['page' => $page['page'] - 1]) }}" aria-label="Previous page">‹</a>
                            @else
                                <span class="vy-pager-btn" aria-disabled="true">‹</span>
                            @endif
                            <span class="vy-line-sub">Page {{ $page['page'] }} of {{ $page['pages'] }}</span>
                            @if ($page['has_next'])
                                <a class="vy-pager-btn" href="{{ $qs(['page' => $page['page'] + 1]) }}" aria-label="Next page">›</a>
                            @else
                                <span class="vy-pager-btn" aria-disabled="true">›</span>
                            @endif
                        </div>
                    @endif
                    <a href="{{ route('owner.dashboard') }}" style="color:var(--vy-accent-text);font-weight:600;font-size:13px">Back to overview</a>
                </div>
            </div>
        @endif
    </section>

</x-owner.shell>
