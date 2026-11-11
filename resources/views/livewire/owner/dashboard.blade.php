{{--
    Owner Dashboard / Overview — rebuilt to the decoded `docs/design/raw/Owner Dashboard.html` wireframe (exact
    design match) AND wired to LIVE, owner-scoped data end-to-end. Every figure is a REAL owner aggregate (#39):
    the this-month earnings hero + MoM delta, total units, earned YTD, recent statements, the earnings chart, the
    OCCUPANCY KPI + per-unit occupancy (OwnerOccupancy), the attention feed (OwnerAttention), the next-30-days
    upcoming stays (OwnerUpcomingStays — guest NAME only, #28) with client-side Source/Status filters, and the
    masked payout IBAN + tax-hold paused-state. Nothing is fabricated — no earnings goal line, no estimated
    payout date, no invented occupancy; thin/empty data shows an honest empty state. Owner-scoped throughout
    (OwnerScope / OwnerContext, #28); integer money (#17). See App\Livewire\Owner\OwnerDashboard.

    Shell chrome (peach background, ambient orbs, frosted sidebar, greeting header, account menu) is the shared
    <x-owner.shell> + layouts/owner — see those files. This view is the Overview body only.
--}}
<x-owner.shell active="overview" :title="$greeting" :title-lg="true" :viewer="$viewer">

    {{-- ── Needs your attention — REAL items via the shared OwnerAttention feed (tax-hold / payout-method /
         agreement-renewal); empty → an honest "caught up". Never a fabricated signal (#39). ── --}}
    @php
        $attnCount = count($attention);
        $attnSub = $attnCount > 0
            ? trans_choice('livewire/owner/dashboard.attention_count', $attnCount, ['count' => $attnCount])
            : __('livewire/owner/dashboard.all_caught_up');
    @endphp
    <section class="vy-ov-attn vy-fade" style="animation-delay:.02s">
        <div class="vy-ov-attn-head @if ($attnCount > 0) has-chips @endif">
            <div class="vy-ov-attn-l">
                <span class="vy-ov-attn-icon">
                    @if ($attnCount > 0)
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                    @else
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    @endif
                </span>
                <div>
                    <div class="vy-ov-attn-t">
                        <h2>{{ __('livewire/owner/dashboard.needs_attention') }}</h2>
                        @if ($attnCount > 0)<span class="vy-ov-attn-count">{{ $attnCount }}</span>@endif
                    </div>
                    <div class="vy-ov-attn-sub">{{ $attnSub }}</div>
                </div>
            </div>
            <a class="vy-ov-attn-link" href="{{ route('owner.notifications') }}">
                {{ __('livewire/owner/dashboard.open_action_center') }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        </div>
        @if ($attnCount > 0)
            {{-- The wireframe shows at most THREE chips and counts the rest into a dashed "+N more"
                 that opens the action center — the row was rendering every item, so a busy owner got a
                 wrapped wall of chips instead of the design's single band. Presentation only: nothing
                 is hidden from the owner, the overflow is counted and linked. --}}
            <div class="vy-ov-chips">
                @foreach (array_slice($attention, 0, 3) as $item)
                    @php $icon = $item['tone'] === 'warn'
                        ? '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>'
                        : '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/>'; @endphp
                    @if (! empty($item['actions']))
                        {{-- Wave C — a rate-proposal decision surfaces here as a REAL chip with Approve/Decline (POST), CAP_ACTIONS. --}}
                        <div class="vy-ov-chip {{ $item['tone'] === 'warn' ? 'is-warn' : 'is-info' }}" style="cursor:default">
                            <span class="vy-ov-chip-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></span>
                            <span class="vy-ov-chip-main">
                                <span class="vy-ov-chip-title">{{ $item['title'] }}</span>
                                <span class="vy-ov-chip-sub">{{ $item['body'] }}</span>
                            </span>
                            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_ACTIONS)
                                <span style="display:flex;gap:6px;flex-shrink:0">
                                    @foreach ($item['actions'] as $action)
                                        <form method="POST" action="{{ $action['route'] }}" style="margin:0">
                                            @csrf
                                            <button type="submit" class="{{ $action['primary'] ? 'vy-btn vy-btn--accent' : 'vy-btn vy-btn--ghost' }}" style="padding:5px 11px;font-size:11.5px">{{ $action['label'] }}</button>
                                        </form>
                                    @endforeach
                                </span>
                            @endownercan
                        </div>
                    @else
                        <a class="vy-ov-chip {{ $item['tone'] === 'warn' ? 'is-warn' : 'is-info' }}" href="{{ $item['cta']['route'] }}">
                            <span class="vy-ov-chip-ic"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg></span>
                            <span class="vy-ov-chip-main">
                                <span class="vy-ov-chip-title">{{ $item['title'] }}</span>
                                <span class="vy-ov-chip-sub">{{ $item['body'] }}</span>
                            </span>
                            <span class="vy-ov-chip-tag">{{ $item['tone'] === 'warn' ? __('livewire/owner/dashboard.chip_action') : __('livewire/owner/dashboard.chip_review') }}</span>
                        </a>
                    @endif
                @endforeach
                @if ($attnCount > 3)
                    <a class="vy-ov-chip-more" href="{{ route('owner.notifications') }}">{{ __('livewire/owner/dashboard.more_count', ['number' => $attnCount - 3]) }}</a>
                @endif
            </div>
        @endif
    </section>

    {{-- ── Quick actions — real links; capability-gated controls hidden so the UI never invites a 403 (#30) ── --}}
    <div class="vy-ov-qa vy-fade" style="animation-delay:.09s">
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_CALENDAR)
            <a class="vy-ov-qa-btn" href="{{ route('owner.calendar') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                {{ __('livewire/owner/dashboard.view_calendar') }}
            </a>
        @endownercan
        <a class="vy-ov-qa-btn" href="{{ route('owner.messages') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-3.2-.6L3 21l1.7-5.1a8.4 8.4 0 1 1 16.3-4.4z"/></svg>
            {{ __('livewire/owner/dashboard.message_guests') }}
        </a>
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
            @if (! empty($statements))
                <a class="vy-ov-qa-btn" href="{{ route('owner.statements.pdf', $statements[0]['id']) }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                    {{ __('livewire/owner/dashboard.download_statement') }}
                </a>
            @endif
        @endownercan
        <a class="vy-ov-qa-btn" href="{{ route('owner.help') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4"/><path d="M12 17h.01"/></svg>
            {{ __('livewire/owner/dashboard.contact_support') }}
        </a>
    </div>

    <div class="vy-ov-cols">
        {{-- ══ LEFT column ══ --}}
        <div class="vy-ov-stack">

            {{-- Hero — REAL this-month net + count-up + derived MoM delta (terracotta accent, 52px) --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <div class="vy-eyebrow" style="margin-bottom:14px">{{ $hero['month_label'] ? __('livewire/owner/dashboard.this_month_label', ['month' => $hero['month_label']]) : __('livewire/owner/dashboard.this_month') }}</div>
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    @if ($hero['has_data'])
                        <div class="vy-hero-figure" style="font-size:52px;margin:0;font-variant-numeric:tabular-nums" data-countup="{{ $hero['this_month_minor'] }}" data-symbol="{{ $hero['currency_symbol'] }}">{{ $hero['this_month_display'] }}</div>
                        @if ($hero['delta'])
                            <span class="vy-delta {{ $hero['delta']['positive'] ? 'vy-delta--up' : 'vy-delta--down' }}">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $hero['delta']['positive'] ? 'M6 15l6-6 6 6' : 'M6 9l6 6 6-6' }}"/></svg>
                                {{ $hero['delta']['label'] }}
                            </span>
                        @endif
                    @else
                        <div class="vy-hero-figure" style="font-size:52px;margin:0;color:var(--vy-faint)">—</div>
                    @endif
                </div>
                @if ($hero['has_data'])
                    <div class="vy-hero-sub" style="margin-top:10px">{{ __('livewire/owner/dashboard.next_payout_line', ['amount' => $hero['next_payout_display'], 'status' => $hero['next_payout_status']['label']]) }}</div>
                @else
                    <div class="vy-hero-sub" style="margin-top:10px">{{ __('livewire/owner/dashboard.no_earnings_hero') }}</div>
                @endif
            </section>

            {{-- Metric row — Total units (REAL) · Occupancy (GATE 2) · Earned YTD (REAL), each a link + ↗ --}}
            <div class="vy-ov-metrics vy-fade" style="animation-delay:.18s">
                <a class="vy-ov-metric" href="{{ route('owner.units') }}">
                    <div class="vy-ov-metric-head">
                        <span class="vy-ov-metric-label">{{ __('livewire/owner/dashboard.total_units') }}</span>
                        <svg class="vy-ov-metric-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                    <div class="vy-ov-metric-val">{{ $unitsCount }}</div>
                </a>
                <a class="vy-ov-metric" href="{{ route('owner.reports') }}">
                    <div class="vy-ov-metric-head">
                        <span class="vy-ov-metric-label">{{ __('livewire/owner/dashboard.occupancy') }}</span>
                        <svg class="vy-ov-metric-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                    {{-- REAL owner-unit occupancy for the current month (OwnerOccupancy) + MoM delta + trend. --}}
                    @if ($occupancy['has_data'])
                        <div class="vy-ov-occ">
                            <div class="vy-ov-occ-fig">
                                <span class="vy-ov-metric-val">{{ $occupancy['pct_display'] }}</span>
                                @if ($occupancy['delta'])
                                    <span class="vy-ov-occ-delta" @unless ($occupancy['delta']['positive']) style="color:var(--vy-muted)" @endunless>{{ $occupancy['delta']['label'] }}</span>
                                @endif
                            </div>
                            @if ($occupancy['spark']['has'])
                                <svg width="94" height="32" viewBox="0 0 100 34" fill="none" style="flex-shrink:0;overflow:visible">
                                    <path d="{{ $occupancy['spark']['area'] }}" fill="rgba(232,112,58,0.13)"></path>
                                    <path d="{{ $occupancy['spark']['line'] }}" stroke="#E8703A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                    <circle cx="{{ $occupancy['spark']['last_x'] }}" cy="{{ $occupancy['spark']['last_y'] }}" r="2.6" fill="#C2410C" stroke="#FFF8F1" stroke-width="1.5"></circle>
                                </svg>
                            @endif
                        </div>
                    @else
                        <div class="vy-ov-metric-val">{{ $occupancy['pct_display'] }}</div>
                    @endif
                </a>
                <a class="vy-ov-metric" href="{{ route('owner.reports') }}">
                    <div class="vy-ov-metric-head">
                        <span class="vy-ov-metric-label">{{ __('livewire/owner/dashboard.earned_ytd') }}</span>
                        <svg class="vy-ov-metric-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                    <div class="vy-ov-metric-val">{{ $metrics['earned_ytd_display'] }}</div>
                </a>
            </div>

            {{-- Recent statements — REAL, each row links to the statement screen --}}
            <section class="vy-card vy-fade" style="animation-delay:.24s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <h2 class="vy-ov-sec-title">{{ __('livewire/owner/dashboard.recent_statements') }}</h2>
                    <a href="{{ route('owner.archive') }}" style="font-size:13px;font-weight:500;color:var(--vy-accent-text);text-decoration:none">{{ __('livewire/owner/dashboard.view_all') }}</a>
                </div>
                @forelse ($statements as $s)
                    <a class="vy-list-row vy-row-link" href="{{ route('owner.statements.show', $s['id']) }}">
                        <div>
                            <div class="vy-row-month" style="font-weight:500;font-size:14px">{{ $s['month_label'] }}</div>
                            <div class="vy-line-sub">{{ $s['period_range'] }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:14px">
                            <span class="vy-status vy-status--{{ $s['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $s['status_meta']['label'] }}</span>
                            <span style="font-family:var(--vy-display);font-weight:500;font-size:14.5px;font-variant-numeric:tabular-nums">{{ $s['net_display'] }}</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--vy-faint)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </div>
                    </a>
                @empty
                    <div class="vy-empty">{{ __('livewire/owner/dashboard.no_statements') }}</div>
                @endforelse
            </section>

            {{-- Earnings chart — REAL monthly series (finalized statements), segmented 3M/6M/12M toggle, hover
                 tooltip. The goal line is the owner's REAL monthly target (OwnerMonthlyTarget, #56/GATE 4),
                 shown only when set — never a fabricated goal (#39). --}}
            <section class="vy-card vy-fade" style="animation-delay:.30s" x-data="{ range: 6 }">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
                    <h2 class="vy-ov-sec-title">{{ __('livewire/owner/dashboard.earnings') }}</h2>
                    @if ($series['count'] > 0)
                        <div class="vy-ov-seg">
                            @foreach ([3 => __('livewire/owner/dashboard.range_3m'), 6 => __('livewire/owner/dashboard.range_6m'), 12 => __('livewire/owner/dashboard.range_12m')] as $n => $label)
                                <button type="button" class="vy-ov-seg-btn" :class="{ 'is-active': range === {{ $n }} }" @click="range = {{ $n }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($series['count'] > 0)
                    @php
                        // Presentation geometry ONLY — no figure is recomputed here. Both numbers are declared
                        // on .vy-ov-chart and are documented there: 0.78 is the wireframe's headroom (and what
                        // keeps the bars linear), 16 is the month-label block the bars sit above, so the goal
                        // line lands on the SAME baseline as the bars it is compared against.
                        $barScale = 0.78;
                        $labelBlock = 16;
                    @endphp
                    <div class="vy-ov-chart">
                        {{-- REAL monthly-target goal line (GATE 4) — same OwnerMonthlyTarget the Bookings ring reads (#56);
                             shown only when the owner has set a target, never a fabricated goal (#39). --}}
                        @if ($series['goal'])
                            <div class="vy-ov-goal" title="{{ __('livewire/owner/dashboard.goal_title', ['amount' => $series['goal']['display']]) }}"
                                 style="bottom:{{ round($labelBlock + min(99, max(1, $series['goal']['pct'])) * $barScale, 2) }}%">
                                {{-- ⚠️ NOT `range@endif` — a directive whose `@` follows a word character is not
                                     compiled (the CLAUDE.md `\B` rule); it cost a 500 here. Expression, no directive. --}}
                                <span class="vy-ov-goal-tag">{{ __($series['goal']['above_range'] ? 'livewire/owner/dashboard.goal_tag_above' : 'livewire/owner/dashboard.goal_tag', ['amount' => $series['goal']['display']]) }}</span>
                            </div>
                        @endif
                        @foreach ($series['bars'] as $i => $bar)
                            <a class="vy-ov-barcol" href="{{ route('owner.archive') }}" x-show="{{ $i }} >= {{ $series['count'] }} - range">
                                <div class="vy-ov-tip">
                                    <div class="vy-ov-tip-inner">
                                        <div class="vy-ov-tip-m">{{ $bar['label'] }}</div>
                                        <div class="vy-ov-tip-v">{{ $bar['net_display'] }}</div>
                                    </div>
                                </div>
                                {{-- A genuinely ZERO month draws NOTHING. This chart still carried the old
                                     `max(4, …)` floor, which puts a visible stub under a €0.00 month and
                                     reads as a small real amount (#39) — the same defect the shared
                                     <x-charts.bars> already fixed; its rule is adopted here verbatim. --}}
                                <div class="vy-ov-bar {{ $i === $series['count'] - 1 ? 'is-last' : '' }}"
                                     style="height:{{ round($bar['pct'] * $barScale, 2) }}%{{ $bar['pct'] > 0 ? ';min-height:2px' : '' }};--vy-bar-a:{{ number_format(0.30 + ($bar['pct'] / 100) * 0.25, 2) }}"></div>
                                <div class="vy-ov-bar-month">{{ $bar['label'] }}</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="vy-deferred">{{ __('livewire/owner/dashboard.no_earnings_chart') }}</div>
                @endif
            </section>
        </div>

        {{-- ══ RIGHT column ══ --}}
        <div class="vy-ov-stack">

            {{-- Next payout — REAL amount + masked IBAN (payout-capable role only) + the tax-hold paused-state;
                 halo glow + dark panel. No estimated date (no scheduled-date field — #39). --}}
            <div class="vy-ov-payout vy-fade" style="animation-delay:.16s">
                <div class="vy-ov-payout-halo"></div>
                <section class="vy-card vy-card--dark">
                    <span class="vy-eyebrow">{{ __('livewire/owner/dashboard.next_payout') }}</span>
                    @if ($payout['paused'])
                        <div class="vy-ov-pause-row" style="margin-top:12px">
                            <span class="vy-ov-pause-ic">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                            </span>
                            <span class="vy-ov-pause-fig">{{ __('livewire/owner/dashboard.paused') }}</span>
                        </div>
                        <div style="font-size:12.5px;color:rgba(255,255,255,.6);margin-top:11px;line-height:1.5">{{ __('livewire/owner/dashboard.paused_body') }}</div>
                        <a href="{{ route('owner.tax') }}" class="vy-ov-resume">
                            {{ __('livewire/owner/dashboard.add_tax_details') }}
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </a>
                    @else
                        <div class="vy-dark-figure">{{ $hero['next_payout_display'] }}</div>
                        <span class="vy-status vy-status--{{ $hero['next_payout_status']['tone'] }}" style="margin-top:6px"><span class="vy-dot"></span>{{ $hero['next_payout_status']['label'] }}</span>
                        @if ($payout['iban_masked'])
                            <div class="vy-ov-iban">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 4l9 5.5"/><path d="M5 10v8"/><path d="M9.5 10v8"/><path d="M14.5 10v8"/><path d="M19 10v8"/><path d="M3 19.5h18"/></svg>
                                {{ __('livewire/owner/dashboard.iban', ['iban' => $payout['iban_masked']]) }}
                            </div>
                        @endif
                    @endif
                </section>
            </div>

            {{-- My units — REAL codes + each unit's REAL current-month occupancy % (owner-scoped OwnerOccupancy). --}}
            <section class="vy-card vy-fade" style="animation-delay:.22s">
                <div style="margin-bottom:14px">
                    <span class="vy-eyebrow">{{ __('livewire/owner/dashboard.my_units') }}</span>
                </div>
                <div class="vy-ov-units">
                    @forelse ($units as $u)
                        <div>
                            <div class="vy-ov-unit-head">
                                <span class="vy-ov-unit-name">{{ $u['code'] }}</span>
                                <span class="vy-ov-unit-pct">{{ $u['occupancy_pct'] }}%</span>
                            </div>
                            <div class="vy-track"><span style="width:{{ $u['occupancy_pct'] }}%"></span></div>
                        </div>
                    @empty
                        <div class="vy-empty">{{ __('livewire/owner/dashboard.no_units') }}</div>
                    @endforelse
                </div>
            </section>

            {{-- Upcoming stays — REAL next-30-days arrivals on the owner's units (OwnerUpcomingStays): guest
                 NAME only (#28) + owner NET + real status/source/date. Filters + sort are CLIENT-SIDE (Alpine)
                 over the server-rendered real rows — a Livewire round-trip would bypass the owner scope. --}}
            @if ($upcoming['count'] > 0)
                <section class="vy-card vy-fade" style="animation-delay:.28s"
                    x-data="{
                        src: 'all', status: 'all', sortDesc: false, fOpen: false,
                        rows: [@foreach ($upcoming['rows'] as $r){s:'{{ $r['source_key'] }}',st:'{{ $r['status_key'] }}'},@endforeach],
                        match(r){ return (this.src==='all'||this.src===r.s) && (this.status==='all'||this.status===r.st); },
                        get anyVisible(){ return this.rows.some(r => this.match(r)); },
                        get activeCount(){ return (this.src!=='all'?1:0)+(this.status!=='all'?1:0); }
                    }"
                    @keydown.escape.window="fOpen = false">
                    <div class="vy-ov-stays-head">
                        <div>
                            <div class="vy-eyebrow">{{ __('livewire/owner/dashboard.upcoming_stays') }}</div>
                            <div style="font-size:11px;color:var(--vy-faint);margin-top:3px">{{ __('livewire/owner/dashboard.next_30_days') }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                            <button type="button" class="vy-ov-ctrl" @click="sortDesc = !sortDesc" :title="sortDesc ? @js(__('livewire/owner/dashboard.latest_first')) : @js(__('livewire/owner/dashboard.earliest_first'))" :aria-label="sortDesc ? @js(__('livewire/owner/dashboard.latest_first')) : @js(__('livewire/owner/dashboard.earliest_first'))">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" :style="sortDesc ? 'transform:rotate(180deg)' : ''"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>
                                <span x-text="sortDesc ? @js(__('livewire/owner/dashboard.sort_latest')) : @js(__('livewire/owner/dashboard.sort_earliest'))"></span>
                            </button>
                            <div style="position:relative">
                                <button type="button" class="vy-ov-ctrl" @click="fOpen = !fOpen" :aria-expanded="fOpen ? 'true' : 'false'">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                                    {{ __('common.filter') }}
                                    <span x-show="activeCount > 0" x-cloak class="vy-ov-ctrl-count" x-text="activeCount"></span>
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <div x-show="fOpen" x-cloak @click.outside="fOpen = false" class="vy-ov-menu">
                                    <div class="vy-ov-menu-h">{{ __('livewire/owner/dashboard.source') }}</div>
                                    <button type="button" class="vy-ov-menu-item" @click="src = 'all'; fOpen = false">
                                        <span class="vy-ov-menu-dot" style="background:var(--vy-faint)"></span>
                                        <span style="flex:1;text-align:left;white-space:nowrap">{{ __('livewire/owner/dashboard.all_sources') }}</span>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" x-show="src === 'all'"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                    @foreach ($upcoming['sources'] as $o)
                                        <button type="button" class="vy-ov-menu-item" @click="src = '{{ $o['key'] }}'; fOpen = false">
                                            <span class="vy-ov-menu-dot" style="background:{{ $o['dot'] }}"></span>
                                            <span style="flex:1;text-align:left;white-space:nowrap">{{ $o['label'] }}</span>
                                            <span style="font-size:10.5px;color:var(--vy-faint)">{{ $o['count'] }}</span>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" x-show="src === '{{ $o['key'] }}'"><path d="M20 6 9 17l-5-5"/></svg>
                                        </button>
                                    @endforeach
                                    <div class="vy-ov-menu-sep"></div>
                                    <div class="vy-ov-menu-h">{{ __('common.status') }}</div>
                                    <button type="button" class="vy-ov-menu-item" @click="status = 'all'; fOpen = false">
                                        <span class="vy-ov-menu-dot" style="background:var(--vy-faint)"></span>
                                        <span style="flex:1;text-align:left;white-space:nowrap">{{ __('livewire/owner/dashboard.all_statuses') }}</span>
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" x-show="status === 'all'"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                    @foreach ($upcoming['statuses'] as $o)
                                        <button type="button" class="vy-ov-menu-item" @click="status = '{{ $o['key'] }}'; fOpen = false">
                                            <span class="vy-ov-menu-dot" style="background:{{ $o['key'] === 'confirmed' ? 'var(--vy-green)' : 'var(--vy-amber-2)' }}"></span>
                                            <span style="flex:1;text-align:left;white-space:nowrap">{{ $o['label'] }}</span>
                                            <span style="font-size:10.5px;color:var(--vy-faint)">{{ $o['count'] }}</span>
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--vy-accent)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" x-show="status === '{{ $o['key'] }}'"><path d="M20 6 9 17l-5-5"/></svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="vy-ov-stays" :style="sortDesc ? 'flex-direction:column-reverse' : ''">
                        @foreach ($upcoming['rows'] as $i => $r)
                            @php $confirmed = $r['status_tone'] === 'confirmed'; @endphp
                            <a href="{{ $r['href'] }}" class="vy-ov-stay" x-show="match(rows[{{ $i }}])" x-cloak>
                                <div class="vy-ov-stay-top">
                                    <span class="vy-ov-stay-guest">
                                        <span class="vy-ov-stay-dot" style="background:{{ $r['source_dot'] }}"></span>
                                        <span class="vy-ov-stay-name">{{ $r['guest'] ?? __('livewire/owner/dashboard.guest_fallback') }}</span>
                                    </span>
                                    <span class="vy-ov-stay-val">{{ $r['value_display'] }}</span>
                                </div>
                                <div class="vy-ov-stay-bot">
                                    <span class="vy-ov-stay-detail">{{ $r['detail'] }}</span>
                                    <span class="vy-ov-stay-meta">
                                        <span class="vy-ov-stay-status" style="background:{{ $confirmed ? 'rgba(31,122,90,.13)' : 'rgba(224,146,30,.14)' }};color:{{ $confirmed ? 'var(--vy-green-text)' : 'var(--vy-amber-text)' }}">
                                            <span style="width:5px;height:5px;border-radius:50%;background:{{ $confirmed ? 'var(--vy-green)' : 'var(--vy-amber-2)' }}"></span>{{ $r['status_label'] }}
                                        </span>
                                        <span class="vy-ov-stay-date">{{ $r['date'] }}</span>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                        <div x-show="!anyVisible" x-cloak class="vy-ov-nomatch">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#C4B8AF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                            <div style="font-size:12.5px;color:var(--vy-muted)">{{ __('livewire/owner/dashboard.no_stays_match') }}</div>
                            <button type="button" @click="src = 'all'; status = 'all'" style="margin-top:2px;padding:5px 12px;border-radius:8px;border:1px solid rgba(120,70,40,.16);background:rgba(255,252,248,.6);color:var(--vy-accent);font-family:var(--vy-sans);font-size:11.5px;font-weight:600;cursor:pointer">{{ __('livewire/owner/dashboard.clear_filters') }}</button>
                        </div>
                    </div>
                    <div class="vy-ov-stays-foot">
                        <span class="vy-ov-stays-foot-l">{{ __('livewire/owner/dashboard.net_to_you', ['amount' => $upcoming['net_total_display']]) }}</span>
                        <a class="vy-ov-viewall" href="{{ route('owner.bookings') }}">{{ __('livewire/owner/dashboard.view_all_bookings') }}<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
                    </div>
                </section>
            @else
                <section class="vy-card vy-fade" style="animation-delay:.28s">
                    <div class="vy-ov-stays-head">
                        <div>
                            <div class="vy-eyebrow">{{ __('livewire/owner/dashboard.upcoming_stays') }}</div>
                            <div style="font-size:11px;color:var(--vy-faint);margin-top:3px">{{ __('livewire/owner/dashboard.next_30_days') }}</div>
                        </div>
                    </div>
                    <div class="vy-deferred">
                        <span class="vy-eyebrow">{{ __('livewire/owner/dashboard.no_upcoming_stays') }}</span>
                        {{ __('livewire/owner/dashboard.no_arrivals') }}
                    </div>
                </section>
            @endif

            {{-- Download statements (bottom of the rail) — the full statement archive --}}
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                <a class="vy-ov-dl vy-fade" style="animation-delay:.34s" href="{{ route('owner.archive') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 20h16"/></svg>
                    {{ __('livewire/owner/dashboard.download_statements') }}
                </a>
            @endownercan
        </div>
    </div>
</x-owner.shell>
