<?php

// I18N-01 — zh_TW translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count 天（RetentionPolicy, retention:prune）|[0,*]:count 天（RetentionPolicy, retention:prune）',
    'rule_login_activity' => '{1}:count 天（platform:prune-activity）|[0,*]:count 天（platform:prune-activity）',
    'rule_guest_documents' => '退房後 30 天（guest-documents:purge-expired）',
    'reason_not_deployed' => '其統計所依據的資料清冊（:file）未部署在此主機上，因此無法在此統計各個類別。',
    'reason_unreadable' => '無法讀取其統計所依據的資料清冊（:file），因此無法在此統計各個類別。',
];
