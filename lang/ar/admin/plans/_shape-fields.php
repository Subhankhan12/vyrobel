<?php

// I18N-01 — ar translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => 'نوع التسعير',
    'pricing_shape_intro' => 'تفرض الباقة <strong>الثابتة</strong> سعرًا واحدًا مهما كان عدد الوحدات التي يشغلها الحساب. وتفرض الباقة <strong>لكل وحدة</strong> سعرًا عن كل وحدة نشطة، يعاد احتسابها في كل فترة.',
    'shape_label' => 'النوع',
    'shape_flat_option' => 'ثابتة — سعر واحد لكل فترة',
    'shape_per_unit_option' => 'لكل وحدة — السعر × الوحدات النشطة',
    'billing_interval_label' => 'دورة الفوترة',
    'interval_monthly_option' => 'شهري',
    'interval_yearly_option' => 'سنوي',
    'price_label' => 'السعر',
    'price_hint' => 'المبلغ النهائي — شامل ضريبة القيمة المضافة.',
    'unit_rate_label' => 'السعر لكل وحدة',
    'unit_rate_hint' => 'قبل الضريبة — يضيف Stripe الضريبة الخاصة بالعميل.',
    'currency_label' => 'العملة',
    'vat_pct_label' => 'نسبة ضريبة القيمة المضافة % <span style="color: var(--ad-faint); font-weight: 400;">· اختياري</span>',
    'stripe_price_heading' => 'سعر Stripe (Price)',
    'stripe_price_intro' => 'يرجى إنشاء السعر (Price) في لوحة تحكم Stripe، ثم لصق معرفه هنا — لا يمكن لهذه اللوحة إنشاؤه. يجب أن يكون السعر لكل وحدة <strong>فوترة لكل وحدة (per-unit)، وسلوك ضريبي غير شامل (exclusive)</strong>؛ والسعر الثابت <strong>مرخص (licensed)، شامل (inclusive)</strong>. التفاصيل في <code>docs/BILLING-STRIPE-PRICES.md</code>.',
    'stripe_price_id_label' => 'معرف سعر Stripe',
    'draft_not_offered' => 'مسودة — غير معروضة على العملاء.',
];
