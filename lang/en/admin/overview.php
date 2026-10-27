<?php

// resources/views/admin/overview.blade.php — the platform console's business overview.

return [
    'title' => 'Overview',
    'subtitle_all' => 'Every organisation, including Vyrobel\'s own internal ones.',
    'subtitle_customers' => 'Customers only — Vyrobel\'s own internal organisations are excluded.',
    'showing_all' => 'Showing <strong>all</strong> organisations — :internal of them internal.',
    'internal_excluded' => '{1} :count internal organisation excluded from every figure below.|[0,*] :count internal organisations excluded from every figure below.',
    'customers_only' => 'Customers only',
    'include_internal' => 'Include internal',
    'no_customers_heading' => 'No customers yet',
    'no_customers_body' => 'Every organisation on the platform is one of Vyrobel\'s own (:internal internal). The first real sign-up will appear here.',
    'kpi_organisations' => 'Organisations',
    'kpi_customers' => 'Customers',
    'kpi_account_status' => ':active active · :suspended suspended',
    'kpi_subscriptions' => 'Subscriptions',
    'kpi_subscriptions_sub' => 'active · :trial in free trial',
    'kpi_revenue' => 'Revenue',
    'no_live_billing' => 'No live billing yet',
    'no_live_billing_sub' => 'No subscription is billed through Stripe. A trial\'s list price is not revenue.',
    'billed_live' => '{1} :count billed live|[0,*] :count billed live',
    'billed_live_sub' => 'Computed from what Stripe charged, never the price list — see <a class="ad-cell-link" href=":url">Revenue</a>.',
    'kpi_properties' => 'Properties',
    // English printed "reservations" for every count, so it still does; other languages pluralise
    'kpi_reservations' => '{1} :count reservations|[0,*] :count reservations',
    'new_organisations_heading' => 'New organisations · last 6 months',
    'new_customers_heading' => 'New customers · last 6 months',
    'no_signups' => 'No sign-ups in the last 6 months.',
    'account_status_heading' => 'Account status',
    'status_active' => 'Active',
    'status_suspended' => 'Suspended',
    'status_cancelled' => 'Cancelled',
    'status_trialing' => 'Trialing (account)',
    'free_trial_hint' => 'A free trial is a subscription state, not an account status — :trial on a free trial now.',
    'plans_in_use' => 'Plans in use <span class="ad-stat-sub" style="font-weight: 400;">· active or trial</span>',
    'no_plans' => 'No plans in use yet.',
];
