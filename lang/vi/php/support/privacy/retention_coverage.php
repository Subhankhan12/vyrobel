<?php

// I18N-01 — vi translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count ngày (RetentionPolicy, retention:prune)|[0,*]:count ngày (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count ngày (platform:prune-activity)|[0,*]:count ngày (platform:prune-activity)',
    'rule_guest_documents' => '30 ngày sau khi trả phòng (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Danh mục dữ liệu dùng để đếm (:file) chưa được triển khai trên máy chủ này, nên không thể đếm các loại dữ liệu tại đây.',
    'reason_unreadable' => 'Không đọc được danh mục dữ liệu dùng để đếm (:file), nên không thể đếm các loại dữ liệu tại đây.',
];
