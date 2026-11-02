<?php

// I18N-01 — zh_TW translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => '定價方式',
    'pricing_shape_intro' => '<strong>固定價格</strong>方案不論帳號營運多少房間，都收取同一個價格。<strong>按房間計價</strong>方案則對每個啟用的房間收取單價，每個週期重新計數。',
    'shape_label' => '方式',
    'shape_flat_option' => '固定價格——每個週期一個價格',
    'shape_per_unit_option' => '按房間計價——單價 × 啟用房間數',
    'billing_interval_label' => '計費週期',
    'interval_monthly_option' => '月繳',
    'interval_yearly_option' => '年繳',
    'price_label' => '價格',
    'price_hint' => '最終金額——含加值稅。',
    'unit_rate_label' => '每間單價',
    'unit_rate_hint' => '未稅價——Stripe 會另外加上客戶適用的稅額。',
    'currency_label' => '幣別',
    'vat_pct_label' => '加值稅率（%）<span style="color: var(--ad-faint); font-weight: 400;">· 選填</span>',
    'stripe_price_heading' => 'Stripe 價格',
    'stripe_price_intro' => '請在 Stripe 管理平台中建立價格，然後將其 ID 貼到這裡——此面板無法建立價格。按房間計價的價格必須設為<strong>按單位計費、稅務行為為不含稅</strong>；固定價格則須設為<strong>授權制、含稅</strong>。請參閱 <code>docs/BILLING-STRIPE-PRICES.md</code>。',
    'stripe_price_id_label' => 'Stripe 價格 ID',
    'draft_not_offered' => '草稿——不提供給客戶。',
];
