<?php

// resources/views/admin/plans/_shape-fields.blade.php — a plan's pricing shape, money basis and Stripe Price (create and edit).

return [
    'pricing_shape_heading' => 'Pricing shape',
    'pricing_shape_intro' => 'A <strong>flat</strong> plan charges one price however many units the account runs. A <strong>per-unit</strong> plan charges a rate for every active unit, re-counted each period.',
    'shape_label' => 'Shape',
    'shape_flat_option' => 'Flat — one price per period',
    'shape_per_unit_option' => 'Per unit — rate × active units',
    'billing_interval_label' => 'Billing interval',
    'interval_monthly_option' => 'Monthly',
    'interval_yearly_option' => 'Yearly',
    'price_label' => 'Price',
    'price_hint' => 'The final amount — VAT-inclusive.',
    'unit_rate_label' => 'Rate per unit',
    'unit_rate_hint' => 'Pre-tax — Stripe adds the customer\'s own tax.',
    'currency_label' => 'Currency',
    // the "· optional" marker sits inside the label so each language can place it
    'vat_pct_label' => 'VAT % <span style="color: var(--ad-faint); font-weight: 400;">· optional</span>',
    'stripe_price_heading' => 'Stripe Price',
    'stripe_price_intro' => 'Create the Price in the Stripe dashboard, then paste its id here — this panel cannot create it. A per-unit Price must be <strong>per-unit billing, tax behaviour exclusive</strong>; a flat one <strong>licensed, inclusive</strong>. See <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe Price id',
    'draft_not_offered' => 'Draft — not offered to customers.',
];
