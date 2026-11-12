<?php

// I18N-01 — ko translation of lang/en/php/services/unit_owner_assignment_service.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'owner_not_active' => ':owner에게는 객실을 배정할 수 없습니다 — 아직 초대를 수락하지 않았거나 보관되었습니다. 활성 상태의 소유주에게만 객실을 배정할 수 있습니다.',
    'unit_on_pending_invitation' => '“:unit” 객실은 아직 수락되지 않은 소유주 초대에 포함되어 있습니다. 초대가 수락되면 해당 소유주의 객실이 됩니다 — 다른 사람에게 배정하려면 먼저 초대를 취소하세요.',
    'unit_already_owned' => '“:unit” 객실은 이미 :owner 소유이므로 여기에서 다른 사람에게 배정할 수 없습니다. 소유주가 없는 객실만 배정할 수 있습니다.',
    'unit_owned_by_another' => '다른 소유주',
    'date_invalid' => '객실을 배정할 올바른 날짜를 선택하세요.',
    'date_in_past' => '오늘 또는 이후 날짜를 선택하세요. 이미 지난 날짜를 선택하면 이미 계산된 수익이 바뀌게 됩니다.',
    'date_too_far' => '앞으로 :days일 이내의 날짜를 선택하세요.',
    'no_agreement' => ':owner에게는 :date에 “:unit” 객실에 적용되는 계약이 없습니다. 먼저 :date부터 이 객실에 적용되는 계약을 :owner에게 만든 다음 객실을 배정하세요.',
    'stay_across_date' => '고객이 “:unit” 객실에서 :from부터 :to까지 숙박하며, 이 숙박은 :date에 걸쳐 있습니다. 한 숙박이 운영사와 :owner 사이에 나뉘지 않도록 :to 또는 그 이후 날짜를 선택하세요.',
    'future_stays_with_another_owner' => '“:unit” 객실에는 :date부터 시작하는 숙박 중 이미 다른 소유주(:owners) 기준으로 계산된 숙박이 있습니다. 그 숙박은 해당 소유주 몫으로 남으므로 그날부터는 객실을 배정할 수 없습니다.',
];
