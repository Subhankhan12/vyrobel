<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => '只有临时预订可以确认——此预订的状态为“:status”。',
    'hold_confirmed' => '临时预订已确认——此预订现为已确认的住宿。',
    'cannot_cancel_closed' => '无法取消该预订——它已结束。',
    'reason_too_long' => '取消原因过长——最多 255 个字符。',
    'cancel_rejected' => '无法取消该预订——预订服务拒绝了此操作。未作任何更改。',
    'cancelled_refunded' => '预订已取消，并已退款 :amount（在免费取消期限内）。所占用的夜晚已释放回日历。',
    'cancelled' => '预订已取消。所占用的夜晚已释放回日历。',
    'cannot_no_show' => '无法将该预订标记为未到店——只有已确认的预订才能标记。',
    'no_show_rejected' => '无法将该预订标记为未到店——预订服务拒绝了此操作。未作任何更改。',
    'marked_no_show' => '已标记为未到店。所占用的夜晚已释放回日历。',
];
