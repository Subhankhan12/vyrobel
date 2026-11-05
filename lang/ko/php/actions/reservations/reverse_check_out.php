<?php

// I18N-01 — ko translation of lang/en/php/actions/reservations/reverse_check_out.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '숙박이 체크인 완료 상태로 돌아갑니다.',
    'pending_tasks_removed' => '{1}아직 시작되지 않은 퇴실 청소 작업 :count건이 제거됩니다.|[0,*]아직 시작되지 않은 퇴실 청소 작업 :count건이 제거됩니다.',
    'started_tasks_kept' => '{1}이미 시작된 청소 작업 :count건은 그대로 둡니다 — 누군가 시작한 작업을 취소할지는 하우스키핑이 결정할 일이며, 되돌리기로 처리하지 않습니다.|[0,*]이미 시작된 청소 작업 :count건은 그대로 둡니다 — 누군가 시작한 작업을 취소할지는 하우스키핑이 결정할 일이며, 되돌리기로 처리하지 않습니다.',
    'unit_stays_dirty' => '객실은 청소 필요 상태로 유지됩니다. 고객이 객실로 돌아왔으므로 “청소 완료”가 사실이 아닐 수 있습니다.',
    'notifications_stay_delivered' => '직원과 고객에게 이미 전달된 알림은 그대로 유지됩니다.',
    'review_unknown' => '이 숙박에 리뷰 요청이 발송되었는지에 대한 기록이 없습니다. 이 제품이 리뷰 요청을 기록하기 시작하기 전에 체크아웃했기 때문입니다. 발송되었을 수도 있고 발송되지 않았을 수도 있습니다.',
    'review_sent_on' => ':date에 고객에게 리뷰 요청 이메일이 발송되었습니다. 회수할 수 없습니다.',
    'review_sent' => '고객에게 리뷰 요청 이메일이 발송되었습니다. 회수할 수 없습니다.',
    'review_suppressed_by_operator' => '리뷰 요청이 발송되지 않았습니다 — 이 숙소는 리뷰 요청 이메일이 꺼져 있습니다.',
    'review_suppressed_by_guest' => '리뷰 요청이 발송되지 않았습니다 — 고객이 리뷰 이메일을 받지 않겠다고 요청했습니다.',
    'review_not_sent' => '리뷰 요청이 발송되지 않았습니다 — 연락처 주소가 없었거나 발송에 실패했습니다.',
    'refused_not_checked_out' => '체크아웃 완료 상태인 숙박만 체크아웃을 되돌릴 수 있습니다. 이 숙박은 “:status” 상태입니다.',
];
