<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => '只有暫定保留的訂房可以確認——此訂房目前狀態為「:status」。',
    'hold_confirmed' => '暫定保留已確認——此訂房現為已確認的住宿。',
    'cannot_cancel_closed' => '此訂房無法取消——訂房已結束。',
    'reason_too_long' => '取消原因過長——最多 255 個字元。',
    'cancel_rejected' => '此訂房無法取消——訂房服務拒絕了此操作。未做任何變更。',
    'cancelled_refunded' => '訂房已取消，並已退款 :amount（在免費取消期限內）。原保留的晚數已釋回日曆。',
    'cancelled' => '訂房已取消。原保留的晚數已釋回日曆。',
    'cannot_no_show' => '此訂房無法標記為未抵達——只有已確認的訂房才可以。',
    'no_show_rejected' => '此訂房無法標記為未抵達——訂房服務拒絕了此操作。未做任何變更。',
    'marked_no_show' => '已標記為未抵達。原保留的晚數已釋回日曆。',
];
