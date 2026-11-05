<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/housekeeping_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'cannot_mark' => ':unit 객실은 현재 :status 상태이므로 :target 상태로 표시할 수 없습니다.',
    'marked' => ':unit 객실을 :target 상태로 표시했습니다.',
    'assigned' => ':name 직원을 :unit 객실에 할당했습니다.',
    'unassigned' => ':unit 객실의 할당을 해제했습니다.',
    'rooms_unassigned' => '{1}객실 :count개의 할당을 해제했습니다.|[0,*]객실 :count개의 할당을 해제했습니다.',
    'nothing_to_undo' => '되돌릴 항목이 없습니다.',
    'no_housekeepers' => '근무표에 할당할 하우스키퍼가 없습니다.',
    'rooms_assigned' => '{1}객실 :count개를 할당했습니다 · 업무량을 균등하게 배분했습니다.|[0,*]객실 :count개를 할당했습니다 · 업무량을 균등하게 배분했습니다.',
    'nothing_to_assign' => '할당할 객실이 없습니다 — 모든 객실에 이미 하우스키퍼가 있습니다.',
    'photo_added' => '사진을 추가했습니다.',
    'photo_removed' => '사진을 삭제했습니다.',
    'workspace_paused' => '워크스페이스가 일시 중지되었습니다(읽기 전용) — 재개할 때까지 하우스키핑을 변경할 수 없습니다.',
    'marked_in_progress' => '청소 중',
    'marked_clean' => '청소 완료',
    'marked_inspected' => '점검 완료',
    'is_pending' => '대기 중',
    'is_in_progress' => '청소 중',
    'is_clean' => '청소 완료',
    'is_inspected' => '점검 완료',
];
