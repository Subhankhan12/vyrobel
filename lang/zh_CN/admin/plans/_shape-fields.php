<?php

// I18N-01 — zh_CN translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => '定价方式',
    'pricing_shape_intro' => '<strong>固定价格</strong>方案无论账号运营多少房间，均收取同一价格。<strong>按房间计价</strong>方案对每个启用的房间收取单价，每个周期重新计数。',
    'shape_label' => '方式',
    'shape_flat_option' => '固定价格——每个周期一个价格',
    'shape_per_unit_option' => '按房间计价——单价 × 启用房间数',
    'billing_interval_label' => '计费周期',
    'interval_monthly_option' => '按月',
    'interval_yearly_option' => '按年',
    'price_label' => '价格',
    'price_hint' => '最终金额——含增值税。',
    'unit_rate_label' => '每间单价',
    'unit_rate_hint' => '税前价格——Stripe 会另加客户适用的税费。',
    'currency_label' => '货币',
    'vat_pct_label' => '增值税率（%）<span style="color: var(--ad-faint); font-weight: 400;">· 可选</span>',
    'stripe_price_heading' => 'Stripe 价格',
    'stripe_price_intro' => '请在 Stripe 管理平台中创建价格，然后将其 ID 粘贴到这里——本面板无法创建价格。按房间计价的价格必须为<strong>按单位计费、税费行为为不含税</strong>；固定价格则必须为<strong>许可计费、含税</strong>。请参阅 <code>docs/BILLING-STRIPE-PRICES.md</code>。',
    'stripe_price_id_label' => 'Stripe 价格 ID',
    'draft_not_offered' => '草稿——不向客户提供。',
];
