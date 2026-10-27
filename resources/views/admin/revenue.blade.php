{{-- ⚠️⚠️ ADMIN 01 · PHASE 4 — REVENUE, traced to the charge: paid Stripe invoices only, never the pricing table. With no
     paid invoice the page says "No live billing yet" in words — never a confident zero that looks like a bad month. --}}
@php use App\Support\Money\MoneyFormatter; @endphp
<x-admin.shell active="revenue" :title="__('admin/revenue.title')" :subtitle="__('admin/revenue.subtitle')">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">{{ $r['include_internal'] ? __('admin/revenue.including_internal') : __('admin/revenue.customers_only_note') }}</span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.revenue', $r['include_internal'] ? [] : ['internal' => 'include']) }}">{{ $r['include_internal'] ? __('admin/revenue.customers_only_link') : __('admin/revenue.include_internal_link') }}</a>
    </div>

    @if (! $r['live'])
        <div class="ad-card">
            <span class="ad-hpill ad-hpill--grey">{{ __('admin/revenue.no_live_billing_pill') }}</span>
            <h2 class="ad-panel-title" style="margin: 12px 0 6px;">{{ __('admin/revenue.no_customer_charged') }}</h2>
            <p class="ad-stat-sub" style="margin: 0; line-height: 1.7;">
                {!! __('admin/revenue.not_live_explainer') !!}
            </p>
            <div class="ad-row" style="margin-top: 14px;"><span class="ad-row-k">{{ __('admin/revenue.live_billed_subscriptions') }}</span><span class="ad-row-v">{{ number_format($r['live_billed_subscriptions']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.trialing_unbilled') }}</span><span class="ad-row-v">{{ number_format($r['trialing_unbilled']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.failed_payments_30d') }}</span><span class="ad-row-v">{{ number_format($r['failed_payments_30d']) }}</span></div>
            <p class="ad-stat-sub" style="margin: 14px 0 0;">{{ __('admin/revenue.when_first_paid') }}</p>
        </div>
    @else
        <div class="ad-grid-2">
            <div class="ad-card">
                <h2 class="ad-panel-title">{{ __('admin/revenue.mrr_arr_heading') }}</h2>
                @forelse ($r['mrr'] as $currency => $row)
                    <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.mrr_currency', ['currency' => $currency]) }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['mrr_minor'], $currency) }}</span></div>
                    <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.arr_currency', ['currency' => $currency]) }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['arr_minor'], $currency) }}</span></div>
                    <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.subscriptions_valued') }}</span><span class="ad-row-v">{{ $row['subscriptions'] }}</span></div>
                @empty
                    <div class="ad-empty">{{ __('admin/revenue.no_live_subscription_charged') }}</div>
                @endforelse
                <p class="ad-reason-hint" style="margin-top: 8px;">{{ __('admin/revenue.mrr_hint') }}</p>
            </div>
            <div class="ad-card">
                <h2 class="ad-panel-title">{{ __('admin/revenue.this_month_vs_last') }}</h2>
                @forelse ($r['movements'] as $currency => $m)
                    @foreach (['new', 'expansion', 'contraction', 'churned'] as $k)
                        @php
                            $label = match ($k) {
                                'new' => __('admin/revenue.movement_new', ['currency' => $currency]),
                                'expansion' => __('admin/revenue.movement_expansion', ['currency' => $currency]),
                                'contraction' => __('admin/revenue.movement_contraction', ['currency' => $currency]),
                                'churned' => __('admin/revenue.movement_churned', ['currency' => $currency]),
                            };
                        @endphp
                        <div class="ad-row"><span class="ad-row-k">{{ $label }}</span><span class="ad-row-v">{{ MoneyFormatter::display($m[$k], $currency) }}</span></div>
                    @endforeach
                @empty
                    <div class="ad-empty">{{ __('admin/revenue.nothing_charged') }}</div>
                @endforelse
                <p class="ad-reason-hint" style="margin-top: 8px;">{{ __('admin/revenue.movements_hint') }}</p>
            </div>
        </div>
        <div class="ad-card" style="margin-top: 18px;">
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.trial_to_paid') }}</span><span class="ad-row-v">{{ trans_choice('admin/revenue.trial_to_paid_value', $r['trial_to_paid']['ended'], ['converted' => $r['trial_to_paid']['converted'], 'ended' => $r['trial_to_paid']['ended']]) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.failed_payments_30d') }}</span><span class="ad-row-v">{{ $r['failed_payments_30d'] }}</span></div>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.past_due_now') }}</span><span class="ad-row-v">{{ $r['past_due_now'] }}</span></div>
            <div class="ad-row"><span class="ad-row-k">{{ __('admin/revenue.paid_invoices_counted') }}</span><span class="ad-row-v">{{ $r['paid_invoices'] }}</span></div>
        </div>
    @endif

    <p class="ad-stat-sub" style="margin-top: 14px;">{{ __('admin/revenue.never_moves_money') }}</p>
</x-admin.shell>
