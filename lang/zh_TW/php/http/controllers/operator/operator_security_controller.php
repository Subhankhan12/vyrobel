<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/operator/operator_security_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'ip_invalid' => '請輸入有效的 IP 位址或 CIDR 區段（例如：203.0.113.4 或 203.0.113.0/24）。',
    'ip_already_listed' => ':ip 已在允許清單中。',
    'allowlist_enabled_auto_added' => '已啟用 IP 允許清單。我們也已新增您目前的位址（:ip），以免您自己被鎖在外面。',
    'ip_added' => '已將 :ip 新增至允許清單。',
    'remove_would_lock_out' => '移除 :ip 會讓您無法存取——它是唯一讓您的位址（:current）能進入後台的規則。請先為您的位址新增一條規則，或移除其他規則。',
    'ip_removed_allowlist_off' => '已移除 :ip——允許清單已關閉：任何能成功登入的人都可以進入後台。',
    'ip_removed' => '已從允許清單移除 :ip。',
    'session_already_ended' => '該工作階段已結束。',
    'signed_out_of_device' => '已登出 :device。',
    'trusted_device_revoked' => '已取消信任裝置。',
    'csv_title' => 'Vyrobel 帳號匯出',
    'csv_name' => '姓名',
    'csv_email' => '電子郵件',
    'csv_locale' => '語言',
    'csv_roles' => '角色',
    'csv_activity' => '活動',
    'csv_when' => '時間',
    'csv_action' => '操作',
    'range_this_year' => '今年',
    'range_custom' => '自訂範圍',
    'range_all_time' => '全部期間',
];
