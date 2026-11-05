<?php

// I18N-01 — ko translation of lang/en/admin/switches/index.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'title' => '킬 스위치',
    'subtitle' => '배포 없이 신규 가입이나 특정 로그인 서비스를 통한 로그인을 중지합니다. 모든 변경은 사유와 함께 감사 로그에 기록됩니다.',
    'explainer' => '여기서 누군가 끄지 않는 한 모든 스위치는 <strong>켜짐</strong> 상태이며, 배포 직후에도 마찬가지입니다. 어떤 스위치도 이 콘솔에는 영향을 주지 않습니다. 로그인 서비스의 <strong>로그인</strong>을 끄면 그 서비스가 유일한 로그인 수단인 사용자는 모두 로그인할 수 없게 되므로, 이런 스위치는 해당 계정 수를 표시하고 두 번 확인합니다.',
    'state_on' => '켜짐',
    'state_off' => '꺼짐',
    'last_changed_by' => '마지막 변경: :time UTC · 변경자: :name.',
    'last_changed' => '마지막 변경: :time UTC.',
    'never_changed' => '변경된 적 없음 — 기본값은 켜짐입니다.',
    'no_account_relies' => '현재 이 로그인 서비스만으로 로그인하는 계정은 없습니다. 모든 계정에 비밀번호나 다른 로그인 수단이 있습니다.',
    'would_lock_out' => '{1}이 스위치를 끄면 계정 <strong>:count</strong>개가 로그인할 수 없게 됩니다 — 비밀번호가 없고 이 로그인 서비스가 유일한 로그인 수단입니다.|[0,*]이 스위치를 끄면 계정 <strong>:count</strong>개가 로그인할 수 없게 됩니다 — 비밀번호가 없고 이 로그인 서비스가 유일한 로그인 수단입니다.',
    'reason' => '사유',
    'why_turn_off' => '이 스위치를 끄는 이유',
    'why_turn_on' => '이 스위치를 켜는 이유',
    'confirm_lockout_zero' => '다시 켜질 때까지 이 방법으로만 로그인하는 사용자는 모두 로그인할 수 없게 된다는 것을 이해했습니다.',
    'confirm_lockout' => '{1}다시 켜질 때까지 계정 :count개가 로그인할 수 없게 된다는 것을 이해했습니다.|[0,*]다시 켜질 때까지 계정 :count개가 로그인할 수 없게 된다는 것을 이해했습니다.',
    'turn_off' => '끄기',
    'turn_on' => '켜기',
];
