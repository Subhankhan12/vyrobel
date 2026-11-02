<?php

// I18N-01 — bg translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count ден (RetentionPolicy, retention:prune)|[0,*]:count дни (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count ден (platform:prune-activity)|[0,*]:count дни (platform:prune-activity)',
    'rule_guest_documents' => '30 дни след напускането (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Описът, по който се броят категориите (:file), не е наличен на този сървър, затова тук те не могат да бъдат преброени.',
    'reason_unreadable' => 'Описът, по който се броят категориите (:file), не може да бъде прочетен, затова тук те не могат да бъдат преброени.',
];
