<?php

// I18N-01 — ko translation of lang/en/pdfs/operator-schedule.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'eyebrow' => '팀 근무 일정',
    'week_of' => '주간: :week',
    'doc_title' => '주간 근무표',
    'doc_sub' => ':property · 주간: :week · 생성일: :generated',
    'published' => '게시일: :date',
    'published_by' => '게시일: :date · 게시자: :name',
    'not_published' => '아직 게시되지 않음 — 이번 주 근무표는 팀에 발송되지 않았습니다.',
    'team' => '팀',
    'shifts' => '근무조',
    'hours' => '시간',
    'open' => '빈 근무조',
    'conflicts' => '충돌',
    'no_staff' => '선택한 주에 이 근무표에 배정된 직원이 없습니다.',
    'team_member' => '팀원',
    'cell_conflict' => ':shift / 휴가!',
    'cell_leave' => '휴가',
    'legend' => '<strong>근무표 읽는 법:</strong> 각 칸에는 근무조 이름과 시간이 표시됩니다 · <strong>휴가</strong> = 승인된 휴가 · <strong>—</strong> = 근무 배정 없음 · <strong>“/ 휴가!”</strong> = 승인된 휴가일에 배정된 근무조. 모든 상태를 글자로 표시하므로 흑백으로 인쇄해도 정확하게 보입니다.',
    'shift_hours' => ':early :early_hours · :day :day_hours · :late :late_hours · 근무 시간은 칸마다 표시됩니다.',
    'conflict_count' => '{1}<strong style="color:#B0322A">충돌 :count건</strong> — 승인된 휴가일에 배정된 근무조입니다.|[0,*]<strong style="color:#B0322A">충돌 :count건</strong> — 승인된 휴가일에 배정된 근무조입니다.',
    'open_shifts' => '빈 근무조',
    'all_assigned' => '이 근무표의 모든 근무조가 할당되었습니다 — 이번 주에는 빈 근무조가 없습니다.',
    'any_department' => '모든 부서',
    'time_off' => '휴가',
    'foot_generated' => ':property · 생성일: :generated',
    'foot_week' => '주간 근무 일정 · :week',
    'snapshot_note' => '이 근무표는 :generated 기준 근무표의 스냅숏입니다 — 이후 변경 사항은 이 인쇄본에 표시되지 않습니다. 근무조 교대 요청은 Vyrobel 앱이나 프런트를 통해 하세요.',
];
