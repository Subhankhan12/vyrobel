<?php

// I18N-01 — ro translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count zi (RetentionPolicy, retention:prune)|:count zile (RetentionPolicy, retention:prune)|:count de zile (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count zi (platform:prune-activity)|:count zile (platform:prune-activity)|:count de zile (platform:prune-activity)',
    'rule_guest_documents' => '30 de zile după check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Inventarul pe care îl numără (:file) nu este instalat pe acest server, așa că aici categoriile nu pot fi numărate.',
    'reason_unreadable' => 'Inventarul pe care îl numără (:file) nu a putut fi citit, așa că aici categoriile nu pot fi numărate.',
];
