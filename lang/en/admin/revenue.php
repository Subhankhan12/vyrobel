<?php

// resources/views/admin/revenue.blade.php — the platform admin console's Revenue page.

return [
    'title' => 'Revenue',
    'subtitle' => 'From what Stripe actually charged — never from list prices. Customers only unless you include Vyrobel’s own.',
    'including_internal' => 'Including Vyrobel’s own internal organisations.',
    'customers_only_note' => 'Customers only.',
    'customers_only_link' => 'Customers only',
    'include_internal_link' => 'Include internal',
    'no_live_billing_pill' => 'No live billing yet',
    'no_customer_charged' => 'No customer has been charged yet.',
    'not_live_explainer' => 'Stripe is not live, so there is no paid invoice to count. That is not a bad month — there is no month yet. Revenue here is computed only from <strong>paid Stripe invoices</strong> (the charge itself, recorded when Stripe tells us), never from a plan’s list price.',
    'live_billed_subscriptions' => 'Live-billed subscriptions',
    'trialing_unbilled' => 'Subscriptions on a trial, not billed',
    'failed_payments_30d' => 'Failed payments, last 30 days',
    'when_first_paid' => 'When the first invoice is paid, this page shows: MRR and ARR per currency · new, expansion, contraction and churned MRR · trial-to-paid conversion · failed payments.',
    'mrr_arr_heading' => 'MRR / ARR',
    'mrr_currency' => 'MRR · :currency',
    'arr_currency' => 'ARR · :currency',
    'subscriptions_valued' => 'Subscriptions valued',
    'no_live_subscription_charged' => 'No active, live-billed subscription has been charged.',
    'mrr_hint' => 'Each active or past-due live subscription at its most recent paid invoice, put on a monthly footing by that invoice’s billing period.',
    'this_month_vs_last' => 'This month vs last',
    'movement_new' => 'New · :currency',
    'movement_expansion' => 'Expansion · :currency',
    'movement_contraction' => 'Contraction · :currency',
    'movement_churned' => 'Churned · :currency',
    'nothing_charged' => 'Nothing charged last month or this month.',
    'movements_hint' => 'Per organisation, from what was charged in each month. Churned = charged last month, nothing this month, and the subscription is cancelled.',
    'trial_to_paid' => 'Trial to paid',
    // :ended is the plural count — a language whose noun changes with the number can add forms here
    'trial_to_paid_value' => ':converted of :ended ended trials have been charged',
    'past_due_now' => 'Past due now',
    'paid_invoices_counted' => 'Paid invoices counted',
    'never_moves_money' => 'This console never charges, refunds or moves money. Every figure is per currency and never summed across currencies.',
];
