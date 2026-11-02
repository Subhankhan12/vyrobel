<?php

// I18N-01 — zh_TW translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => '其定價方式不是計費引擎所能辨識的類型。',
    'per_unit_needs_rate' => '按房間計價的方案須先設定大於 0 的每間單價，才能販售。',
    'flat_needs_price' => '固定價格的方案須先設定大於 0 的價格，才能販售。',
    'no_price_id' => '尚未設定 Stripe 價格 ID——請先在 Stripe 建立價格，並將其 ID 貼到這裡。',
    'placeholder_price_id' => '仍連結到一個暫定的價格 ID，該 ID 在 Stripe 中並不存在。',
];
