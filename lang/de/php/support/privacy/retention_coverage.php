<?php

// I18N-01 — de translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count Tag (RetentionPolicy, retention:prune)|[0,*] :count Tage (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count Tag (platform:prune-activity)|[0,*] :count Tage (platform:prune-activity)',
    'rule_guest_documents' => '30 Tage nach dem Check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Die Bestandsliste, auf der die Zählung beruht (:file), ist auf diesem Host nicht bereitgestellt, daher können die Kategorien hier nicht gezählt werden.',
    'reason_unreadable' => 'Die Bestandsliste, auf der die Zählung beruht (:file), konnte nicht gelesen werden, daher können die Kategorien hier nicht gezählt werden.',
];
