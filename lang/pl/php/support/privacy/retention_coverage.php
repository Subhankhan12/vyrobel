<?php

// I18N-01 — pl translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count dzień (RetentionPolicy, retention:prune)|:count dni (RetentionPolicy, retention:prune)|:count dni (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count dzień (platform:prune-activity)|:count dni (platform:prune-activity)|:count dni (platform:prune-activity)',
    'rule_guest_documents' => '30 dni po wymeldowaniu (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Wykaz, na podstawie którego liczone są kategorie (:file), nie jest wdrożony na tym serwerze, więc nie można ich tu policzyć.',
    'reason_unreadable' => 'Nie udało się odczytać wykazu, na podstawie którego liczone są kategorie (:file), więc nie można ich tu policzyć.',
];
