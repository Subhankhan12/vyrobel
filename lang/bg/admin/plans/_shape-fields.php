<?php

// I18N-01 — bg translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Модел на ценообразуване',
    'pricing_shape_intro' => '<strong>Фиксираният</strong> план таксува една цена, независимо колко единици управлява акаунтът. Планът <strong>на единица</strong> таксува цена за всяка активна единица, преброявана наново всеки период.',
    'shape_label' => 'Модел',
    'shape_flat_option' => 'Фиксиран – една цена за период',
    'shape_per_unit_option' => 'На единица – цена × активни единици',
    'billing_interval_label' => 'Период на фактуриране',
    'interval_monthly_option' => 'Месечно',
    'interval_yearly_option' => 'Годишно',
    'price_label' => 'Цена',
    'price_hint' => 'Крайната сума – с включен ДДС.',
    'unit_rate_label' => 'Цена на единица',
    'unit_rate_hint' => 'Без данъци – Stripe добавя данъка на клиента.',
    'currency_label' => 'Валута',
    'vat_pct_label' => 'ДДС % <span style="color: var(--ad-faint); font-weight: 400;">· по избор</span>',
    'stripe_price_heading' => 'Цена в Stripe',
    'stripe_price_intro' => 'Създайте цената (Price) в таблото на Stripe и поставете идентификатора ѝ тук – този панел не може да я създаде. Цената на единица трябва да е с <strong>таксуване на единица, данъкът не е включен (exclusive)</strong>; фиксираната – <strong>лицензна (licensed), с включен данък (inclusive)</strong>. Вижте <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'Stripe Price ID',
    'draft_not_offered' => 'Чернова – не се предлага на клиентите.',
];
