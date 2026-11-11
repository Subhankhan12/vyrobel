{{--
    Owner Reports (exact-match pass · invariant #63) — the owner's REAL analytics for their OWN units, rebuilt to
    the decoded "Owner Reports" wireframe (design + behaviour). Shell from <x-owner.shell active="reports">; the
    notification popover is the shared shell bell (not a page-local card). All figures reconcile to the owner
    ledger/statements (#17); owner-scoped (#28); nothing fabricated (#39). Read-only (year/date-range/export are
    GETs — no write to gate, #20).

    HONESTY MAP (see App\Livewire\Owner\OwnerReports + docs/OWNER-REPORTS-AUDIT.md):
      • REAL — KPIs, net-by-month, per-unit (ADR/RevPAR/nights/occ/net/share), NET-by-channel (ledger×reservation
        source; Σ channels == period net), gross-to-net (reconciles #17, REAL agreement rate — never 15%), payout
        (masked IBAN CAP_PAYOUT-only), real Documents + exports (CAP_EXPORTS-gated).
      • HONEST-OMIT — the prior-year comparison (chart dashed bars + KPI Δ + hero badge): no prior-year data → not
        fabricated; the year dropdown lists REAL statement years, never fabricated 2024/2023.
      • REAL (Wave D) — "Email to me" queues the latest statement PDF to your own inbox (#73 track); the Tax & VAT
        summary IS computed (OwnerVatService — output/input/net VAT from FOUNDER-CONFIGURED rates × real gross/
        commission, reconciling #17), honestly labelled ("EXAMPLE rates — not for filing" / "not configured" /
        "not registered"), never a fabricated figure or a "Mark filed" toggle (#39). "PDF pack" stays honest-"Soon".
    RAW-BLADE RULE: every `@` follows a space, newline or tag (unregistered @click/@keydown pass through to Alpine).
--}}
<x-owner.shell active="reports" :title="__('livewire/owner/reports.title')" :subtitle="__('livewire/owner/reports.subtitle')" :viewer="$viewer">
    <style>
        .vy-rep-top { display:flex; align-items:center; justify-content:space-between; gap:14px 16px; flex-wrap:wrap; margin-bottom:20px; }
        .vy-rep-selectors { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .vy-rep-pop { position:relative; }
        .vy-rep-selbtn { display:inline-flex; align-items:center; gap:9px; padding:9px 15px; border-radius:var(--vy-r-pill); border:1px solid var(--vy-glass-border); background:rgba(255,255,255,.6); color:var(--vy-ink); font-family:var(--vy-sans); font-size:13px; font-weight:600; cursor:pointer; box-shadow:var(--vy-shadow-sm); }
        .vy-rep-selbtn:hover { background:rgba(255,255,255,.85); }
        .vy-rep-selbtn svg { width:11px; height:11px; color:var(--vy-faint); }
        .vy-rep-menu { position:absolute; top:calc(100% + 8px); left:0; z-index:41; width:236px; background:rgba(255,252,248,.96); backdrop-filter:blur(30px) saturate(1.15); border:1px solid var(--vy-glass-border); border-radius:16px; box-shadow:0 20px 50px rgba(60,30,10,.28), inset 0 1px 0 rgba(255,255,255,.75); padding:8px; box-sizing:border-box; }
        .vy-rep-menu--range { width:340px; }
        .vy-rep-menu--right { left:auto; right:0; width:250px; }
        .vy-rep-menu-head { font-size:10.5px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--vy-faint); padding:8px 12px 6px; }
        .vy-rep-menu-item { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:9px 12px; border-radius:11px; text-decoration:none; color:var(--vy-ink); font-size:13px; font-weight:500; }
        .vy-rep-menu-item .sub { font-size:11px; color:var(--vy-faint); font-weight:500; }
        .vy-rep-menu-item:hover { background:rgba(217,102,46,.08); }
        .vy-rep-menu-item.is-active { background:var(--vy-grad); color:#fff; }
        .vy-rep-menu-item.is-active .sub { color:rgba(255,255,255,.8); }
        .vy-rep-menu-sep { border-top:1px solid var(--vy-hairline); margin:6px 4px; }
        .vy-rep-menu-foot { font-size:11px; color:var(--vy-faint); padding:8px 12px 4px; border-top:1px solid var(--vy-hairline); margin-top:6px; }
        .vy-rep-range-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:2px; }
        .vy-rep-range-grid .lbl { font-size:11px; font-weight:600; color:var(--vy-muted); margin-bottom:6px; padding-left:2px; }
        .vy-rep-monthlist { max-height:188px; overflow-y:auto; display:flex; flex-direction:column; gap:1px; border:1px solid var(--vy-hairline); border-radius:10px; padding:4px; background:rgba(255,255,255,.4); }
        .vy-rep-month { text-align:left; padding:6px 9px; border:none; background:transparent; border-radius:7px; font-family:var(--vy-sans); font-size:12px; color:var(--vy-ink); cursor:pointer; }
        .vy-rep-month:hover { background:rgba(217,102,46,.08); }
        .vy-rep-month.is-sel { background:var(--vy-grad); color:#fff; font-weight:600; }
        .vy-rep-range-foot { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:12px; padding-top:10px; border-top:1px solid var(--vy-hairline); }
        .vy-rep-exitem { display:flex; align-items:center; gap:11px; width:100%; text-align:left; padding:9px 10px; border-radius:11px; background:transparent; border:none; cursor:pointer; text-decoration:none; color:var(--vy-ink); }
        .vy-rep-exitem:hover { background:rgba(217,102,46,.08); }
        .vy-rep-exitem.is-soon { cursor:default; }
        .vy-rep-exicon { width:32px; height:32px; border-radius:9px; background:rgba(217,102,46,.13); color:#B4400F; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .vy-rep-exicon.is-muted { background:rgba(120,70,40,.08); color:var(--vy-muted); }
        .vy-rep-exttl { font-size:13px; font-weight:500; }
        .vy-rep-exsub { font-size:11px; color:var(--vy-faint); margin-top:1px; }

        /* ⚠️ `minmax(0, …)`, not a bare `1fr` — that `auto` minimum is a min-content floor the tracks
           cannot shrink below, and it was 113px of sideways scroll at 375px. The band then steps down
           the way the shell's `.vy-kpi-grid` family does rather than staying 3-up on a phone. */
        .vy-rep-kpis { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; margin-bottom:20px; }
        @media (max-width:640px) { .vy-rep-kpis { grid-template-columns:minmax(0,1fr); } }
        .vy-rep-kpi-val { font-family:var(--vy-display); font-weight:600; font-size:30px; letter-spacing:-.02em; font-variant-numeric:tabular-nums; }
        .vy-rep-kpi-delta { font-size:13px; font-weight:500; color:var(--vy-accent-text); }
        .vy-rep-kpi-note { font-size:12px; color:var(--vy-faint); }

        .vy-rep-cols { display:grid; grid-template-columns:minmax(0,1fr) 232px; gap:20px; align-items:start; }
        @media (max-width:1040px) { .vy-rep-cols { grid-template-columns:1fr; } }

        .vy-rep-chart { display:flex; align-items:flex-end; gap:10px; height:164px; margin-top:6px; }
        /* ⚠️ THE LAST 2px. Each bar's value label is `position:absolute; left:0; right:0` centred over
           its column with `white-space: nowrap`, so on a phone — twelve columns in ~300px — a label wider
           than its own column overflows it EQUALLY on both sides. Eleven of those bleeds land on a
           neighbouring column and are invisible; the twelfth lands past the card and is 2px of pushable
           page.
           ⚠️ THE FIGURES ARE NOT CLIPPED AND NOT SHRUNK. Clipping would cut a digit off a money figure
           and truncation would be worse than the overflow. The row is simply given the small inset the
           outermost labels were already using, so the bleed has somewhere to land inside the card. */
        /* ⚠️⚠️ THE LAST 13px, AND IT IS THE SAME TWO-PART LESSON THIS FILE'S SHELL ALREADY WRITES DOWN
           (owner.blade.php:800): the container floor and the ITEM floor are different things.
           `.vy-rep-bar-col` is `flex: 1` with `min-width: auto` — a content-based floor set by the month
           label — so twelve columns refused to shrink below their labels and the row stayed wider than
           the card. Relaxing the columns is what actually clears it.
           ⚠️ AND THEN THE LABELS THEMSELVES. Both the month name and the value above each bar are
           CENTRED and `nowrap`, so a label wider than its own ~24px column bleeds equally past both its
           edges; eleven of those bleeds land on a neighbour and are invisible, the twelfth lands past the
           card. They are stepped down a size on a phone rather than clipped or truncated — every month
           and every figure stays completely readable, which on a money surface is the point. */
        @media (max-width:860px) {
            .vy-rep-bar-col { min-width:0; }
            .vy-rep-bar-label { font-size:8.5px; }
            .vy-rep-bar-val .txt { font-size:8.5px; }
            .vy-rep-chart { gap:6px; padding-left:6px; padding-right:6px; }
        }
        .vy-rep-bar-col { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; }
        .vy-rep-bar-area { position:relative; width:100%; height:120px; }
        .vy-rep-bar-prior { position:absolute; left:8%; right:8%; bottom:0; border-radius:6px 6px 0 0; background:rgba(120,70,40,.07); border:1px dashed rgba(120,70,40,.32); border-bottom:none; }
        .vy-rep-bar { position:absolute; left:21%; right:21%; bottom:0; border-radius:6px 6px 3px 3px; background:var(--vy-grad); transition:filter .14s; }
        .vy-rep-bar-col:hover .vy-rep-bar { filter:brightness(1.07); }
        .vy-rep-bar-val { position:absolute; left:0; right:0; display:flex; justify-content:center; padding-bottom:4px; }
        .vy-rep-bar-val .txt { font-size:9.5px; color:var(--vy-ink); font-weight:500; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .vy-rep-bar-val .peak { display:inline-flex; align-items:center; gap:3px; padding:2px 7px; border-radius:20px; background:var(--vy-grad); color:#fff; font-size:9.5px; font-weight:600; font-variant-numeric:tabular-nums; white-space:nowrap; box-shadow:0 3px 9px rgba(194,65,12,.34); }
        .vy-rep-bar-label { font-size:10.5px; color:var(--vy-muted); }
        .vy-rep-legend { display:flex; align-items:center; gap:12px; font-size:11px; color:var(--vy-muted); }
        .vy-rep-legend .sw { width:9px; height:9px; border-radius:2px; }

        .vy-rep-perf-head { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:6px; }
        .vy-rep-perf-stats { display:flex; gap:22px; }
        .vy-rep-perf-stat { text-align:right; }
        .vy-rep-perf-stat .k { font-size:10px; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--vy-faint); }
        .vy-rep-perf-stat .v { font-family:var(--vy-display); font-weight:600; font-size:15px; font-variant-numeric:tabular-nums; margin-top:2px; }
        /* ⚠️ The same bare-`1fr` floor, five tracks of it. These rows are FIGURES (nights · occupancy ·
           net · share) with no control in them, so they keep their columns and simply narrow — the unit
           name in the first track wraps. Not a scroll wrapper: the header row above is a separate element
           and a scroller around one but not the other would slide the columns out of alignment. */
        .vy-rep-urow { display:grid; grid-template-columns:1.5fr .8fr .9fr 1fr 1.1fr; grid-template-columns:minmax(0,1.5fr) minmax(0,.8fr) minmax(0,.9fr) minmax(0,1fr) minmax(0,1.1fr); align-items:center; gap:14px; padding:12px 10px; margin:0 -10px; border-top:1px solid var(--vy-hairline); font-size:13px; }
        .vy-rep-urow.head { border-top:none; padding-bottom:8px; font-size:10px; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:var(--vy-faint); }
        .vy-rep-urow .rt { text-align:right; font-variant-numeric:tabular-nums; }
        .vy-rep-uname { font-size:13.5px; font-weight:500; }
        .vy-rep-usub { font-size:11.5px; color:var(--vy-faint); margin-top:1px; }
        .vy-rep-share { display:flex; align-items:center; gap:8px; }
        .vy-rep-share-bar { flex:1; height:6px; border-radius:4px; background:rgba(120,70,40,.1); overflow:hidden; }
        .vy-rep-share-bar > span { display:block; height:100%; border-radius:4px; background:var(--vy-grad-peach); }
        .vy-rep-share .p { font-size:11px; color:var(--vy-muted); font-variant-numeric:tabular-nums; width:32px; text-align:right; }
        .vy-rep-uall { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 0 2px; border-top:1px solid rgba(120,70,40,.16); margin-top:2px; }
        .vy-rep-uall .k { font-family:var(--vy-display); font-weight:600; font-size:14px; }
        .vy-rep-uall .v { font-family:var(--vy-display); font-weight:600; font-size:16px; color:var(--vy-accent-text); letter-spacing:-.02em; font-variant-numeric:tabular-nums; }

        .vy-rep-chan-bar { height:22px; border-radius:7px; overflow:hidden; box-shadow:inset 0 0 0 1px rgba(120,70,40,.08); }
        .vy-rep-chan-legend { display:flex; flex-wrap:wrap; gap:10px 24px; margin-top:14px; }
        .vy-rep-chan-item { display:flex; align-items:center; gap:8px; }
        .vy-rep-chan-item .sw { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
        .vy-rep-chan-item .nm { font-size:13px; font-weight:500; }
        .vy-rep-chan-item .pc { font-size:12px; color:var(--vy-faint); font-variant-numeric:tabular-nums; }
        .vy-rep-chan-item .amt { font-size:12.5px; color:#6B6058; font-weight:500; font-variant-numeric:tabular-nums; }

        .vy-rep-gtn-row { display:flex; align-items:center; justify-content:space-between; gap:10px; font-size:12px; color:var(--vy-muted); }
        .vy-rep-gtn-row .amt { font-size:12.5px; font-variant-numeric:tabular-nums; }
        .vy-rep-gtn-net { padding-top:10px; margin-top:2px; border-top:1px solid rgba(120,70,40,.16); }
        .vy-rep-gtn-net .lbl { font-size:12.5px; font-weight:500; color:var(--vy-ink); }
        .vy-rep-gtn-net .amt { font-family:var(--vy-display); font-weight:600; font-size:14px; color:var(--vy-accent-text); }

        .vy-rep-hero { position:relative; }
        .vy-rep-hero-glow { position:absolute; inset:-30px -28px -34px; background:radial-gradient(ellipse at 50% 55%, rgba(232,112,58,.38), transparent 70%); filter:blur(26px); pointer-events:none; }
        /* ⚠️ THE LAST 13px, AND IT IS DECORATION — the THIRD instance of this exact shape, after
           `.vy-tonight-glow` (operator shell) and `.vy-ov-payout-halo` (owner shell): an absolutely
           positioned, `pointer-events: none` blur that bleeds ~28px past its card on each side. Harmless
           in a wide column, 13px of pushable page at 375px. Contained, not removed.
           ⚠️ MUST SIT AFTER the rule above, not before it: `inset` sets `left`/`right`, the selectors have
           equal specificity, so source order is the only thing that decides which wins. */
        @media (max-width:860px) { .vy-rep-hero-glow { left:0; right:0; } }
        .vy-rep-hero-card { position:relative; background:var(--vy-grad-dark); border-radius:20px; box-shadow:var(--vy-shadow-dark); padding:20px; color:#fff; }
        .vy-rep-hero-eyebrow { font-size:11px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#B9ABA0; margin-bottom:12px; }
        .vy-rep-hero-fig { font-family:var(--vy-display); font-weight:600; font-size:28px; letter-spacing:-.02em; font-variant-numeric:tabular-nums; }
        .vy-rep-hero-sub { font-size:13px; color:#B9ABA0; margin-top:5px; }
        .vy-rep-hero-badge { display:inline-flex; align-items:center; gap:6px; margin-top:14px; padding:6px 12px; border-radius:var(--vy-r-pill); background:var(--vy-grad); color:#fff; font-size:12px; font-weight:500; box-shadow:0 4px 12px rgba(194,65,12,.35); }

        .vy-rep-doc { display:flex; align-items:center; gap:10px; padding:9px 10px; margin:0 -10px; border-radius:12px; text-decoration:none; color:var(--vy-ink); }
        .vy-rep-doc:hover { background:rgba(255,255,255,.55); }
        .vy-rep-doc-ic { width:30px; height:30px; border-radius:50%; background:rgba(217,102,46,.13); color:#B4400F; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .vy-rep-payout-line { display:flex; align-items:center; gap:9px; font-size:12.5px; color:#4A403A; }

        .vy-rep-vat { background:rgba(255,252,248,.5); border:1px dashed rgba(120,70,40,.28); margin-top:20px; }
    </style>

    @if (session('owner_notice'))
        <div class="vy-card vy-fade" role="status" style="margin-bottom:14px;border-color:var(--vy-accent-border);padding:14px 18px;font-size:13px;color:var(--vy-ink)">
            {{ session('owner_notice') }}
        </div>
    @endif

    @if ($unitsCount === 0)
        <div class="vy-card vy-fade"><div class="vy-empty">{{ __('livewire/owner/reports.no_units') }}</div></div>
    @else
    {{-- ── Top control row: reporting-year dropdown + custom date range + Export (bell/account are in the shell) ── --}}
    <div class="vy-rep-top">
        <div class="vy-rep-selectors">
            {{-- Reporting year (real statement years, never fabricated 2024/2023) --}}
            <div class="vy-rep-pop" x-data="{ open: false }" @keydown.escape.window="open = false">
                <button type="button" class="vy-rep-selbtn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;color:var(--vy-accent)"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M3 9h18M8 3v3M16 3v3"/></svg>
                    <span>{{ $activeRange['label'] }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click.outside="open = false" class="vy-rep-menu">
                    <div class="vy-rep-menu-head">{{ __('livewire/owner/reports.reporting_year') }}</div>
                    @foreach ($yearOptions as $y)
                        <a href="{{ $y['href'] }}" class="vy-rep-menu-item {{ $y['active'] ? 'is-active' : '' }}"><span>{{ $y['label'] }}</span><span class="sub">{{ $y['sub'] }}</span></a>
                    @endforeach
                    <div class="vy-rep-menu-sep"></div>
                    @foreach ($rangeOptions as $r)
                        <a href="{{ $r['href'] }}" class="vy-rep-menu-item {{ $r['active'] ? 'is-active' : '' }}"><span>{{ $r['label'] }}</span></a>
                    @endforeach
                    <div class="vy-rep-menu-foot">{{ __('livewire/owner/reports.earlier_years') }}</div>
                </div>
            </div>

            {{-- Custom date range — a real GET over the owner ledger (Apply builds the ?from=&to= URL) --}}
            <div class="vy-rep-pop" x-data="{ open: false, from: @js($activeRange['from']), to: @js($activeRange['to']) }" @keydown.escape.window="open = false">
                <button type="button" class="vy-rep-selbtn" @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
                    <span>{{ __('livewire/owner/reports.custom_range') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click.outside="open = false" class="vy-rep-menu vy-rep-menu--range">
                    <div class="vy-rep-menu-head">{{ __('livewire/owner/reports.custom_date_range') }}</div>
                    <div class="vy-rep-range-grid">
                        <div>
                            <div class="lbl">{{ __('livewire/owner/reports.range_from') }}</div>
                            <div class="vy-rep-monthlist">
                                @foreach ($monthOptions as $mo)
                                    <button type="button" class="vy-rep-month" :class="from === @js($mo['value']) ? 'is-sel' : ''" @click="from = @js($mo['value'])">{{ $mo['label'] }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <div class="lbl">{{ __('livewire/owner/reports.range_to') }}</div>
                            <div class="vy-rep-monthlist">
                                @foreach ($monthOptions as $mo)
                                    <button type="button" class="vy-rep-month" :class="to === @js($mo['value']) ? 'is-sel' : ''" @click="to = @js($mo['value'])">{{ $mo['label'] }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="vy-rep-range-foot">
                        <a class="vy-btn vy-btn--ghost" href="{{ route('owner.reports') }}" style="padding:6px 13px;font-size:12.5px">{{ __('common.clear') }}</a>
                        <button type="button" class="vy-btn vy-btn--accent" style="padding:6px 15px;font-size:12.5px" :style="(!from || !to) ? { opacity: '.5', cursor: 'not-allowed' } : {}" @click="if (from && to) window.location = @js(route('owner.reports')) + '?from=' + from + '&to=' + to">{{ __('common.apply') }}</button>
                    </div>
                </div>
            </div>
        </div>

        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
            <div class="vy-rep-pop" x-data="{ open: false }" @keydown.escape.window="open = false">
                <button type="button" class="vy-btn vy-btn--accent" @click="open = !open" style="gap:7px" :aria-expanded="open ? 'true' : 'false'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>
                    {{ __('livewire/owner/reports.export_button') }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.85)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click.outside="open = false" class="vy-rep-menu vy-rep-menu--right">
                    <div class="vy-rep-menu-head">{{ __('livewire/owner/reports.export_heading', ['period' => $activeRange['label']]) }}</div>
                    @foreach ($exportItems as $ex)
                        @if (($ex['post'] ?? null))
                            {{-- REAL "Email to me" — a POST that queues the owner's latest statement PDF (GATE 4) --}}
                            <form method="POST" action="{{ $ex['post'] }}" style="margin:0">
                                @csrf
                                <button type="submit" class="vy-rep-exitem" style="width:100%;border:0;background:none;text-align:left;cursor:pointer">
                                    <span class="vy-rep-exicon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 8l9 6 9-6"/></svg>
                                    </span>
                                    <span style="flex:1;min-width:0"><span class="vy-rep-exttl">{{ $ex['title'] }}</span><span class="vy-rep-exsub">{{ $ex['sub'] }}</span></span>
                                </button>
                            </form>
                        @elseif ($ex['soon'])
                            <span class="vy-rep-exitem is-soon">
                                <span class="vy-rep-exicon is-muted">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 8l9 6 9-6"/></svg>
                                </span>
                                <span style="flex:1;min-width:0"><span class="vy-rep-exttl">{{ $ex['title'] }}</span><span class="vy-rep-exsub">{{ $ex['sub'] }}</span></span>
                                <span class="vy-soon-tag">{{ __('livewire/owner/reports.soon') }}</span>
                            </span>
                        @else
                            <a href="{{ $ex['href'] }}" class="vy-rep-exitem">
                                <span class="vy-rep-exicon {{ in_array($ex['key'], ['csv', 'xls'], true) ? 'is-muted' : '' }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg>
                                </span>
                                <span style="flex:1;min-width:0"><span class="vy-rep-exttl">{{ $ex['title'] }}</span><span class="vy-rep-exsub">{{ $ex['sub'] }}</span></span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endownercan
    </div>

    {{-- ── KPIs — REAL (net reconciles to statements; occupancy + nights from the metric layer; Δ honest-omitted) ── --}}
    <div class="vy-rep-kpis vy-fade" style="animation-delay:.02s">
        <div class="vy-card" style="padding:18px 20px">
            <div class="vy-kpi-label">{{ __('livewire/owner/reports.kpi_net_earned', ['period' => $kpis['net_period']]) }}</div>
            <div style="display:flex;align-items:baseline;gap:8px;margin-top:8px">
                <span class="vy-rep-kpi-val">{{ $kpis['net'] }}</span>
                @if ($kpis['net_delta'])<span class="vy-rep-kpi-delta">{{ $kpis['net_delta'] }}</span>@endif
            </div>
            {{-- WAVE 2 · #20 — this figure is Σ statements PLUS ledger not yet statemented, so "reconciles to
                 your statements" was a narrower claim than the number. Tax Center prints the statement-only
                 figure for the same year, and the two differed. --}}
            <div class="vy-kpi-sub">{{ __('livewire/owner/reports.kpi_net_sub') }}</div>
        </div>
        <div class="vy-card" style="padding:18px 20px">
            <div class="vy-kpi-label">{{ __('livewire/owner/reports.kpi_avg_occupancy') }}</div>
            <div class="vy-rep-kpi-val" style="margin-top:8px">{{ $kpis['occupancy'] }}</div>
            <div class="vy-kpi-sub">{{ trans_choice('livewire/owner/reports.kpi_across_units', $unitsCount, ['count' => $unitsCount]) }}</div>
        </div>
        <div class="vy-card" style="padding:18px 20px">
            <div class="vy-kpi-label">{{ __('livewire/owner/reports.kpi_nights_sold') }}</div>
            <div style="display:flex;align-items:baseline;gap:8px;margin-top:8px">
                <span class="vy-rep-kpi-val">{{ $kpis['nights'] }}</span>
                <span class="vy-rep-kpi-note">{{ __('livewire/owner/reports.kpi_room_nights') }}</span>
            </div>
            <div class="vy-kpi-sub">{{ __('livewire/owner/reports.kpi_in_period') }}</div>
        </div>
    </div>

    <div class="vy-rep-cols">
        {{-- ── LEFT ── --}}
        <div class="vy-stack">
            {{-- Net earnings by month --}}
            <section class="vy-card vy-fade" style="animation-delay:.05s">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px">
                    <h2 class="vy-card-title">{{ __('livewire/owner/reports.net_by_month') }}</h2>
                    <div style="display:flex;align-items:center;gap:14px">
                        @if ($netByMonth['has_prior'])
                            <div class="vy-rep-legend">
                                <span style="display:inline-flex;align-items:center;gap:5px"><span class="sw" style="background:var(--vy-grad)"></span>{{ __('livewire/owner/reports.legend_this_year') }}</span>
                                <span style="display:inline-flex;align-items:center;gap:5px"><span class="sw" style="border:1px dashed rgba(120,70,40,.5);background:rgba(120,70,40,.07)"></span>{{ $netByMonth['prior_year_label'] }}</span>
                            </div>
                        @endif
                        <span style="font-size:12px;color:var(--vy-faint)">{{ $netByMonth['chart_range'] }}</span>
                    </div>
                </div>
                @if ($netByMonth['has_data'])
                    <div class="vy-rep-chart">
                        @foreach ($netByMonth['months'] as $m)
                            <div class="vy-rep-bar-col" title="{{ $m['label'] }} · {{ $m['net_display'] }}">
                                <div class="vy-rep-bar-area">
                                    @if ($netByMonth['has_prior'] && $m['prior_pct'] > 0)
                                        <div class="vy-rep-bar-prior" style="height:{{ $m['prior_pct'] }}%"></div>
                                    @endif
                                    {{-- HONEST ZERO: a month that earned nothing draws NOTHING. This bar used to
                                         carry a two-percent floor, so five genuinely EUR0.00 months each rendered a
                                         visible bar — a real figure contradicted by its own chart. The hairline
                                         keeps a small non-zero month visible without inventing height for a zero. --}}
                                    <div class="vy-rep-bar" style="height:{{ (int) $m['pct'] }}%{{ $m['pct'] > 0 ? ';min-height:3px' : '' }}"></div>
                                    <div class="vy-rep-bar-val" style="bottom:{{ (int) $m['pct'] }}%">
                                        @if ($m['is_peak'])
                                            <span class="peak"><svg viewBox="0 0 24 24" fill="currentColor" style="width:8px;height:8px"><path d="M12 2l2.9 6.9 7.1.6-5.4 4.7 1.6 7L12 18.5 5.8 21.2l1.6-7L2 9.5l7.1-.6z"/></svg>{{ $m['net_display'] }}</span>
                                        @else
                                            <span class="txt">{{ $m['net_display'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="vy-rep-bar-label">{{ $m['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty" style="margin-top:14px">{{ __('livewire/owner/reports.no_earnings') }}</div>
                @endif
            </section>

            {{-- Performance by unit --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s">
                <div class="vy-rep-perf-head">
                    <h2 class="vy-card-title">{{ __('livewire/owner/reports.performance_by_unit') }}</h2>
                    <div class="vy-rep-perf-stats">
                        <div class="vy-rep-perf-stat" title="{{ __('livewire/owner/reports.adr_title') }}"><div class="k">{{ __('livewire/owner/reports.adr') }}</div><div class="v">{{ $perfHeader['adr'] }}</div></div>
                        <div class="vy-rep-perf-stat" title="{{ __('livewire/owner/reports.revpar_title') }}"><div class="k">{{ __('livewire/owner/reports.revpar') }}</div><div class="v">{{ $perfHeader['revpar'] }}</div></div>
                    </div>
                </div>
                <div class="vy-rep-urow head">
                    <span>{{ __('livewire/owner/reports.col_unit') }}</span><span class="rt">{{ __('livewire/owner/reports.col_nights') }}</span><span class="rt">{{ __('livewire/owner/reports.col_occupancy') }}</span><span class="rt">{{ __('livewire/owner/reports.col_net_earned') }}</span><span>{{ __('livewire/owner/reports.col_share') }}</span>
                </div>
                @forelse ($perUnit as $u)
                    <div class="vy-rep-urow">
                        <span><span class="vy-rep-uname">{{ $u['name'] }}</span>@if ($u['sub'])<span class="vy-rep-usub">{{ $u['sub'] }}</span>@endif</span>
                        <span class="rt">{{ $u['nights'] }}</span>
                        <span class="rt">{{ $u['occupancy_pct'] }}%</span>
                        <span class="rt" style="font-family:var(--vy-display);font-weight:500">{{ $u['net'] }}</span>
                        <span class="vy-rep-share"><span class="vy-rep-share-bar"><span style="width:{{ $u['share_pct'] }}%"></span></span><span class="p">{{ $u['share_pct'] }}%</span></span>
                    </div>
                @empty
                    <div class="vy-empty">{{ __('livewire/owner/reports.no_units_yet') }}</div>
                @endforelse
                <div class="vy-rep-uall"><span class="k">{{ __('livewire/owner/reports.all_units') }}</span><span class="v">{{ $allUnitsNet }}</span></div>
                <div class="vy-fx-note">{{ __('livewire/owner/reports.perf_note') }}</div>
            </section>

            {{-- Booking sources — NET by channel (real; Σ channels == period net) --}}
            <section class="vy-card vy-fade" style="animation-delay:.11s">
                <div class="vy-rep-perf-head">
                    <h2 class="vy-card-title">{{ __('livewire/owner/reports.booking_sources') }}</h2>
                    <div style="font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--vy-faint)">{{ __('livewire/owner/reports.net_by_channel', ['period' => $activeRange['label']]) }}</div>
                </div>
                @if ($byChannel['has_data'])
                    <div class="vy-rep-chan-bar" style="background:{{ $byChannel['gradient'] }};margin-top:6px"></div>
                    <div class="vy-rep-chan-legend">
                        @foreach ($byChannel['rows'] as $c)
                            <div class="vy-rep-chan-item">
                                <span class="sw" style="background:{{ $c['color'] }}"></span>
                                <span class="nm">{{ $c['label'] }}</span>
                                <span class="pc">{{ $c['pct'] }}%</span>
                                <span class="amt">{{ $c['amount'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-empty" style="margin-top:12px">{{ __('livewire/owner/reports.no_bookings') }}</div>
                @endif
            </section>
        </div>

        {{-- ── RIGHT (232px rail) ── --}}
        <div class="vy-stack">
            {{-- Dark hero: the period net (YoY badge honest-omitted → real nights·units sub) --}}
            <div class="vy-rep-hero vy-fade" style="animation-delay:.05s">
                <div class="vy-rep-hero-glow"></div>
                <div class="vy-rep-hero-card">
                    <div class="vy-rep-hero-eyebrow">{{ $hero['eyebrow'] }}</div>
                    <div class="vy-rep-hero-fig">{{ $hero['net'] }}</div>
                    <div class="vy-rep-hero-sub">{{ __('livewire/owner/reports.hero_sub') }}</div>
                    @if ($hero['sub'] !== '')<div class="vy-rep-hero-badge">{{ $hero['sub'] }}</div>@endif
                </div>
            </div>

            {{-- Gross to net — reconciles (#17), REAL commission rate --}}
            <section class="vy-card vy-fade" style="animation-delay:.08s;padding:20px">
                <div class="vy-eyebrow" style="margin-bottom:12px">{{ __('livewire/owner/reports.gross_to_net') }}</div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div class="vy-rep-gtn-row"><span>{{ __('livewire/owner/reports.gross_revenue') }}</span><span class="amt" style="color:var(--vy-ink);font-weight:500">{{ $grossToNet['gross'] }}</span></div>
                    <div class="vy-rep-gtn-row"><span>{{ $commissionLabel }}</span><span class="amt">{{ $grossToNet['commission'] }}</span></div>
                    <div class="vy-rep-gtn-row"><span>{{ __('livewire/owner/reports.fees_ota') }}</span><span class="amt">{{ $grossToNet['fees'] }}</span></div>
                    @if ($grossToNet['has_adjustments'])
                        <div class="vy-rep-gtn-row"><span>{{ __('livewire/owner/reports.costs_adjustments') }}</span><span class="amt">{{ $grossToNet['adjustments'] }}</span></div>
                    @endif
                    {{-- Tax withheld (#73 · P2-2): a REAL withholding deduction, shown only when configured/non-zero
                         (matches your Tax Center + annual summary). €0 → hidden, never a fabricated line (#39). --}}
                    @if ($grossToNet['has_withholding'])
                        <div class="vy-rep-gtn-row"><span>{{ __('livewire/owner/reports.tax_withheld') }}</span><span class="amt">{{ $grossToNet['withholding'] }}</span></div>
                    @endif
                    <div class="vy-rep-gtn-row vy-rep-gtn-net"><span class="lbl">{{ __('livewire/owner/reports.net_to_you') }}</span><span class="amt">{{ $grossToNet['net'] }}</span></div>
                </div>
                @unless ($grossToNet['reconciles'])
                    {{-- Safety net: the buckets now cover every entry type, so this never shows — but if a figure
                         ever failed to reconcile we say so honestly rather than print a total that doesn't add up. --}}
                    <p class="vy-line-sub" style="font-size:11.5px;margin-top:10px;color:var(--vy-amber-ink,#92400e)">{{ __('livewire/owner/reports.not_reconciled') }}</p>
                @endunless
            </section>

            {{-- Next payout / latest payout --}}
            <a href="{{ route('owner.payouts') }}" class="vy-card vy-card--tappable vy-fade" style="animation-delay:.11s;padding:20px;display:block;text-decoration:none;color:inherit">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px">
                    <span class="vy-eyebrow">{{ __('livewire/owner/reports.latest_payout') }}</span>
                    @if ($payout['status'])
                        <span class="vy-status vy-status--{{ $payout['status_key'] }}"><span class="vy-dot"></span>{{ $payout['status'] }}</span>
                    @endif
                </div>
                @if ($payout['amount'])
                    <div style="font-family:var(--vy-display);font-weight:600;font-size:24px;letter-spacing:-.02em;font-variant-numeric:tabular-nums">{{ $payout['amount'] }}</div>
                    <div style="font-size:12px;color:var(--vy-faint);margin-top:4px">{{ __('livewire/owner/reports.paid_this_year', ['amount' => $payout['paid_year']]) }}</div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-top:14px;padding-top:14px;border-top:1px solid var(--vy-hairline)">
                        @if ($payout['date'])
                            <div class="vy-rep-payout-line"><svg viewBox="0 0 24 24" fill="none" stroke="#B4400F" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>{{ $payout['date'] }}</div>
                        @endif
                        @if ($payout['method_masked'])
                            <div class="vy-rep-payout-line" style="color:var(--vy-muted)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;flex-shrink:0"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>{{ $payout['method_masked'] }}</div>
                        @endif
                    </div>
                @else
                    <div class="vy-empty" style="padding:20px 0">{{ __('livewire/owner/reports.no_payouts') }}</div>
                @endif
            </a>

            {{-- Documents (real PDFs) --}}
            <section class="vy-card vy-fade" style="animation-delay:.14s;padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="vy-eyebrow">{{ __('livewire/owner/reports.documents') }}</span>
                    <a href="{{ route('owner.documents') }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600">{{ __('livewire/owner/reports.documents_all') }}</a>
                </div>
                @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                    @forelse ($documents as $d)
                        <a href="{{ $d['href'] }}" class="vy-rep-doc">
                            <span class="vy-rep-doc-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></svg></span>
                            <span style="flex:1;min-width:0"><span style="display:block;font-size:12.5px;font-weight:500">{{ $d['name'] }}</span><span style="display:block;font-size:10.5px;color:var(--vy-faint);margin-top:1px">{{ $d['sub'] }}</span></span>
                        </a>
                    @empty
                        <div class="vy-empty" style="padding:20px 0">{{ __('livewire/owner/reports.no_documents') }}</div>
                    @endforelse
                @else
                    <div class="vy-line-sub">{{ __('livewire/owner/reports.documents_no_access') }}</div>
                @endownercan
            </section>
        </div>
    </div>

    {{-- ── Tax & VAT summary — REAL (Wave D · #73): computed from FOUNDER-CONFIGURED rates × real amounts (OwnerVatService).
         (Superseded the earlier KIND-2 refusal — VAT is now a real engine, not a deferred card.)
         Honestly labelled; an EXAMPLE-rate figure is flagged "not for filing"; unconfigured/unregistered → the honest
         state (no figure). NEVER a fabricated figure an owner could file on (#39). --}}
    <section class="vy-card {{ $vat['available'] ? '' : 'vy-rep-vat' }} vy-fade" style="animation-delay:.17s">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
            <div style="display:flex;align-items:center;gap:9px">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" style="width:17px;height:17px"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                <div>
                    <h2 class="vy-card-title">{{ __('livewire/owner/reports.vat_heading') }}</h2>
                    <div class="vy-line-sub">{{ $taxCountry ? __($vat['available'] ? 'livewire/owner/reports.vat_sub_available_country' : 'livewire/owner/reports.vat_sub_unavailable_country', ['country' => $taxCountry]) : __($vat['available'] ? 'livewire/owner/reports.vat_sub_available' : 'livewire/owner/reports.vat_sub_unavailable') }}</div>
                </div>
            </div>
            @if ($vat['available'] && $vat['is_example'])
                <span class="vy-soon-tag" style="flex-shrink:0;background:rgba(194,110,20,.14);color:var(--vy-amber-text);border-color:rgba(194,110,20,.3)">{{ __('livewire/owner/reports.vat_tag_example') }}</span>
            @elseif ($vat['available'])
                <span class="vy-soon-tag" style="flex-shrink:0;background:rgba(34,120,60,.12);color:#227a3c;border-color:rgba(34,120,60,.25)">{{ __('livewire/owner/reports.vat_tag_computed') }}</span>
            @else
                <span class="vy-soon-tag" style="flex-shrink:0">{{ __('livewire/owner/reports.vat_tag_unavailable') }}</span>
            @endif
        </div>

        @if ($vat['available'])
            @if ($vat['is_example'])
                <div style="margin-top:12px;padding:11px 14px;border-radius:12px;background:rgba(194,110,20,.1);border:1px solid rgba(194,110,20,.28);font-size:12px;color:#8a4c0e;line-height:1.55">
                    {!! __('livewire/owner/reports.vat_example_warning', ['country' => e((string) $taxCountry)]) !!}
                </div>
            @endif
            <div style="overflow-x:auto;margin-top:14px">
                <table style="width:100%;border-collapse:collapse;font-size:12.5px;min-width:460px">
                    <thead><tr style="color:var(--vy-muted);text-align:right">
                        <th style="text-align:left;padding:6px 8px;font-weight:600">{{ __('livewire/owner/reports.vat_col_quarter') }}</th>
                        <th style="padding:6px 8px;font-weight:600">{{ __('livewire/owner/reports.vat_col_output') }}</th>
                        <th style="padding:6px 8px;font-weight:600">{{ __('livewire/owner/reports.vat_col_input') }}</th>
                        <th style="padding:6px 8px;font-weight:600">{{ __('livewire/owner/reports.vat_col_net') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($vat['quarters'] as $q)
                            <tr style="border-top:1px solid var(--vy-hairline);text-align:right">
                                <td style="text-align:left;padding:7px 8px;color:var(--vy-ink);font-weight:500">{{ $q['label'] }}</td>
                                <td style="padding:7px 8px;font-variant-numeric:tabular-nums">{{ $q['output_display'] }}</td>
                                <td style="padding:7px 8px;font-variant-numeric:tabular-nums;color:var(--vy-muted)">−{{ $q['input_display'] }}</td>
                                <td style="padding:7px 8px;font-variant-numeric:tabular-nums;font-weight:600;color:var(--vy-ink)">{{ $q['net_display'] }}</td>
                            </tr>
                        @endforeach
                        <tr style="border-top:2px solid var(--vy-hairline);text-align:right;font-weight:700">
                            <td style="text-align:left;padding:8px;color:var(--vy-ink)">{{ __('livewire/owner/reports.vat_year_total', ['year' => $vat['year']]) }}</td>
                            <td style="padding:8px;font-variant-numeric:tabular-nums">{{ $vat['annual']['output_display'] }}</td>
                            <td style="padding:8px;font-variant-numeric:tabular-nums">−{{ $vat['annual']['input_display'] }}</td>
                            <td style="padding:8px;font-variant-numeric:tabular-nums">{{ $vat['annual']['net_display'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:12px;font-size:11.5px;color:var(--vy-muted);line-height:1.6;max-width:720px">
                @php
                    $vatAccommodationRate = \App\Support\I18n\LocalNumber::decimal(rtrim(rtrim(number_format((float) $vat['accommodation_rate'], 1), '0'), '.'));
                    $vatRates = $vat['service_rate'] !== null
                        ? __('livewire/owner/reports.vat_rates_both', ['accommodation_rate' => $vatAccommodationRate, 'service_rate' => \App\Support\I18n\LocalNumber::decimal(rtrim(rtrim(number_format((float) $vat['service_rate'], 1), '0'), '.'))])
                        : __('livewire/owner/reports.vat_rates_accommodation', ['accommodation_rate' => $vatAccommodationRate]);
                @endphp
                {!! __($vat['is_example'] ? 'livewire/owner/reports.vat_computed_example' : 'livewire/owner/reports.vat_computed_configured', ['country' => e((string) $taxCountry), 'rates' => e($vatRates)]) !!}
            </div>
        @else
            <div style="margin-top:14px;padding:16px 18px;border-radius:14px;background:rgba(255,255,255,.5);border:1px solid var(--vy-hairline);font-size:12.5px;color:var(--vy-muted);line-height:1.7;max-width:720px">
                @if ($vat['status'] === 'not_registered')
                    {!! __('livewire/owner/reports.vat_not_registered') !!}
                @else
                    {!! $taxCountry ? __('livewire/owner/reports.vat_not_configured_country', ['country' => e($taxCountry)]) : __('livewire/owner/reports.vat_not_configured') !!}
                @endif
            </div>
        @endif

        <div style="display:flex;align-items:center;gap:18px;margin-top:14px;flex-wrap:wrap">
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                <a href="{{ route('owner.tax.annual', (int) $activeRange['start']->year) }}" class="vy-btn vy-btn--ghost" style="padding:6px 13px;font-size:12.5px">{{ __('livewire/owner/reports.annual_tax_pdf') }}</a>
                @if ($vat['available'])
                    <a href="{{ route('owner.tax.vat-return', (int) $activeRange['start']->year) }}" class="vy-btn vy-btn--ghost" style="padding:6px 13px;font-size:12.5px">{{ __('livewire/owner/reports.vat_return_csv') }}</a>
                @endif
            @endownercan
            <a href="{{ route('owner.tax') }}" style="color:var(--vy-accent-text);font-size:12.5px;font-weight:600">{{ __('livewire/owner/reports.tax_center_link') }}</a>
        </div>
    </section>
    @endif
</x-owner.shell>
