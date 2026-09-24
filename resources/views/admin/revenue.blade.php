{{-- ⚠️⚠️ ADMIN 01 · PHASE 4 — REVENUE, traced to the charge: paid Stripe invoices only, never the pricing table. With no
     paid invoice the page says "No live billing yet" in words — never a confident zero that looks like a bad month. --}}
@php use App\Support\Money\MoneyFormatter; @endphp
<x-admin.shell active="revenue" title="Revenue" subtitle="From what Stripe actually charged — never from list prices. Customers only unless you include Vyrobel’s own.">

    <div class="ad-filters" style="justify-content: space-between;">
        <span class="ad-stat-sub" style="margin: 0;">{{ $r['include_internal'] ? 'Including Vyrobel’s own internal organisations.' : 'Customers only.' }}</span>
        <a class="ad-btn ad-btn--ghost ad-btn--sm" href="{{ route('admin.revenue', $r['include_internal'] ? [] : ['internal' => 'include']) }}">{{ $r['include_internal'] ? 'Customers only' : 'Include internal' }}</a>
    </div>

    @if (! $r['live'])
        <div class="ad-card">
            <span class="ad-hpill ad-hpill--grey">No live billing yet</span>
            <h2 class="ad-panel-title" style="margin: 12px 0 6px;">No customer has been charged yet.</h2>
            <p class="ad-stat-sub" style="margin: 0; line-height: 1.7;">
                Stripe is not live, so there is no paid invoice to count. That is not a bad month — there is no month yet.
                Revenue here is computed only from <strong>paid Stripe invoices</strong> (the charge itself, recorded when Stripe
                tells us), never from a plan’s list price.
            </p>
            <div class="ad-row" style="margin-top: 14px;"><span class="ad-row-k">Live-billed subscriptions</span><span class="ad-row-v">{{ number_format($r['live_billed_subscriptions']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Subscriptions on a trial, not billed</span><span class="ad-row-v">{{ number_format($r['trialing_unbilled']) }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Failed payments, last 30 days</span><span class="ad-row-v">{{ number_format($r['failed_payments_30d']) }}</span></div>
            <p class="ad-stat-sub" style="margin: 14px 0 0;">When the first invoice is paid, this page shows: MRR and ARR per currency · new, expansion, contraction and churned MRR · trial-to-paid conversion · failed payments.</p>
        </div>
    @else
        <div class="ad-grid-2">
            <div class="ad-card">
                <h2 class="ad-panel-title">MRR / ARR</h2>
                @forelse ($r['mrr'] as $currency => $row)
                    <div class="ad-row"><span class="ad-row-k">MRR · {{ $currency }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['mrr_minor'], $currency) }}</span></div>
                    <div class="ad-row"><span class="ad-row-k">ARR · {{ $currency }}</span><span class="ad-row-v">{{ MoneyFormatter::display($row['arr_minor'], $currency) }}</span></div>
                    <div class="ad-row"><span class="ad-row-k">Subscriptions valued</span><span class="ad-row-v">{{ $row['subscriptions'] }}</span></div>
                @empty
                    <div class="ad-empty">No active, live-billed subscription has been charged.</div>
                @endforelse
                <p class="ad-reason-hint" style="margin-top: 8px;">Each active or past-due live subscription at its most recent paid invoice, put on a monthly footing by that invoice’s billing period.</p>
            </div>
            <div class="ad-card">
                <h2 class="ad-panel-title">This month vs last</h2>
                @forelse ($r['movements'] as $currency => $m)
                    @foreach (['new' => 'New', 'expansion' => 'Expansion', 'contraction' => 'Contraction', 'churned' => 'Churned'] as $k => $label)
                        <div class="ad-row"><span class="ad-row-k">{{ $label }} · {{ $currency }}</span><span class="ad-row-v">{{ MoneyFormatter::display($m[$k], $currency) }}</span></div>
                    @endforeach
                @empty
                    <div class="ad-empty">Nothing charged last month or this month.</div>
                @endforelse
                <p class="ad-reason-hint" style="margin-top: 8px;">Per organisation, from what was charged in each month. Churned = charged last month, nothing this month, and the subscription is cancelled.</p>
            </div>
        </div>
        <div class="ad-card" style="margin-top: 18px;">
            <div class="ad-row"><span class="ad-row-k">Trial to paid</span><span class="ad-row-v">{{ $r['trial_to_paid']['converted'] }} of {{ $r['trial_to_paid']['ended'] }} ended trials have been charged</span></div>
            <div class="ad-row"><span class="ad-row-k">Failed payments, last 30 days</span><span class="ad-row-v">{{ $r['failed_payments_30d'] }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Past due now</span><span class="ad-row-v">{{ $r['past_due_now'] }}</span></div>
            <div class="ad-row"><span class="ad-row-k">Paid invoices counted</span><span class="ad-row-v">{{ $r['paid_invoices'] }}</span></div>
        </div>
    @endif

    <p class="ad-stat-sub" style="margin-top: 14px;">This console never charges, refunds or moves money. Every figure is per currency and never summed across currencies.</p>
</x-admin.shell>
