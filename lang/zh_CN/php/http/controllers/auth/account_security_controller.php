<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/auth/account_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'login_approval_unavailable' => '暂时无法开启登录确认：Vyrobel 目前未发送邮件，因此确认链接无法送达，新设备也将无法登录。',
    'login_approval_on' => '登录确认已开启——新设备需等待您允许后才能登录。',
    'login_approval_off' => '登录确认已关闭。',
    'approval_no_longer_waiting' => '该登录已不再等待处理——它已被处理或已过期。',
    'approval_approved' => '已允许——该设备现在可以完成登录。',
    'approval_denied' => '已拒绝——该登录无法继续。如果不是您本人的操作，请修改密码。',
    'login_alerts_on' => '登录提醒已开启——新设备登录时会显示在您的通知铃铛中。',
    'login_alerts_off' => '登录提醒已关闭。',
    'session_timeout_off' => '会话超时已关闭——与之前一样，无操作时您仍会保持登录 :duration。',
    'session_timeout_on' => '无操作 :duration 后，您将自动退出登录。',
];
