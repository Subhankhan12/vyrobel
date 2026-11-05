<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/operator/season_action_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'choose_rate_plan' => '요금 플랜을 선택하세요.',
    'name_required' => '시즌 이름을 입력하세요.',
    'name_too_long' => '시즌 이름은 80자 이내로 입력하세요.',
    'choose_first_night' => '첫 숙박일을 선택하세요.',
    'choose_last_night' => '마지막 숙박일을 선택하세요.',
    'last_before_first' => '마지막 숙박일은 첫 숙박일보다 앞설 수 없습니다.',
    'price_required' => '1박 요금을 입력하세요.',
    'read_only' => '워크스페이스가 현재 읽기 전용입니다 — 요금 변경이 일시 중지되었습니다.',
    'price_out_of_range' => '1박 요금은 0보다 크고 1,000,000 이하로 입력하세요.',
    'season_saved' => '시즌이 저장되었습니다. 변경될 범위를 검토한 다음, 적용하여 해당 날짜의 요금을 기록하세요.',
    'already_applied' => '이 시즌은 이미 적용되어 수정할 수 없습니다 — 시즌이 기록한 1박 요금이 기존 요금을 대체했으며, 이전 요금은 어디에도 기록되지 않았습니다. 대신 해당 날짜에 새 시즌을 만드세요.',
    'season_updated' => '시즌이 업데이트되었습니다. 변경될 범위를 검토한 다음, 적용하여 해당 날짜의 요금을 기록하세요.',
    'deleted_after_apply' => '“:season” 시즌이 삭제되었습니다. ⚠️ 이미 적용된 1박 요금은 변경되지 않습니다 — 시즌을 삭제해도 대체된 요금은 복원되지 않습니다. 요금이 잘못되었다면 해당 날짜의 요금을 직접 설정하세요.',
    'deleted_not_applied' => '“:season” 시즌이 삭제되었습니다. 적용되지 않은 시즌이었으므로 변경된 요금은 없습니다.',
    'applied' => ':season 시즌이 적용되었습니다 — :units에 걸쳐 :nights의 요금을 다시 기록했습니다.',
    'nights_count' => '{1}:count박|[0,*]:count박',
    'units_count' => '{1}객실 :count개|[0,*]객실 :count개',
    'overlapping_reapplied' => '{1}겹치는 시즌 :count개가 우선순위에 따라 다시 적용되었습니다.|[0,*]겹치는 시즌 :count개가 우선순위에 따라 다시 적용되었습니다.',
    'pick_unit_to_continue' => '계속하려면 객실을 선택하세요.',
    'stay_nights_unpriced' => '{1}숙박 기간 중 :count박에 아직 요금이 없습니다.|[0,*]숙박 기간 중 :count박에 아직 요금이 없습니다.',
];
