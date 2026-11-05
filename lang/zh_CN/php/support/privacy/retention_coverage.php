<?php

// I18N-01 — zh_CN translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count 天（RetentionPolicy, retention:prune）|[0,*]:count 天（RetentionPolicy, retention:prune）',
    'rule_login_activity' => '{1}:count 天（platform:prune-activity）|[0,*]:count 天（platform:prune-activity）',
    'rule_guest_documents' => '退房后 30 天（guest-documents:purge-expired）',
    'reason_not_deployed' => '其统计所依据的清单（:file）未部署在此主机上，因此无法在此统计各类别。',
    'reason_unreadable' => '无法读取其统计所依据的清单（:file），因此无法在此统计各类别。',
];
