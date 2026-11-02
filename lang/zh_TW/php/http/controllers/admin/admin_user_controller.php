<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => '只有待處理的邀請可以重新產生連結。',
    'last_owner_role' => '這是最後一位啟用中的「平台擁有者」。請先指定其他人為「平台擁有者」。',
    'role_changed' => ':name 現在是「:role」。',
    'cannot_revoke_self' => '您無法收回自己的存取權。請由另一位「平台擁有者」處理。',
    'last_owner_revoke' => '這是最後一位啟用中的「平台擁有者」，無法收回其存取權。',
    'access_revoked' => '{1}已收回 :name 的存取權；已結束 :count 個工作階段。|[0,*]已收回 :name 的存取權；已結束 :count 個工作階段。',
    'only_revoked_restored' => '只有已收回存取權的管理員可以恢復存取權。',
    'access_restored' => '已恢復 :name 的存取權。',
    'ended_own_other_sessions' => '{1}已結束您的其他 :count 個工作階段——目前這個仍保持登入。|[0,*]已結束您的其他 :count 個工作階段——目前這個仍保持登入。',
    'ended_sessions_for' => '{1}已結束 :name 的 :count 個工作階段。|[0,*]已結束 :name 的 :count 個工作階段。',
];
