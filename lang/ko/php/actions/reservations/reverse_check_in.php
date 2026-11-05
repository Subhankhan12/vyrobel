<?php

// I18N-01 — ko translation of lang/en/php/actions/reservations/reverse_check_in.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'reversible_status' => '숙박이 확정됨 상태로 돌아가며, 객실은 같은 날짜에 계속 확보됩니다.',
    'history_kept' => '체크인 기록 자체는 예약 내역에 남습니다. 실제로 있었던 일이므로 기록에도 그대로 남고, 되돌린 내역이 그 옆에 추가됩니다.',
    'charges_kept' => '{1}체크인 이후 반영된 요금 항목 :count개(합계 :amount)는 폴리오에 그대로 남습니다. 도착을 되돌려도 사용한 금액은 취소되지 않습니다 — 이 항목도 실수였다면 폴리오에서 제거하세요.|[0,*]체크인 이후 반영된 요금 항목 :count개(합계 :amount)는 폴리오에 그대로 남습니다. 도착을 되돌려도 사용한 금액은 취소되지 않습니다 — 이 항목도 실수였다면 폴리오에서 제거하세요.',
    'online_check_in_kept' => '고객이 직접 한 온라인 체크인은 완료 상태로 유지됩니다. 고객이 실제로 작성했으므로 다시 작성하도록 요청하지 않습니다.',
    'already_reversed' => '이 체크인은 조금 전에 이미 되돌려졌습니다 — 숙박이 확정됨 상태로 돌아갔습니다.',
    'refused_not_checked_in' => '체크인 완료 상태인 숙박만 체크인을 되돌릴 수 있습니다. 이 숙박은 “:status” 상태입니다.',
];
