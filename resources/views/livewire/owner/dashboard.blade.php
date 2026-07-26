{{--
    Owner Dashboard (design pass · Screen 2) — the owner-portal overview, styled from the catalogued
    "Owner Dashboard" design and wired to LIVE owner-scoped data. Shell from <x-owner.shell>.

    HONESTY MAP (rule locked in DECISIONS.md 2026-07-11; see App\Livewire\Owner\OwnerDashboard):
      • REAL      — unit count + list (codes), recent statements (→ statement screen), this-month
                    earnings hero, next-payout amount, YTD-earned + paid-to-date totals.
      • DERIVED   — MoM earnings delta + the monthly earnings chart, from the owner's REAL statements.
      • DEFERRED  — occupancy KPI + per-unit occupancy (availabilityRange is room-type-wide, not owner-
                    specific): visually stubbed + "not yet available".
      • NO BACKEND— action centre, quick actions, statement downloads: disabled "Soon" affordances.
      • Guest PII — upcoming-stays guest names are NOT exposed.
--}}
<x-owner.shell active="overview" title="Overview" :viewer="$viewer">
    {{-- Action centre — NO BACKEND (no approvals/tasks/notifications engine): disabled, never faked --}}
    <section class="vy-card vy-card--accent" style="margin-bottom:22px;display:flex;justify-content:space-between;align-items:center;gap:16px">
        <div style="display:flex;align-items:center;gap:12px">
            <span class="vy-brand-mark" style="border-radius:12px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
            </span>
            <div>
                <div style="font-family:var(--vy-display);font-weight:600;font-size:14.5px">Action centre</div>
                <div class="vy-line-sub">Approvals, tasks and alerts will appear here.</div>
            </div>
        </div>
        <span class="vy-soon-tag">Soon</span>
    </section>

    {{-- Quick actions — NO BACKEND: disabled affordances, never a fake action --}}
    <div class="vy-quick" style="margin-bottom:22px">
        <span class="vy-eyebrow" style="margin-right:4px">Quick actions</span>
        @foreach (['View calendar', 'Message guests', 'Download statement', 'Contact support'] as $qa)
            <button type="button" class="vy-pill is-soon" style="opacity:.55;cursor:not-allowed" disabled aria-disabled="true" title="Coming soon">{{ $qa }}</button>
        @endforeach
        <span class="vy-soon-tag">Soon</span>
    </div>

    <div class="vy-cols">
        {{-- ── Main feed ── --}}
        <div class="vy-stack">
            {{-- Hero earnings (REAL this-month net + count-up + derived MoM delta) --}}
            <section class="vy-card vy-card--accent vy-fade" style="animation-delay:.02s">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px">
                    <span class="vy-eyebrow">THIS MONTH @if ($hero['month_label'])· {{ $hero['month_label'] }}@endif</span>
                    @if ($hero['delta'])
                        <span class="vy-delta {{ $hero['delta']['positive'] ? 'vy-delta--up' : 'vy-delta--down' }}">{{ $hero['delta']['label'] }}</span>
                    @endif
                </div>
                @if ($hero['has_data'])
                    <div class="vy-hero-figure" style="font-size:44px" data-countup="{{ $hero['this_month_minor'] }}" data-symbol="{{ $hero['currency_symbol'] }}">{{ $hero['this_month_display'] }}</div>
                    <div class="vy-hero-sub">Next payout {{ $hero['next_payout_display'] }} · {{ $hero['next_payout_status']['label'] }}</div>
                @else
                    <div class="vy-hero-figure" style="font-size:44px;color:var(--vy-faint)">—</div>
                    <div class="vy-hero-sub">No earnings yet — your first finalized statement will show here.</div>
                @endif
            </section>

            {{-- KPI tiles: UNITS (real) · OCCUPANCY (deferred) · EARNED YTD (real) --}}
            <div class="vy-kpi-grid vy-fade" style="animation-delay:.06s">
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Total units</div>
                    <div class="vy-kpi-val">{{ $unitsCount }}</div>
                    <div class="vy-kpi-sub">In your portfolio</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Occupancy</div>
                    <div class="vy-kpi-val is-deferred">—</div>
                    <div class="vy-kpi-sub">Not yet available</div>
                </div>
                <div class="vy-kpi">
                    <div class="vy-kpi-label">Earned YTD</div>
                    <div class="vy-kpi-val">{{ $metrics['earned_ytd_display'] }}</div>
                    <div class="vy-kpi-sub">@if ($metrics['year']){{ $metrics['year'] }} · paid {{ $metrics['paid_display'] }}@else Paid {{ $metrics['paid_display'] }}@endif</div>
                </div>
            </div>

            {{-- Earnings chart — REAL monthly series from finalized statements, 3M/6M/12M range toggle --}}
            <section class="vy-card vy-fade" style="animation-delay:.1s" x-data="{ range: 6 }">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px">
                    <h2 class="vy-card-title">Earnings</h2>
                    @if ($series['count'] > 0)
                        <div style="display:flex;gap:6px">
                            @foreach ([3 => '3M', 6 => '6M', 12 => '12M'] as $n => $label)
                                <button type="button" class="vy-pill" :class="{ 'is-active': range === {{ $n }} }" @click="range = {{ $n }}">{{ $label }}</button>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($series['count'] > 0)
                    <div class="vy-chart">
                        @foreach ($series['bars'] as $i => $bar)
                            <div class="vy-chart-col" x-show="{{ $i }} >= {{ $series['count'] }} - range" title="{{ $bar['net_display'] }}">
                                <div class="vy-bar {{ $i === $series['count'] - 1 ? 'is-last' : '' }}" style="height:{{ max(4, $bar['pct']) }}%"></div>
                                <div class="vy-bar-label">{{ $bar['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="vy-deferred">No earnings yet — your first finalized statement will appear here.</div>
                @endif
            </section>

            {{-- Recent statements — REAL, each row links to the styled statement screen --}}
            <section class="vy-card vy-fade" style="animation-delay:.14s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                    <h2 class="vy-card-title">Recent statements</h2>
                    <span class="vy-soon-tag">View all · Soon</span>
                </div>
                @forelse ($statements as $s)
                    <a class="vy-list-row vy-row-link" href="{{ route('owner.statements.show', $s['id']) }}">
                        <div>
                            <div class="vy-row-month" style="font-weight:600;font-size:13.5px">{{ $s['month_label'] }}</div>
                            <div class="vy-line-sub">{{ $s['period_range'] }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:14px">
                            <span class="vy-status vy-status--{{ $s['status_meta']['tone'] }}"><span class="vy-dot"></span>{{ $s['status_meta']['label'] }}</span>
                            <span class="vy-line-amt vy-net" style="color:var(--vy-accent)">{{ $s['net_display'] }}</span>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--vy-faint)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </div>
                    </a>
                @empty
                    <div class="vy-empty">No statements yet.</div>
                @endforelse
            </section>
        </div>

        {{-- ── Right rail ── --}}
        <div class="vy-stack">
            {{-- Next payout — REAL amount + status; no IBAN / date (not backed) --}}
            <section class="vy-card vy-card--dark vy-fade" style="animation-delay:.05s">
                <span class="vy-eyebrow">Next payout</span>
                <div class="vy-dark-figure">{{ $hero['next_payout_display'] }}</div>
                <span class="vy-status vy-status--{{ $hero['next_payout_status']['tone'] }}" style="margin-top:6px"><span class="vy-dot"></span>{{ $hero['next_payout_status']['label'] }}</span>
                <div style="margin-top:14px;font-size:12px;color:rgba(255,255,255,.6);line-height:1.6">Payout method setup is coming soon.</div>
            </section>

            {{-- My units — REAL codes; per-unit occupancy DEFERRED (see class docblock) --}}
            <section class="vy-card vy-fade" style="animation-delay:.09s">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                    <span class="vy-eyebrow">My units</span>
                    <span class="vy-soon-tag">Occupancy soon</span>
                </div>
                @forelse ($units as $u)
                    <div class="vy-list-row">
                        <div>
                            <div style="font-weight:600;font-size:13.5px">{{ $u['code'] }}</div>
                            <div class="vy-line-sub">{{ $u['room_type'] ?? 'Unit' }}</div>
                        </div>
                        <div class="vy-skeleton" style="width:70px;height:7px" title="Occupancy — not yet available"></div>
                    </div>
                @empty
                    <div class="vy-empty">No units yet.</div>
                @endforelse
            </section>

            {{-- Upcoming stays — DEFERRED + guest PII must NOT be exposed --}}
            <section class="vy-card vy-fade" style="animation-delay:.13s">
                <span class="vy-eyebrow">Upcoming stays</span>
                <div class="vy-deferred" style="margin-top:10px">
                    <span class="vy-eyebrow">Not yet available</span>
                    Upcoming arrivals will appear here. Guest personal details stay private and are never shown on the owner portal.
                </div>
            </section>
        </div>
    </div>
</x-owner.shell>