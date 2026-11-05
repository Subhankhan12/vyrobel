<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => '올바른 IP 주소 또는 CIDR 블록을 입력하세요(예: 203.0.113.4 또는 203.0.113.0/24).',
    'ip_already_listed' => ':ip 주소는 이미 허용 목록에 있습니다.',
    'allowlist_enabled_auto_added' => 'IP 허용 목록이 활성화되었습니다. 접속이 차단되지 않도록 현재 주소(:ip)도 추가했습니다.',
    'ip_added' => ':ip 주소를 허용 목록에 추가했습니다.',
    'remove_would_lock_out' => ':ip 주소를 제거하면 접속이 차단됩니다 — 현재 주소(:current)로 백오피스에 접속할 수 있게 하는 유일한 규칙입니다. 먼저 현재 주소에 대한 규칙을 추가하거나 다른 규칙을 제거하세요.',
    'ip_removed_allowlist_off' => ':ip 주소를 제거했습니다 — 허용 목록이 꺼졌습니다. 유효한 로그인 정보가 있으면 누구나 백오피스에 접속할 수 있습니다.',
    'ip_removed' => ':ip 주소를 허용 목록에서 제거했습니다.',
    'session_already_ended' => '이 세션은 이미 종료되었습니다.',
    'signed_out_of_device' => ':device 기기에서 로그아웃했습니다.',
    'trusted_device_revoked' => '신뢰할 수 있는 기기를 회수했습니다.',
    'csv_title' => 'Vyrobel 계정 내보내기',
    'csv_name' => '이름',
    'csv_email' => '이메일',
    'csv_locale' => '언어',
    'csv_roles' => '역할',
    'csv_activity' => '활동',
    'csv_when' => '일시',
    'csv_action' => '작업',
    'range_this_year' => '올해',
    'range_custom' => '사용자 지정 기간',
    'range_all_time' => '전체 기간',
];
