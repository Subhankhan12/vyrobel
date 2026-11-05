<?php

// I18N-01 — ko translation of lang/en/php/actions/reservations/check_in_reservation.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'refused_cancelled' => '취소된 예약은 체크인할 수 없습니다. 예약이 종료되었고 객실이 이미 재판매되었을 수 있습니다. 다시 온 고객은 새로 예약해야 합니다.',
    'refused_no_show' => '노쇼 예약은 체크인할 수 없습니다. 예약이 종료되었고 숙박일은 다시 판매 가능 상태로 풀렸습니다. 이 고객을 객실에 투숙시키려면 프런트에서 “워크인”을 등록하세요 — 예약 전에 잔여 객실을 다시 확인합니다. 노쇼 기록과 이미 부과된 수수료는 고객 기록에 그대로 남습니다.',
    'refused_checked_out' => '체크아웃된 예약은 체크인할 수 없습니다. 출발이 이미 기록되어 처리되었습니다. 체크아웃이 잘못 처리되었다면 먼저 예약에서 “체크아웃 되돌리기”를 사용하세요.',
    'refused_tentative' => '가예약은 체크인할 수 없습니다. 객실은 확보되었지만 확정되지 않았으므로 아직 이 고객에게 숙박을 약속한 상태가 아닙니다. 먼저 이 예약에서 “가예약 확정” 버튼을 누른 다음 체크인하세요. 숙박일은 이미 이 예약이 확보하고 있으므로 확정해도 추가로 차지하는 것이 없으며 잔여 객실 때문에 실패하지 않습니다.',
    'refused_other' => '상태가 “:status”인 예약은 체크인할 수 없습니다.',
];
