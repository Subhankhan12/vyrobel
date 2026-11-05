<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/operator/reservation_lifecycle_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_tentative_confirmable' => '가예약만 확정할 수 있습니다 — 이 예약의 상태는 “:status”입니다.',
    'hold_confirmed' => '가예약이 확정되었습니다 — 이제 확정된 예약입니다.',
    'cannot_cancel_closed' => '해당 예약은 취소할 수 없습니다 — 이미 종료된 예약입니다.',
    'reason_too_long' => '취소 사유가 너무 깁니다 — 최대 255자까지 입력할 수 있습니다.',
    'cancel_rejected' => '예약을 취소하지 못했습니다 — 예약 서비스에서 거부했습니다. 변경된 내용이 없습니다.',
    'cancelled_refunded' => '예약이 취소되고 :amount 금액이 환불되었습니다(무료 취소 기간 내). 확보된 날짜가 캘린더로 반환되었습니다.',
    'cancelled' => '예약이 취소되었습니다. 확보된 날짜가 캘린더로 반환되었습니다.',
    'cannot_no_show' => '해당 예약은 노쇼로 처리할 수 없습니다 — 확정된 예약만 노쇼로 처리할 수 있습니다.',
    'no_show_rejected' => '노쇼로 처리하지 못했습니다 — 예약 서비스에서 거부했습니다. 변경된 내용이 없습니다.',
    'marked_no_show' => '노쇼로 처리되었습니다. 확보된 날짜가 캘린더로 반환되었습니다.',
];
