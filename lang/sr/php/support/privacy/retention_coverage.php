<?php

// I18N-01 — sr translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count дан (RetentionPolicy, retention:prune)|:count дана (RetentionPolicy, retention:prune)|:count дана (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count дан (platform:prune-activity)|:count дана (platform:prune-activity)|:count дана (platform:prune-activity)',
    'rule_guest_documents' => '30 дана након одјаве госта (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Попис (:file) на основу којег се броји није постављен на овом серверу, па се категорије овде не могу пребројати.',
    'reason_unreadable' => 'Попис (:file) на основу којег се броји није могао да се прочита, па се категорије овде не могу пребројати.',
];
