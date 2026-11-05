<?php

// I18N-01 — id translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count hari (RetentionPolicy, retention:prune)|[0,*]:count hari (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count hari (platform:prune-activity)|[0,*]:count hari (platform:prune-activity)',
    'rule_guest_documents' => '30 hari setelah check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Inventaris data yang dihitungnya (:file) tidak terpasang di host ini, sehingga kategorinya tidak dapat dihitung di sini.',
    'reason_unreadable' => 'Inventaris data yang dihitungnya (:file) tidak dapat dibaca, sehingga kategorinya tidak dapat dihitung di sini.',
];
