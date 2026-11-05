<?php

// I18N-01 — ko translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => '대기 중인 초대만 다시 발급할 수 있습니다.',
    'last_owner_role' => '마지막 활성 “총괄 관리자”입니다. 먼저 다른 사람을 “총괄 관리자”로 지정하세요.',
    'role_changed' => '이제 :name의 역할은 :role입니다.',
    'cannot_revoke_self' => '자신의 액세스는 회수할 수 없습니다. 다른 “총괄 관리자”에게 요청하세요.',
    'last_owner_revoke' => '마지막 활성 “총괄 관리자”이므로 액세스를 회수할 수 없습니다.',
    'access_revoked' => '{1}:name의 액세스가 회수되었습니다. 세션 :count개가 종료되었습니다.|[0,*]:name의 액세스가 회수되었습니다. 세션 :count개가 종료되었습니다.',
    'only_revoked_restored' => '액세스가 회수된 관리자만 복원할 수 있습니다.',
    'access_restored' => ':name의 액세스가 복원되었습니다.',
    'ended_own_other_sessions' => '{1}다른 세션 :count개를 종료했습니다 — 현재 세션은 로그인 상태로 유지됩니다.|[0,*]다른 세션 :count개를 종료했습니다 — 현재 세션은 로그인 상태로 유지됩니다.',
    'ended_sessions_for' => '{1}:name의 세션 :count개를 종료했습니다.|[0,*]:name의 세션 :count개를 종료했습니다.',
];
