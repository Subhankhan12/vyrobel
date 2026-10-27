<?php

// resources/views/livewire/operator/billing-checkout.blade.php — the operator subscription Checkout.

return [
    'page_title' => 'Checkout',
    'plan_subtitle' => ':plan plan',
    'back_to_plans' => '← Back to plans',
    'your_details' => 'Your details',
    'for_your_invoices' => 'for your invoices',
    'billing_period' => 'Billing period',
    // the period, keyed by the interval code (the toggle and the summary line)
    'interval_monthly' => 'Monthly',
    'interval_yearly' => 'Yearly',
    'company_name' => 'Company name',
    'company_placeholder' => 'e.g. Seaside Group Lda',
    // the "optional" hint stays inside the label, in its own lighter style
    'vat_label' => 'VAT / tax ID <span class="vy-fl-note">· optional</span>',
    'vat_placeholder' => 'e.g. PT 512 034 776',
    'card_on_stripe' => 'You’ll enter your card on Stripe’s secure checkout — it never touches Vyrobel. Your subscription activates once payment is confirmed.',
    'continue_to_checkout' => 'Continue to secure checkout',
    'summary' => 'Summary',
    'plan' => 'Plan',
    'billing' => 'Billing',
    // the placeholder-price note, keyed by the interval code
    'disclaimer_monthly' => 'Placeholder price while we finalise pricing. Billed monthly once live billing is available.',
    'disclaimer_yearly' => 'Placeholder price while we finalise pricing. Billed yearly once live billing is available.',
];
