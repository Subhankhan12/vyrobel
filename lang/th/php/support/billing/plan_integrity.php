<?php

// I18N-01 — th translation of lang/en/php/support/billing/plan_integrity.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'unknown_shape' => 'รูปแบบราคาของแผนนี้ไม่ใช่รูปแบบที่ระบบเรียกเก็บเงินรองรับ',
    'per_unit_needs_rate' => 'แผนแบบต่อห้องพักต้องมีราคาต่อห้องพักมากกว่าศูนย์ก่อนจึงจะขายได้',
    'flat_needs_price' => 'แผนแบบราคาคงที่ต้องมีราคามากกว่าศูนย์ก่อนจึงจะขายได้',
    'no_price_id' => 'ยังไม่มีรหัสราคาใน Stripe — โปรดสร้างราคาใน Stripe แล้ววางรหัสของราคานั้นที่นี่ก่อน',
    'placeholder_price_id' => 'ยังเชื่อมกับรหัสราคาชั่วคราวที่ไม่มีอยู่จริงใน Stripe',
];
