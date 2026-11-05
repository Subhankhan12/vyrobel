<?php

// I18N-01 — sq translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count ditë (RetentionPolicy, retention:prune)|[0,*]:count ditë (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count ditë (platform:prune-activity)|[0,*]:count ditë (platform:prune-activity)',
    'rule_guest_documents' => '30 ditë pas check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Inventari që numërohet (:file) nuk është i vendosur në këtë server, ndaj kategoritë nuk mund të numërohen këtu.',
    'reason_unreadable' => 'Inventari që numërohet (:file) nuk u lexua dot, ndaj kategoritë nuk mund të numërohen këtu.',
];
