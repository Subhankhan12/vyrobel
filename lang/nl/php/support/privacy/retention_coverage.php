<?php

// I18N-01 — nl translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count dag (RetentionPolicy, retention:prune)|[0,*] :count dagen (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count dag (platform:prune-activity)|[0,*] :count dagen (platform:prune-activity)',
    'rule_guest_documents' => '30 dagen na check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Het overzicht dat wordt geteld (:file) staat niet op deze host, dus de categorieën kunnen hier niet worden geteld.',
    'reason_unreadable' => 'Het overzicht dat wordt geteld (:file) kon niet worden gelezen, dus de categorieën kunnen hier niet worden geteld.',
];
