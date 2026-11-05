<?php

// I18N-01 — zh_CN translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => '其计价方式不受计费引擎支持。',
    'per_unit_needs_rate' => '按房间计价的方案须设置大于零的每间价格，才能销售。',
    'flat_needs_price' => '固定价格方案须设置大于零的价格，才能销售。',
    'no_price_id' => '尚无 Stripe 价格 ID——请先在 Stripe 中创建该价格，并将其 ID 粘贴到此处。',
    'placeholder_price_id' => '仍关联着一个临时价格 ID，该 ID 在 Stripe 中不存在。',
];
