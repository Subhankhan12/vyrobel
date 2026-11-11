<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/reservation_folio_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'card_not_live' => '银行卡付款通过 Stripe 处理（测试模式）——将在切换正式密钥后可用。如需立即在前台收款，请登记现金或银行转账。',
    'payment_amount_invalid' => '请输入大于零的付款金额。',
    'payment_recorded' => '收款已登记。',
    'charge_amount_invalid' => '请输入大于零的消费金额。',
    'charge_added' => '消费已添加。',
    'refund_only_settled' => '只有已结算的付款才能退款。',
    'already_fully_refunded' => '该付款已全额退款。',
    'refund_amount_invalid' => '请输入大于零的退款金额。',
    'refund_exceeds' => '退款被拒绝——该付款仅剩 :amount :currency 可退。',
    'refund_issued' => '退款已发放。',
    'billed_to_account' => '已记入 :account。在对方付款之前，余额仍为欠款。',
    'returned_to_guest' => '已退回客人账单。',
    'hold_released' => '预授权已释放——客人的银行卡已被释放，未产生任何扣款。',
];
