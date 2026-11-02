<?php

// I18N-01 — th translation of lang/en/php/http/controllers/operator/payout_run_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'engine_disabled' => 'ระบบจ่ายเงินอัตโนมัติปิดอยู่ (PAYOUT_ENGINE_ENABLED) โปรดเปิดใช้งานก่อนเริ่มรอบการจ่ายเงิน',
    'nothing_to_run' => 'ไม่มีรายการให้ดำเนินการ — ไม่มีเจ้าของห้องพักที่มีใบแจ้งยอดพร้อมจ่ายหรือการจ่ายเงินที่ระงับไว้:paused_note',
    'run_complete' => 'รอบการจ่ายเงินเสร็จสิ้น (โหมด “:mode”) — สร้าง :created รายการ · จ่ายแล้ว :paid รายการ · ระงับการจ่าย :held รายการ:paused_note',
    'create_failed' => 'สร้างการจ่ายเงินไม่ได้ — :reason',
    'already_has_payout' => 'ใบแจ้งยอดนี้มีการจ่ายเงินแล้ว — ไม่ได้สร้างรายการใหม่',
    'one_payout_created' => 'สร้างการจ่ายเงิน 1 รายการแล้ว',
    'pay_failed' => 'จ่ายเงินไม่ได้ — :reason',
    'one_payout_paid' => 'ระบุว่าจ่ายแล้ว 1 รายการ',
    'payout_failed_reason' => 'การจ่ายเงินล้มเหลว — :reason',
    'payout_failed_no_reason' => 'การจ่ายเงินล้มเหลว — ช่องทางการจ่ายเงินไม่ได้ระบุเหตุผล',
    'create_all_failed' => 'สร้างการจ่ายเงินไม่ได้ — :reason',
    'no_payout_created_paused' => 'ไม่ได้สร้างการจ่ายเงิน:paused_note — ดำเนินการจ่ายเงินต่อได้ที่หน้าของเจ้าของห้องพัก',
    'nothing_ready' => 'ยังไม่มีรายการพร้อมจ่าย — ไม่มีใบแจ้งยอดที่ปิดงวดแล้วซึ่งมียอดสุทธิที่ต้องจ่ายเป็นบวก:paused_note',
    'no_new_payouts' => 'ไม่มีการจ่ายเงินใหม่ — ใบแจ้งยอดที่พร้อมจ่ายทุกฉบับมีการจ่ายเงินแล้ว:paused_note',
    'payouts_created' => '{1}สร้างการจ่ายเงิน :count รายการแล้ว:paused_note|[0,*]สร้างการจ่ายเงิน :count รายการแล้ว:paused_note',
    'some_not_created' => ':message บางรายการสร้างไม่ได้ — :reason',
    'nothing_paid_paused' => 'ยังไม่ได้จ่ายเงิน:paused_note — ดำเนินการจ่ายเงินต่อได้ที่หน้าของเจ้าของห้องพัก',
    'no_pending_payouts' => 'ไม่มีการจ่ายเงินที่รอดำเนินการ',
    'paid_only' => '{1}ระบุว่าจ่ายแล้ว :count รายการ:paused_note|[0,*]ระบุว่าจ่ายแล้ว :count รายการ:paused_note',
    'failed_only' => '[0,*]ล้มเหลวที่ช่องทางการจ่ายเงิน :count รายการ:paused_note',
    'paid_and_failed' => '{1}ระบุว่าจ่ายแล้ว :count รายการ · ล้มเหลวที่ช่องทางการจ่ายเงิน :failed รายการ:paused_note|[0,*]ระบุว่าจ่ายแล้ว :count รายการ · ล้มเหลวที่ช่องทางการจ่ายเงิน :failed รายการ:paused_note',
    'some_not_attempted' => ':message บางรายการยังไม่ได้ดำเนินการ — :reason',
    'nothing_payable_negative' => 'ไม่มียอดที่ต้องจ่าย — ยอดสุทธิของใบแจ้งยอดนี้ติดลบ จึงยกยอดไปหักกับใบแจ้งยอดฉบับถัดไปของเจ้าของห้องพัก',
    'nothing_payable_zero' => 'ไม่มียอดที่ต้องจ่าย — ยอดสุทธิของใบแจ้งยอดนี้เป็นศูนย์ จึงไม่มียอดค้างจ่าย',
    'workspace_paused_until' => 'พื้นที่ทำงานหยุดชั่วคราว — การจ่ายเงินเป็นแบบอ่านอย่างเดียวจนถึง :date',
    'workspace_paused_for_now' => 'พื้นที่ทำงานหยุดชั่วคราว — การจ่ายเงินเป็นแบบอ่านอย่างเดียวในขณะนี้',
    'paused_note' => ' · :skipped',
    'paused_owners_skipped' => '{1}ข้ามเจ้าของห้องพักที่หยุดการจ่ายเงินชั่วคราว :count คน|[0,*]ข้ามเจ้าของห้องพักที่หยุดการจ่ายเงินชั่วคราว :count คน',
];
