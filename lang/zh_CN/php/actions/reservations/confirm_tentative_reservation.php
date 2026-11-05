<?php

// I18N-01 — zh_CN translation of lang/en/php/actions/reservations/confirm_tentative_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_closed' => '无法确认状态为“:status”的预订：该预订已关闭，其占用的夜晚已重新开放销售。如客人再次前来，需要新建预订。',
    'refused_checked_in' => '无法确认已入住的预订：客人已到店，该预订已过确认阶段。',
    'refused_checked_out' => '无法确认已退房的预订：住宿已经结束。',
    'refused_enquiry' => '无法确认询价：只有临时预订可以确认。',
    'refused_other' => '无法确认状态为“:status”的预订：只有临时预订可以确认。',
];
