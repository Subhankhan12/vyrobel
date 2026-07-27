{{--
    Owner Dashboard / Overview — rebuilt to the decoded `docs/design/raw/Owner Dashboard.html` wireframe (exact
    design match). GATE 1 = the visual layer, built to RECEIVE real data: the already-wired data (this-month
    earnings hero + MoM delta, total units, earned YTD, recent statements, earnings chart, next-payout amount,
    unit codes) renders in the new design; the sections whose real backends are wired in GATE 2 (occupancy KPI +
    sparkline, per-unit occupancy, the attention feed, upcoming stays, masked IBAN + paused-state) show an HONEST
    empty state in the exact design — NEVER a fabricated figure (#39). Guest names (name-only, #28) arrive with
    upcoming stays in GATE 2. Owner-scoped throughout (OwnerScope / OwnerContext, #28); integer money (#17).

    Shell chrome (peach background, ambient orbs, frosted sidebar, greeting header, account menu) is the shared
    <x-owner.shell> + layouts/owner — see those files. This view is the Overview body only.
--}}
<x-owner.shell active="overview" :title="$greeting" :title-lg="true" :viewer="$viewer">

    {{-- ── Needs your attention (GATE 1: honest "caught up"; GATE 2 wires the real tax-hold / payout-method /
         agreement-renewal signals as chips) ── --}}
    <section class="vy-ov-attn vy-fade" style="animation-delay:.02s">
        <div class="vy-ov-attn-head">
            <div class="vy-ov-attn-l">
                <span class="vy-ov-attn-icon">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>
                <div>
                    <div class="vy-ov-attn-t"><h2>Needs your attention</h2></div>
                    <div class="vy-ov-attn-sub">You're all caught up — nothing needs you right now.</div>
                </div>
            </div>
            <a class="vy-ov-attn-link" href="{{ route('owner.notifications') }}">
                Open Action center
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
            </a>
        </div>
    </section>

    {{-- ── Quick actions — real links; capability-gated controls hidden so the UI never invites a 403 (#30) ── --}}
    <div class="vy-ov-qa vy-fade" style="animation-delay:.09s">
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_CALENDAR)
            <a class="vy-ov-qa-btn" href="{{ route('owner.calendar') }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9h18"/><path d="M8 2v4"/><path d="M16 2v4"/></svg>
                View calendar
            </a>
        @endownercan
        <a class="vy-ov-qa-btn" href="{{ route('owner.messages') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.9 8.9 0 0 1-3.2-.6L3 21l1.7-5.1a8.4 8.4 0 1 1 16.3-4.4z"/></svg>
            Message guests
        </a>
        @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
            @if (! empty($statements))
                <a class="vy-ov-qa-btn" href="{{ route('owner.statements.pdf', $statements[0]['id']) }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                    Download statement
                </a>
            @endif
        @endownercan
        <a class="vy-ov-qa-btn" href="{{ route('owner.help') }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4"/><path d="M12 17h.01"/></svg>
            Contact support
        </a>
    </div>

    <div class="vy-ov-cols">
        {{-- ══ LEFT column ══ --}}
        <div class="vy-ov-stack">

            {{-- Hero — REAL this-month net + count-up + derived MoM delta (terracotta accent, 52px) --}}
            <section class="vy-card vy-fade" style="animation-delay:.12s">
                <div class="vy-eyebrow" style="margin-bottom:14px">This month @if ($hero['month_label'])· {{ $hero['month_label'] }}@endif</div>
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
                    <div class="vy-hero-sub" style="margin-top:10px">Next payout {{ $hero['next_payout_display'] }} · {{ $hero['next_payout_status']['label'] }}</div>
                @else
                    <div class="vy-hero-sub" style="margin-top:10px">No earnings yet — your first finalized statement will show here.</div>
                @endif
            </section>

            {{-- Metric row — Total units (REAL) · Occupancy (GATE 2) · Earned YTD (REAL), each a link + ↗ --}}
            <div class="vy-ov-metrics vy-fade" style="animation-delay:.18s">
                <a class="vy-ov-metric" href="{{ route('owner.units') }}">
                    <div class="vy-ov-metric-head">
                        <span class="vy-ov-metric-label">Total units</span>
                        <svg class="vy-ov-metric-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                    <div class="vy-ov-metric-val">{{ $unitsCount }}</div>
                </a>
                <a class="vy-ov-metric" href="{{ route('owner.reports') }}">
                    <div class="vy-ov-metric-head">
                        <span class="vy-ov-metric-label">Occupancy</span>
                        <svg class="vy-ov-metric-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                    {{-- GATE 2 replaces with the real owner-unit occupancy % + delta + sparkline (OwnerOccupancy). --}}
                    <div class="vy-ov-metric-val is-deferred">—</div>
                </a>
                <a class="vy-ov-metric" href="{{ route('owner.reports') }}">
                    <div class="vy-ov-metric-head">
                        <span class="vy-ov-metric-label">Earned YTD</span>
                        <svg class="vy-ov-metric-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M7 7h10v10"/></svg>
                    </div>
                    <div class="vy-ov-metric-val">{{ $metrics['earned_ytd_display'] }}</div>
                </a>
            </div>

            {{-- Recent statements — REAL, each row links to the statement screen --}}
            <section class="vy-card vy-fade" style="animation-delay:.24s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <h2 class="vy-ov-sec-title">Recent statements</h2>
                    <a href="{{ route('owner.archive') }}" style="font-size:13px;font-weight:500;color:var(--vy-accent);text-decoration:none">View all</a>
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
                    <div class="vy-empty">No statements yet.</div>
                @endforelse
            </section>

            {{-- Earnings chart — REAL monthly series (finalized statements), segmented 3M/6M/12M toggle, hover
                 tooltip. No goal line (no goal backend — #39). --}}
            <section class="vy-card vy-fade" style="animation-delay:.30s" x-data="{ range: 6 }">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
                    <h2 class="vy-ov-sec-title">Earnings</h2>
                    @if ($series['count'] > 0)
                        <div class="vy-ov-seg">
                            @foreach ([3 => '3M', 6 => '6M', 12 => '12M'] as $n => $label)
                                <button type="button" class="vy-ov-seg-btn" :class="{ 'is-active': range === {{ $n }} }" @click="range = {{ $n }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($series['count'] > 0)
                    <div class="vy-ov-chart">
                        @foreach ($series['bars'] as $i => $bar)
                            <a class="vy-ov-barcol" href="{{ route('owner.archive') }}" x-show="{{ $i }} >= {{ $series['count'] }} - range">
                                <div class="vy-ov-tip">
                                    <div class="vy-ov-tip-inner">
                                        <div class="vy-ov-tip-m">{{ $bar['label'] }}</div>
                                        <div class="vy-ov-tip-v">{{ $bar['net_display'] }}</div>
                                    </div>
                                </div>
                                <div class="vy-ov-bar {{ $i === $series['count'] - 1 ? 'is-last' : '' }}" style="height:{{ max(4, $bar['pct']) }}%"></div>
                                <div class="vy-ov-bar-month">{{ $bar['label'] }}</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="vy-deferred">No earnings yet — your first finalized statement will appear here.</div>
                @endif
            </section>
        </div>

        {{-- ══ RIGHT column ══ --}}
        <div class="vy-ov-stack">

            {{-- Next payout — REAL amount; halo glow + dark panel. GATE 2 adds the masked IBAN + paused/resume. --}}
            <div class="vy-ov-payout vy-fade" style="animation-delay:.16s">
                <div class="vy-ov-payout-halo"></div>
                <section class="vy-card vy-card--dark">
                    <span class="vy-eyebrow">Next payout</span>
                    <div class="vy-dark-figure">{{ $hero['next_payout_display'] }}</div>
                    <span class="vy-status vy-status--{{ $hero['next_payout_status']['tone'] }}" style="margin-top:6px"><span class="vy-dot"></span>{{ $hero['next_payout_status']['label'] }}</span>
                    <div style="margin-top:14px;font-size:12px;color:rgba(255,255,255,.6);line-height:1.6">Your payout account will show here once it's confirmed.</div>
                </section>
            </div>

            {{-- My units — REAL codes. GATE 2 fills each unit's real occupancy % + bar (OwnerOccupancy). --}}
            <section class="vy-card vy-fade" style="animation-delay:.22s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
                    <span class="vy-eyebrow">My units</span>
                    <span class="vy-soon-tag">Occupancy soon</span>
                </div>
                <div class="vy-ov-units">
                    @forelse ($units as $u)
                        <div>
                            <div class="vy-ov-unit-head">
                                <span class="vy-ov-unit-name">{{ $u['code'] }}</span>
                                <span class="vy-ov-unit-pct">—</span>
                            </div>
                            <div class="vy-track"><span style="width:0%"></span></div>
                        </div>
                    @empty
                        <div class="vy-empty">No units yet.</div>
                    @endforelse
                </div>
            </section>

            {{-- Upcoming stays — GATE 2 wires the real next-30-days bookings (guest name-only #28 + owner-net
                 value + status + date) and the working Source/Status filters. --}}
            <section class="vy-card vy-fade" style="animation-delay:.28s">
                <div class="vy-ov-stays-head">
                    <div>
                        <div class="vy-eyebrow">Upcoming stays</div>
                        <div style="font-size:11px;color:var(--vy-faint);margin-top:3px">Next 30 days</div>
                    </div>
                </div>
                <div class="vy-deferred">
                    <span class="vy-eyebrow">Not yet available</span>
                    Your upcoming arrivals will appear here.
                </div>
            </section>

            {{-- Download statements (bottom of the rail) — the full statement archive --}}
            @ownercan(\App\Support\Owner\OwnerTeamRole::CAP_EXPORTS)
                <a class="vy-ov-dl vy-fade" style="animation-delay:.34s" href="{{ route('owner.archive') }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7 11l5 5 5-5"/><path d="M4 20h16"/></svg>
                    Download statements
                </a>
            @endownercan
        </div>
    </div>
</x-owner.shell>
