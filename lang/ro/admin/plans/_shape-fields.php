<?php

// I18N-01 — ro translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Structura prețului',
    'pricing_shape_intro' => 'Un plan <strong>fix</strong> are un singur preț, indiferent câte unități are contul. Un plan <strong>per unitate</strong> aplică un tarif pentru fiecare unitate activă, recalculat în fiecare perioadă.',
    'shape_label' => 'Structură',
    'shape_flat_option' => 'Fix — un singur preț pe perioadă',
    'shape_per_unit_option' => 'Per unitate — tarif × unități active',
    'billing_interval_label' => 'Interval de facturare',
    'interval_monthly_option' => 'Lunar',
    'interval_yearly_option' => 'Anual',
    'price_label' => 'Preț',
    'price_hint' => 'Suma finală — cu TVA inclus.',
    'unit_rate_label' => 'Tarif per unitate',
    'unit_rate_hint' => 'Fără taxe — Stripe adaugă taxa aplicabilă clientului.',
    'currency_label' => 'Monedă',
    'vat_pct_label' => 'TVA % <span style="color: var(--ad-faint); font-weight: 400;">· opțional</span>',
    'stripe_price_heading' => 'Preț Stripe (Price)',
    'stripe_price_intro' => 'Creați obiectul Price în panoul Stripe, apoi lipiți aici ID-ul său — acest panou nu îl poate crea. Un Price per unitate trebuie să aibă <strong>facturare per unitate (per-unit) și taxe excluse (exclusive)</strong>; unul fix — <strong>licențiat (licensed), cu taxe incluse (inclusive)</strong>. Vedeți <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID de Price Stripe',
    'draft_not_offered' => 'Ciornă — nu este oferit clienților.',
];
