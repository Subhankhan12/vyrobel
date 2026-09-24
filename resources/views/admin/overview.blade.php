{{-- The platform's BUSINESS overview — real aggregates computed inside one audited, read-only PlatformQuery::read.

     ⚠️⚠️ ADMIN 01 · PHASE 1: internal organisations are EXCLUDED by default, and there is NO revenue figure until
     Phase 4 computes one from what is actually charged. The "Recurring plan value" that used to sit here summed
     the list price of free trials — on production, three of Vyrobel's own internal orgs — and read €297/month.
     With no customers, the page says so in words instead of laying out confident zeroes. --}}
@php
    $peak = collect($m['signups'])->max('count') ?: 1;
    $noCustomers = ! $m['include_internal'] && $m['organizations'] === 0;
@endphp
<x-admin.shell active="overview" title="Overview"
    :subtitle="$m['include_internal'] ? 'Every organisation, including Vyrobel\'s own internal ones.' : 'Customers only — Vyrobel\'s own internal organisations are excluded.'">

    <div class="ad-card" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; padding: 14px 20px;">
        <span class="ad-stat-sub" style="margin: 0;">
            @if ($m['include_internal'])
                Showing <strong>all</strong> organisations — {{ number_format($m['internal_orgs']) }} of them internal.
            @else
                {{ number_format($m['internal_orgs']) }} internal organisation{{ $m['internal_orgs'] === 1 ? '' : 's' }} excluded from every figure below.
            @endif
        </span>
        @if ($m['include_internal'])
            <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.overview') }}">Customers only</a>
        @else
            <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.overview', ['internal' => 'include']) }}">Include internal</a>
        @endif
    </div>

    @if ($noCustomers)
        {{-- ⚠️ Said in words. A grid of zeroes would read like a business that died; this is one that has not
             started — every organisation that exists is Vyrobel's own. --}}
        <div class="ad-card" style="text-align: center; padding: 40px 24px;">
            <h2 class="ad-panel-title" style="margin-bottom: 8px; font-size: 20px;">No customers yet</h2>
            <p class="ad-stat-sub" style="max-width: 520px; margin: 0 auto;">
                Every organisation on the platform is one of Vyrobel's own
                ({{ number_format($m['internal_orgs']) }} internal). The first real sign-up will appear here.
            </p>
        </div>
    @else
        <div class="ad-kpis">
            <div class="ad-kpi">
                <div class="ad-kpi-label">{{ $m['include_internal'] ? 'Organisations' : 'Customers' }}</div>
                <div class="ad-kpi-val">{{ number_format($m['organizations']) }}</div>
                <div class="ad-kpi-sub">{{ number_format($m['by_status']['active']) }} active · {{ number_format($m['by_status']['trialing']) }} on trial</div>
            </div>
            <div class="ad-kpi">
                <div class="ad-kpi-label">Subscriptions</div>
                <div class="ad-kpi-val">{{ number_format($m['active_subscriptions']) }}</div>
                <div class="ad-kpi-sub">active · {{ number_format($m['on_trial']) }} in free trial</div>
            </div>
            <div class="ad-kpi">
                <div class="ad-kpi-label">Revenue</div>
                @if ($m['live_billed_subscriptions'] === 0)
                    <div class="ad-kpi-val" style="font-size: 20px; margin-top: 14px;">No live billing yet</div>
                    <div class="ad-kpi-sub">No subscription is billed through Stripe. A trial's list price is not revenue.</div>
                @else
                    <div class="ad-kpi-val" style="font-size: 20px; margin-top: 14px;">{{ number_format($m['live_billed_subscriptions']) }} billed live</div>
                    <div class="ad-kpi-sub">Revenue is computed from charges, not the price list — that screen arrives in a later release.</div>
                @endif
            </div>
            <div class="ad-kpi">
                <div class="ad-kpi-label">Properties</div>
                <div class="ad-kpi-val">{{ number_format($m['properties']) }}</div>
                <div class="ad-kpi-sub">{{ number_format($m['reservations']) }} reservations</div>
            </div>
        </div>

        <div class="ad-grid-2">
            <div class="ad-card">
                <h2 class="ad-panel-title">New {{ $m['include_internal'] ? 'organisations' : 'customers' }} · last 6 months</h2>
                @if (collect($m['signups'])->sum('count') > 0)
                    <div class="ad-chart">
                        @foreach ($m['signups'] as $point)
                            <div class="ad-chart-col" title="{{ $point['label'] }}: {{ $point['count'] }}">
                                <div class="ad-bar-val">{{ $point['count'] }}</div>
                                <div class="ad-bar {{ $point['count'] === $peak && $peak > 0 ? 'is-peak' : '' }}"
                                     style="height: {{ max(4, (int) round($point['count'] / $peak * 100)) }}%"></div>
                                <div class="ad-bar-label">{{ $point['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="ad-empty">No sign-ups in the last 6 months.</div>
                @endif
            </div>

            <div class="ad-card">
                <h2 class="ad-panel-title">By status</h2>
                @foreach (['active' => 'Active', 'trialing' => 'On trial', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $key => $label)
                    <div class="ad-row">
                        <span class="ad-row-k"><span class="ad-pill ad-pill--{{ $key }}">{{ $label }}</span></span>
                        <span class="ad-row-v">{{ number_format($m['by_status'][$key]) }}</span>
                    </div>
                @endforeach

                <h2 class="ad-panel-title" style="margin-top: 22px;">Plans in use <span class="ad-stat-sub" style="font-weight: 400;">· active or trial</span></h2>
                @forelse ($m['plan_distribution'] as $plan)
                    <div class="ad-row">
                        <span class="ad-row-k">{{ $plan['name'] }}</span>
                        <span class="ad-row-v">{{ number_format($plan['count']) }}</span>
                    </div>
                @empty
                    <div class="ad-empty">No plans in use yet.</div>
                @endforelse
            </div>
        </div>
    @endif
</x-admin.shell>
