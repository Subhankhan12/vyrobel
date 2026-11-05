<?php

// I18N-01 — ko translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => '청구 엔진이 인식할 수 없는 가격 구조입니다.',
    'per_unit_needs_rate' => '객실당 요금제는 판매하기 전에 0보다 큰 객실당 요금이 필요합니다.',
    'flat_needs_price' => '정액 요금제는 판매하기 전에 0보다 큰 가격이 필요합니다.',
    'no_price_id' => '아직 Stripe 가격 ID가 없습니다 — 먼저 Stripe에서 가격을 만들고 여기에 그 ID를 붙여 넣으세요.',
    'placeholder_price_id' => '아직 임시 가격 ID에 연결되어 있으며, 이 ID는 Stripe에 존재하지 않습니다.',
];
