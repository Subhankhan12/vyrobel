<?php

// I18N-01 — sq translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Modeli i çmimit',
    'pricing_shape_intro' => 'Një plan <strong>fiks</strong> debiton një çmim të vetëm, sado njësi të ketë llogaria. Një plan <strong>për njësi</strong> debiton një tarifë për çdo njësi aktive, të rinumëruar në çdo periudhë.',
    'shape_label' => 'Modeli',
    'shape_flat_option' => 'Fiks — një çmim për periudhë',
    'shape_per_unit_option' => 'Për njësi — tarifa × njësitë aktive',
    'billing_interval_label' => 'Intervali i faturimit',
    'interval_monthly_option' => 'Mujor',
    'interval_yearly_option' => 'Vjetor',
    'price_label' => 'Çmimi',
    'price_hint' => 'Shuma përfundimtare — me TVSH të përfshirë.',
    'unit_rate_label' => 'Tarifa për njësi',
    'unit_rate_hint' => 'Pa taksa — Stripe shton taksën e vetë klientit.',
    'currency_label' => 'Monedha',
    'vat_pct_label' => 'TVSH % <span style="color: var(--ad-faint); font-weight: 400;">· opsionale</span>',
    'stripe_price_heading' => 'Price në Stripe',
    'stripe_price_intro' => 'Krijoni objektin Price në panelin e Stripe, pastaj ngjitni këtu ID-në e tij — ky panel nuk mund ta krijojë. Një Price për njësi duhet të jetë <strong>me faturim për njësi (per-unit), me taksën të përjashtuar (exclusive)</strong>; një Price fiks <strong>me licencë (licensed), me taksën të përfshirë (inclusive)</strong>. Shihni <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID-ja e Price në Stripe',
    'draft_not_offered' => 'Skicë — nuk u ofrohet klientëve.',
];
