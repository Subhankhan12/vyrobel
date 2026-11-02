<?php

// I18N-01 — th translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => 'การชำระเงินด้วยบัตรผ่าน Stripe (โหมดทดสอบ) — จะใช้งานได้เมื่อเปลี่ยนเป็นคีย์โหมดใช้งานจริง หากต้องการรับชำระเงินที่แผนกต้อนรับตอนนี้ ให้บันทึกเงินสดหรือการโอนเงินผ่านธนาคาร',
    'payment_amount_invalid' => 'ป้อนจำนวนเงินที่รับชำระมากกว่าศูนย์',
    'payment_recorded' => 'บันทึกการชำระเงินแล้ว',
    'charge_amount_invalid' => 'ป้อนจำนวนเงินของรายการเรียกเก็บมากกว่าศูนย์',
    'charge_added' => 'เพิ่มรายการเรียกเก็บแล้ว',
    'refund_only_settled' => 'คืนเงินได้เฉพาะการชำระเงินที่เสร็จสมบูรณ์แล้ว',
    'already_fully_refunded' => 'การชำระเงินนั้นคืนเงินครบจำนวนแล้ว',
    'refund_amount_invalid' => 'ป้อนจำนวนเงินคืนมากกว่าศูนย์',
    'refund_exceeds' => 'ปฏิเสธการคืนเงินนี้ — การชำระเงินนั้นคืนเงินได้อีกเพียง :amount :currency',
    'refund_issued' => 'คืนเงินแล้ว',
    'billed_to_account' => 'เรียกเก็บจากบัญชี :account แล้ว ยอดคงเหลือจะยังค้างชำระจนกว่าจะได้รับชำระเงิน',
    'returned_to_guest' => 'ส่งกลับไปยังโฟลิโอของผู้เข้าพักแล้ว',
];
