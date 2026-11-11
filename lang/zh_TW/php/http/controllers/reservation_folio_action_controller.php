<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => '信用卡付款透過 Stripe 處理（測試模式）——待正式金鑰啟用後才可使用。目前如要在櫃檯收款，請記錄現金或銀行轉帳。',
    'payment_amount_invalid' => '請輸入大於零的付款金額。',
    'payment_recorded' => '付款已記錄。',
    'charge_amount_invalid' => '請輸入大於零的消費金額。',
    'charge_added' => '已新增消費。',
    'refund_only_settled' => '只有已完成收款的付款才能退款。',
    'already_fully_refunded' => '此筆付款已全額退款。',
    'refund_amount_invalid' => '請輸入大於零的退款金額。',
    'refund_exceeds' => '已拒絕此次退款——該筆付款僅剩 :amount :currency 可退款。',
    'refund_issued' => '退款已辦理。',
    'billed_to_account' => '已記入帳戶 :account。在對方付款前，餘額仍為待付金額。',
    'returned_to_guest' => '已退回房客的帳單。',
    'hold_released' => '預先授權已釋放——房客的信用卡已恢復可用額度，未扣款。',
];
