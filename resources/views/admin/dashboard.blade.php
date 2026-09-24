{{-- SUPER ADMIN · BUILD 1 · GATE 1 — the platform Overview: REAL metrics across ALL orgs (computed inside one
     audited PlatformQuery::read). Every figure is a real aggregate (#39); zero states are honest. --}}
@php
    use App\Support\Money\MoneyFormatter;
    $peak = collect($m['signups'])->max('count') ?: 1;
@endphp
<x-admin.shell active="overview" title="Overview"
    subtitle="Real-time metrics across every organization on the platform.">

    {{-- KPI band — real counts. --}}
    <div class="ad-kpis">
        <div class="ad-kpi">
            <div class="ad-kpi-label">Customers</div>
            <div class="ad-kpi-val">{{ number_format($m['organizations']) }}</div>
            <div class="ad-kpi-sub">{{ number_format($m['by_status']['active']) }} active · {{ number_format($m['by_status']['trialing']) }} on trial</div>
        </div>
        <div class="ad-kpi">
            <div class="ad-kpi-label">Active subscriptions</div>
            <div class="ad-kpi-val">{{ number_format($m['active_subscriptions']) }}</div>
            <div class="ad-kpi-sub">{{ number_format($m['on_trial']) }} in free trial</div>
        </div>
        <div class="ad-kpi">
            <div class="ad-kpi-label">Recurring plan value</div>
            <div class="ad-kpi-val">{{ MoneyFormatter::display($m['recurring_plan_value_minor'], $m['currency']) }}</div>
            <div class="ad-kpi-sub">/ month · contracted, not yet billed</div>
        </div>
        <div class="ad-kpi">
            <div class="ad-kpi-label">Properties</div>
            <div class="ad-kpi-val">{{ number_format($m['properties']) }}</div>
            <div class="ad-kpi-sub">{{ number_format($m['reservations']) }} reservations platform-wide</div>
        </div>
    </div>

    <div class="ad-grid-2">
        {{-- Signups over time — a real 6-month series over organizations.created_at. --}}
        <div class="ad-card">
            <h2 class="ad-panel-title">New customers · last 6 months</h2>
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
                <div class="ad-empty">No sign-ups in the last 6 months yet.</div>
            @endif
        </div>

        {{-- Status breakdown + plan distribution — real. --}}
        <div class="ad-card">
            <h2 class="ad-panel-title">By status</h2>
            @foreach (['active' => 'Active', 'trialing' => 'On trial', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled'] as $key => $label)
                <div class="ad-row">
                    <span class="ad-row-k"><span class="ad-pill ad-pill--{{ $key }}">{{ $label }}</span></span>
                    <span class="ad-row-v">{{ number_format($m['by_status'][$key]) }}</span>
                </div>
            @endforeach

            <h2 class="ad-panel-title" style="margin-top: 22px;">Plan distribution</h2>
            @forelse ($m['plan_distribution'] as $plan)
                <div class="ad-row">
                    <span class="ad-row-k">{{ $plan['name'] }}</span>
                    <span class="ad-row-v">{{ number_format($plan['count']) }}</span>
                </div>
            @empty
                <div class="ad-empty">No active plans yet.</div>
            @endforelse
        </div>
    </div>
</x-admin.shell>
