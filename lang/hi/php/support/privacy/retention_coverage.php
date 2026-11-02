<?php

// I18N-01 — hi translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count दिन (RetentionPolicy, retention:prune)|[0,*]:count दिन (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count दिन (platform:prune-activity)|[0,*]:count दिन (platform:prune-activity)',
    'rule_guest_documents' => 'चेक-आउट के 30 दिन बाद (guest-documents:purge-expired)',
    'reason_not_deployed' => 'जिस इन्वेंटरी (:file) की गिनती यह करता है, वह इस सर्वर पर मौजूद नहीं है, इसलिए श्रेणियाँ यहाँ गिनी नहीं जा सकतीं।',
    'reason_unreadable' => 'जिस इन्वेंटरी (:file) की गिनती यह करता है, वह पढ़ी नहीं जा सकी, इसलिए श्रेणियाँ यहाँ गिनी नहीं जा सकतीं।',
];
