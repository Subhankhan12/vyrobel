<?php

// I18N-01 — nl translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Prijsmodel',
    'pricing_shape_intro' => 'Een <strong>vast</strong> pakket rekent één prijs, hoeveel eenheden het account ook beheert. Een pakket <strong>per eenheid</strong> rekent een tarief voor elke actieve eenheid, elke periode opnieuw geteld.',
    'shape_label' => 'Model',
    'shape_flat_option' => 'Vast — één prijs per periode',
    'shape_per_unit_option' => 'Per eenheid — tarief × actieve eenheden',
    'billing_interval_label' => 'Factuurinterval',
    'interval_monthly_option' => 'Maandelijks',
    'interval_yearly_option' => 'Jaarlijks',
    'price_label' => 'Prijs',
    'price_hint' => 'Het eindbedrag — inclusief btw.',
    'unit_rate_label' => 'Tarief per eenheid',
    'unit_rate_hint' => 'Exclusief belasting — Stripe telt de eigen belasting van de klant erbij op.',
    'currency_label' => 'Valuta',
    'vat_pct_label' => 'Btw-% <span style="color: var(--ad-faint); font-weight: 400;">· optioneel</span>',
    'stripe_price_heading' => 'Stripe-prijs',
    'stripe_price_intro' => 'Maak de prijs aan in het Stripe-dashboard en plak de ID hier — dit paneel kan die niet aanmaken. Een prijs per eenheid moet <strong>facturatie per eenheid, belasting exclusief</strong> zijn; een vaste prijs <strong>gelicentieerd, inclusief</strong>. Zie <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe-prijs-ID',
    'draft_not_offered' => 'Concept — niet aangeboden aan klanten.',
];
