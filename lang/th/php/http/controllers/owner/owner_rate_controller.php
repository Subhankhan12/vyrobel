<?php

// I18N-01 — th translation of lang/en/php/http/controllers/owner/owner_rate_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'invalid_dates' => 'เลือกวันที่เริ่มและวันที่สิ้นสุดที่ถูกต้อง',
    'end_before_start' => 'วันที่สิ้นสุดต้องไม่อยู่ก่อนวันที่เริ่ม',
    'span_too_long' => 'โปรดใช้ราคาครั้งละไม่เกิน 1 ปี',
    'invalid_rate' => 'ป้อนราคาต่อคืนที่ถูกต้อง เช่น 175 หรือ 175.50',
    'invalid_min_stay' => 'จำนวนคืนขั้นต่ำต้องเป็นจำนวนเต็ม (1–365) หรือเว้นว่าง',
    'invalid_weekend_uplift' => 'ราคาเพิ่มช่วงสุดสัปดาห์ต้องเป็นเปอร์เซ็นต์ระหว่าง 0 ถึง 200 หรือเว้นว่าง',
    'rate_updated' => '{1}อัปเดตราคาครอบคลุม :count คืนแล้ว มีผลทันที|[0,*]อัปเดตราคาครอบคลุม :count คืนแล้ว มีผลทันที',
    'no_rate_plan' => 'ประเภทห้องพักนั้นไม่มีแผนราคา จึงบันทึกราคาไม่ได้',
];
