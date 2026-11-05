<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/admin/admin_user_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'only_pending_reissued' => '只有待处理的邀请才能重新生成链接。',
    'last_owner_role' => '这是最后一位启用的“平台所有者”。请先将其他人设为“平台所有者”。',
    'role_changed' => ':name 的角色现为“:role”。',
    'cannot_revoke_self' => '您不能收回自己的访问权限。请联系另一位“平台所有者”。',
    'last_owner_revoke' => '这是最后一位启用的“平台所有者”，无法收回其访问权限。',
    'access_revoked' => '{1}已收回 :name 的访问权限；已结束 :count 个会话。|[0,*]已收回 :name 的访问权限；已结束 :count 个会话。',
    'only_revoked_restored' => '只有已被收回访问权限的管理员才能恢复访问权限。',
    'access_restored' => '已恢复 :name 的访问权限。',
    'ended_own_other_sessions' => '{1}已结束您的其他 :count 个会话——当前会话保持登录。|[0,*]已结束您的其他 :count 个会话——当前会话保持登录。',
    'ended_sessions_for' => '{1}已结束 :name 的 :count 个会话。|[0,*]已结束 :name 的 :count 个会话。',
];
