<?php

// I18N-01 — el translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count ημέρα (RetentionPolicy, retention:prune)|[0,*]:count ημέρες (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count ημέρα (platform:prune-activity)|[0,*]:count ημέρες (platform:prune-activity)',
    'rule_guest_documents' => '30 ημέρες μετά το check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Η απογραφή που μετράει (:file) δεν έχει αναπτυχθεί σε αυτόν τον διακομιστή, οπότε οι κατηγορίες δεν μπορούν να μετρηθούν εδώ.',
    'reason_unreadable' => 'Η απογραφή που μετράει (:file) δεν μπόρεσε να διαβαστεί, οπότε οι κατηγορίες δεν μπορούν να μετρηθούν εδώ.',
];
