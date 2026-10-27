{{-- The platform's BUSINESS overview — real aggregates computed inside one audited, read-only PlatformQuery::read.

     ⚠️⚠️ ADMIN 01 · PHASE 1: internal organisations are EXCLUDED by default, and there is NO revenue figure until
     Phase 4 computes one from what is actually charged. The "Recurring plan value" that used to sit here summed
     the list price of free trials — on production, three of Vyrobel's own internal orgs — and read €297/month.
     With no customers, the page says so in words instead of laying out confident zeroes. --}}
@php
    $peak = collect($m['signups'])->max('count') ?: 1;
    $noCustomers = ! $m['include_internal'] && $m['organizations'] === 0;
@endphp
<x-admin.shell active="overview" :title="__('admin/overview.title')"
    :subtitle="$m['include_internal'] ? __('admin/overview.subtitle_all') : __('admin/overview.subtitle_customers')">

    <div class="ad-card" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; padding: 14px 20px;">
        <span class="ad-stat-sub" style="margin: 0;">
            @if ($m['include_internal'])
                {!! __('admin/overview.showing_all', ['internal' => e(number_format($m['internal_orgs']))]) !!}
            @else
                {{ trans_choice('admin/overview.internal_excluded', $m['internal_orgs'], ['count' => number_format($m['internal_orgs'])]) }}
            @endif
        </span>
        @if ($m['include_internal'])
            <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.overview') }}">{{ __('admin/overview.customers_only') }}</a>
        @else
            <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.overview', ['internal' => 'include']) }}">{{ __('admin/overview.include_internal') }}</a>
        @endif
    </div>

    @if ($noCustomers)
        {{-- ⚠️ Said in words. A grid of zeroes would read like a business that died; this is one that has not
             started — every organisation that exists is Vyrobel's own. --}}
        <div class="ad-card" style="text-align: center; padding: 40px 24px;">
            <h2 class="ad-panel-title" style="margin-bottom: 8px; font-size: 20px;">{{ __('admin/overview.no_customers_heading') }}</h2>
            <p class="ad-stat-sub" style="max-width: 520px; margin: 0 auto;">
                {{ __('admin/overview.no_customers_body', ['internal' => number_format($m['internal_orgs'])]) }}
            </p>
        </div>
    @else
        <div class="ad-kpis">
            <div class="ad-kpi">
                <div class="ad-kpi-label">{{ $m['include_internal'] ? __('admin/overview.kpi_organisations') : __('admin/overview.kpi_customers') }}</div>
                <div class="ad-kpi-val">{{ number_format($m['organizations']) }}</div>
                {{-- ⚠️ ADMIN 03 · F2 — the ACCOUNT's own status. A free trial is a SUBSCRIPTION state (next card): signup never
                     sets an organisation's status to "trialing", so an "on trial" figure here was always 0 beside "N in free trial". --}}
                <div class="ad-kpi-sub">{{ __('admin/overview.kpi_account_status', ['active' => number_format($m['by_status']['active']), 'suspended' => number_format($m['by_status']['suspended'])]) }}</div>
            </div>
            <div class="ad-kpi">
                <div class="ad-kpi-label">{{ __('admin/overview.kpi_subscriptions') }}</div>
                <div class="ad-kpi-val">{{ number_format($m['active_subscriptions']) }}</div>
                <div class="ad-kpi-sub">{{ __('admin/overview.kpi_subscriptions_sub', ['trial' => number_format($m['on_trial'])]) }}</div>
            </div>
            <div class="ad-kpi">
                <div class="ad-kpi-label">{{ __('admin/overview.kpi_revenue') }}</div>
                @if ($m['live_billed_subscriptions'] === 0)
                    <div class="ad-kpi-val" style="font-size: 20px; margin-top: 14px;">{{ __('admin/overview.no_live_billing') }}</div>
                    <div class="ad-kpi-sub">{{ __('admin/overview.no_live_billing_sub') }}</div>
                @else
                    <div class="ad-kpi-val" style="font-size: 20px; margin-top: 14px;">{{ trans_choice('admin/overview.billed_live', $m['live_billed_subscriptions'], ['count' => number_format($m['live_billed_subscriptions'])]) }}</div>
                    {{-- ADMIN 03 · F22 — the Revenue screen exists (Phase 4); this used to say it "arrives in a later release". --}}
                    <div class="ad-kpi-sub">{!! __('admin/overview.billed_live_sub', ['url' => e(route('admin.revenue'))]) !!}</div>
                @endif
            </div>
            <div class="ad-kpi">
                <div class="ad-kpi-label">{{ __('admin/overview.kpi_properties') }}</div>
                <div class="ad-kpi-val">{{ number_format($m['properties']) }}</div>
                <div class="ad-kpi-sub">{{ trans_choice('admin/overview.kpi_reservations', $m['reservations'], ['count' => number_format($m['reservations'])]) }}</div>
            </div>
        </div>

        <div class="ad-grid-2">
            <div class="ad-card">
                <h2 class="ad-panel-title">{{ $m['include_internal'] ? __('admin/overview.new_organisations_heading') : __('admin/overview.new_customers_heading') }}</h2>
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
                    <div class="ad-empty">{{ __('admin/overview.no_signups') }}</div>
                @endif
            </div>

            <div class="ad-card">
                <h2 class="ad-panel-title">{{ __('admin/overview.account_status_heading') }}</h2>
                @foreach (['active' => __('admin/overview.status_active'), 'suspended' => __('admin/overview.status_suspended'), 'cancelled' => __('admin/overview.status_cancelled'), 'trialing' => __('admin/overview.status_trialing')] as $key => $label)
                    @continue($key === 'trialing' && $m['by_status']['trialing'] === 0)
                    <div class="ad-row">
                        <span class="ad-row-k"><span class="ad-pill ad-pill--{{ $key }}">{{ $label }}</span></span>
                        <span class="ad-row-v">{{ number_format($m['by_status'][$key]) }}</span>
                    </div>
                @endforeach
                <p class="ad-reason-hint" style="margin-top: 8px;">{{ __('admin/overview.free_trial_hint', ['trial' => number_format($m['on_trial'])]) }}</p>

                <h2 class="ad-panel-title" style="margin-top: 22px;">{!! __('admin/overview.plans_in_use') !!}</h2>
                @forelse ($m['plan_distribution'] as $plan)
                    <div class="ad-row">
                        <span class="ad-row-k">{{ $plan['name'] }}</span>
                        <span class="ad-row-v">{{ number_format($plan['count']) }}</span>
                    </div>
                @empty
                    <div class="ad-empty">{{ __('admin/overview.no_plans') }}</div>
                @endforelse
            </div>
        </div>
    @endif
</x-admin.shell>
