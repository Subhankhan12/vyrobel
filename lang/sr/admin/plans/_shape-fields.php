<?php

// I18N-01 — sr translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'Модел цене',
    'pricing_shape_intro' => 'План са <strong>фиксном</strong> ценом наплаћује једну цену без обзира на то колико јединица налог има. План <strong>по јединици</strong> наплаћује цену за сваку активну јединицу, поново пребројану у сваком периоду.',
    'shape_label' => 'Модел',
    'shape_flat_option' => 'Фиксна – једна цена по периоду',
    'shape_per_unit_option' => 'По јединици – цена × активне јединице',
    'billing_interval_label' => 'Интервал наплате',
    'interval_monthly_option' => 'Месечно',
    'interval_yearly_option' => 'Годишње',
    'price_label' => 'Цена',
    'price_hint' => 'Коначан износ – са ПДВ-ом.',
    'unit_rate_label' => 'Цена по јединици',
    'unit_rate_hint' => 'Без пореза – Stripe додаје порез који плаћа клијент.',
    'currency_label' => 'Валута',
    'vat_pct_label' => 'ПДВ % <span style="color: var(--ad-faint); font-weight: 400;">· опционо</span>',
    'stripe_price_heading' => 'Цена (Stripe Price)',
    'stripe_price_intro' => 'Направите цену (Price) на контролној табли система Stripe, па овде налепите њен ID – овај панел не може да је направи. Цена по јединици мора имати <strong>обрачун по јединици и порез који није укључен у цену</strong>; фиксна мора имати <strong>лиценцирани тип и порез укључен у цену</strong>. Погледајте <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'ID цене (Stripe Price)',
    'draft_not_offered' => 'Нацрт – не нуди се клијентима.',
];
