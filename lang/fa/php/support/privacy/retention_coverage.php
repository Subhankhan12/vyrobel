<?php

// I18N-01 — fa translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count روز (RetentionPolicy, retention:prune)|[0,*]:count روز (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count روز (platform:prune-activity)|[0,*]:count روز (platform:prune-activity)',
    'rule_guest_documents' => '30 روز پس از تخلیه (guest-documents:purge-expired)',
    'reason_not_deployed' => 'فهرستی که این شمارش از آن می‌خواند (:file) روی این سرور مستقر نشده است، بنابراین دسته‌ها را نمی‌توان در اینجا شمرد.',
    'reason_unreadable' => 'فهرستی که این شمارش از آن می‌خواند (:file) قابل خواندن نبود، بنابراین دسته‌ها را نمی‌توان در اینجا شمرد.',
];
