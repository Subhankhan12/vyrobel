<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => '请输入有效的 IP 地址或 CIDR 地址段（例如：203.0.113.4 或 203.0.113.0/24）。',
    'ip_already_listed' => ':ip 已在白名单中。',
    'allowlist_enabled_auto_added' => 'IP 白名单已启用。我们还添加了您当前的地址（:ip），以免您将自己锁定在外。',
    'ip_added' => '已将 :ip 添加到白名单。',
    'remove_would_lock_out' => '移除 :ip 会将您锁定在外——这是唯一允许您的地址（:current）访问后台的规则。请先为您的地址添加规则，或移除其他规则。',
    'ip_removed_allowlist_off' => '已移除 :ip——白名单已关闭：任何能正常登录的人都可以访问后台。',
    'ip_removed' => '已将 :ip 从白名单中移除。',
    'session_already_ended' => '该会话已结束。',
    'signed_out_of_device' => '已在 :device 上退出登录。',
    'trusted_device_revoked' => '已取消信任设备。',
    'csv_title' => 'Vyrobel 账号导出',
    'csv_name' => '姓名',
    'csv_email' => '邮箱',
    'csv_locale' => '语言',
    'csv_roles' => '角色',
    'csv_activity' => '活动',
    'csv_when' => '时间',
    'csv_action' => '操作',
    'range_this_year' => '今年',
    'range_custom' => '自定义范围',
    'range_all_time' => '全部时间',
];
