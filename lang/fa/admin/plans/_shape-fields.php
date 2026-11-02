<?php

// I18N-01 — fa translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'ساختار قیمت‌گذاری',
    'pricing_shape_intro' => 'طرح <strong>ثابت</strong> یک قیمت واحد دریافت می‌کند، صرف‌نظر از تعداد واحدهایی که حساب اداره می‌کند. طرح <strong>به ازای هر واحد</strong> برای هر واحد فعال یک نرخ دریافت می‌کند که در هر دوره دوباره شمرده می‌شود.',
    'shape_label' => 'ساختار',
    'shape_flat_option' => 'ثابت — یک قیمت در هر دوره',
    'shape_per_unit_option' => 'به ازای هر واحد — نرخ × واحدهای فعال',
    'billing_interval_label' => 'دوره پرداخت',
    'interval_monthly_option' => 'ماهانه',
    'interval_yearly_option' => 'سالانه',
    'price_label' => 'قیمت',
    'price_hint' => 'مبلغ نهایی — با احتساب مالیات بر ارزش افزوده.',
    'unit_rate_label' => 'نرخ هر واحد',
    'unit_rate_hint' => 'پیش از مالیات — Stripe مالیات خود مشتری را اضافه می‌کند.',
    'currency_label' => 'ارز',
    'vat_pct_label' => 'مالیات بر ارزش افزوده (%) <span style="color: var(--ad-faint); font-weight: 400;">· اختیاری</span>',
    'stripe_price_heading' => 'قیمت (Price) در Stripe',
    'stripe_price_intro' => 'ابتدا Price را در داشبورد Stripe ایجاد کنید و سپس شناسه آن را اینجا وارد کنید — این پنل نمی‌تواند آن را ایجاد کند. Price به ازای هر واحد باید <strong>پرداخت به ازای هر واحد (per-unit) با رفتار مالیاتی exclusive</strong> باشد و Price ثابت <strong>licensed با رفتار مالیاتی inclusive</strong>. به <code>docs/BILLING-STRIPE-PRICES.md</code> مراجعه کنید.',
    'stripe_price_id_label' => 'شناسه Price در Stripe',
    'draft_not_offered' => 'پیش‌نویس — به مشتریان ارائه نمی‌شود.',
];
