<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/operator/owner_manage_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'commission_required' => '수수료를 퍼센트로 입력하세요.',
    'commission_range' => '수수료는 0에서 100 사이의 숫자여야 합니다.',
    'commission_unchanged' => '변경 사항이 없습니다 — :name 소유주의 수수료는 이미 :date부터 :rate입니다.',
    'commission_from_today' => ':name 소유주의 수수료는 오늘부터 :rate입니다 — 적용 중인 계약이 없었습니다.',
    'commission_waiting_cancelled' => ':name 소유주의 수수료는 :rate 그대로 유지됩니다 — :date에 예정된 변경은 적용되지 않습니다.',
    'commission_from_date' => ':name 소유주의 수수료는 :date부터 :rate입니다 — 지금부터 계산되는 예약에 적용됩니다. 이미 계산된 예약은 기존 수수료율을 유지합니다.',
    'payouts_paused' => ':name 소유주의 지급을 일시 중지했습니다 — 재개할 때까지 지급이 나가지 않습니다.',
    'payouts_resumed' => ':name 소유주의 지급을 재개했습니다.',
    'period_start_required' => '기간의 첫날을 선택하세요.',
    'period_end_required' => '기간의 마지막 날을 선택하세요.',
    'period_end_after_start' => '기간 종료일은 시작일과 같거나 그 이후여야 합니다.',
    'period_not_closed' => '이 기간을 마감할 수 없습니다: :reason 마지막으로 마감된 기간 이후에 시작하는 기간을 선택하세요.',
    'period_sealed' => '마감되어 잠긴 정산서는 다시 생성할 수 없습니다.',
    'period_not_closed_other' => '해당 기간의 정산서를 생성할 수 없습니다.',
    'period_closed' => '기간을 마감했습니다 — 정산서 번호 :number 마감, 실지급액 :amount. 아직 고객에게서 수납되지 않은 매출은 이번 정산서에 포함되지 않으며 이후 정산서에 반영됩니다.',
    'note_required' => '먼저 메모를 작성하세요.',
    'note_max' => '메모는 최대 2,000자까지 입력할 수 있습니다.',
    'note_added' => '메모를 추가했습니다.',
    'remove_password_required' => '이 소유주를 보관하려면 비밀번호를 입력하세요.',
    'password_wrong' => '비밀번호가 올바르지 않습니다 — 로그인할 때 사용하는 비밀번호를 입력하세요.',
    'confirm_name_required' => '확인하려면 소유주 이름을 입력하세요.',
    'confirm_name_mismatch' => '입력한 이름이 일치하지 않습니다 — 소유주가 보관되지 않았습니다.',
    'owner_removed' => ':name 소유주를 보관했습니다 — 객실 연결이 해제되었으며, 모든 정산서와 지급 내역은 회계용으로 유지됩니다.',
    'workspace_paused_until' => '워크스페이스가 일시 중지되었습니다 — :date까지 소유주 변경은 읽기 전용입니다.',
    'workspace_paused_for_now' => '워크스페이스가 일시 중지되었습니다 — 현재 소유주 변경은 읽기 전용입니다.',
];
