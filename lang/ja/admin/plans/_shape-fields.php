<?php

// I18N-01 — ja translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => '料金体系',
    'pricing_shape_intro' => '<strong>定額</strong>プランは、アカウントが運営する客室数にかかわらず1つの料金を請求します。<strong>客室単位</strong>プランは、有効な客室ごとに料金を請求し、期間ごとに数え直します。',
    'shape_label' => '体系',
    'shape_flat_option' => '定額 — 期間ごとに1つの料金',
    'shape_per_unit_option' => '客室単位 — 料金 × 有効な客室数',
    'billing_interval_label' => '請求サイクル',
    'interval_monthly_option' => '月払い',
    'interval_yearly_option' => '年払い',
    'price_label' => '料金',
    'price_hint' => '最終金額（VAT込み）です。',
    'unit_rate_label' => '1室あたりの料金',
    'unit_rate_hint' => '税別 — 顧客ごとの税はStripeが加算します。',
    'currency_label' => '通貨',
    'vat_pct_label' => 'VAT税率（%） <span style="color: var(--ad-faint); font-weight: 400;">· 任意</span>',
    'stripe_price_heading' => 'Stripe価格',
    'stripe_price_intro' => 'Stripeダッシュボードで価格を作成し、そのIDをここに貼り付けてください。このパネルでは作成できません。客室単位の価格は<strong>単位ごとの請求、税の扱いは税別</strong>、定額の価格は<strong>ライセンス型、税込</strong>にする必要があります。<code>docs/BILLING-STRIPE-PRICES.md</code>を参照してください。',
    'stripe_price_id_label' => 'Stripe価格ID',
    'draft_not_offered' => '下書き — 顧客には提供されていません。',
];
