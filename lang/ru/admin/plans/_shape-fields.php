<?php

// I18N-01 — ru translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Модель цены',
    'pricing_shape_intro' => '<strong>Фиксированный</strong> план стоит одинаково, сколько бы номеров ни было в аккаунте. План <strong>за номер</strong> взимает плату за каждый активный номер, их число пересчитывается в каждом периоде.',
    'shape_label' => 'Модель',
    'shape_flat_option' => 'Фиксированный — одна цена за период',
    'shape_per_unit_option' => 'За номер — цена × активные номера',
    'billing_interval_label' => 'Период оплаты',
    'interval_monthly_option' => 'Ежемесячно',
    'interval_yearly_option' => 'Ежегодно',
    'price_label' => 'Цена',
    'price_hint' => 'Итоговая сумма — с НДС.',
    'unit_rate_label' => 'Цена за номер',
    'unit_rate_hint' => 'Без налогов — Stripe добавляет налог самого клиента.',
    'currency_label' => 'Валюта',
    'vat_pct_label' => 'НДС, % <span style="color: var(--ad-faint); font-weight: 400;">· необязательно</span>',
    'stripe_price_heading' => 'Цена в Stripe',
    'stripe_price_intro' => 'Создайте цену (Price) в панели Stripe, затем вставьте сюда её ID — эта панель не может её создать. Цена за номер должна иметь <strong>поштучную тарификацию (per-unit), налог не включён (exclusive)</strong>; фиксированная — <strong>лицензионную тарификацию (licensed), налог включён (inclusive)</strong>. См. <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID цены в Stripe',
    'draft_not_offered' => 'Черновик — не предлагается клиентам.',
];
