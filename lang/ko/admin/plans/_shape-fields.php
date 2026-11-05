<?php

// I18N-01 — ko translation of lang/en/admin/plans/_shape-fields.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'pricing_shape_heading' => '가격 구조',
    'pricing_shape_intro' => '<strong>정액</strong> 요금제는 계정이 운영하는 객실 수와 관계없이 하나의 가격을 청구합니다. <strong>객실당</strong> 요금제는 활성 객실마다 요금을 청구하며, 매 기간 다시 집계합니다.',
    'shape_label' => '구조',
    'shape_flat_option' => '정액 — 기간당 하나의 가격',
    'shape_per_unit_option' => '객실당 — 요금 × 활성 객실',
    'billing_interval_label' => '청구 주기',
    'interval_monthly_option' => '매월',
    'interval_yearly_option' => '매년',
    'price_label' => '가격',
    'price_hint' => '최종 금액 — 부가세 포함.',
    'unit_rate_label' => '객실당 요금',
    'unit_rate_hint' => '세전 — Stripe가 고객사의 세금을 추가합니다.',
    'currency_label' => '통화',
    'vat_pct_label' => '부가세 % <span style="color: var(--ad-faint); font-weight: 400;">· 선택 사항</span>',
    'stripe_price_heading' => 'Stripe 가격',
    'stripe_price_intro' => 'Stripe 대시보드에서 가격을 만든 후 여기에 ID를 붙여 넣으세요 — 이 패널에서는 만들 수 없습니다. 객실당 가격은 <strong>단위당 청구, 세금 별도</strong>여야 하고, 정액 가격은 <strong>라이선스 방식, 세금 포함</strong>이어야 합니다. <code>docs/BILLING-STRIPE-PRICES.md</code>를 참조하세요.',
    'stripe_price_id_label' => 'Stripe 가격 ID',
    'draft_not_offered' => '초안 — 고객사에 제공되지 않습니다.',
];
