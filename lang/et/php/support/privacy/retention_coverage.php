<?php

// I18N-01 — et translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count päev (RetentionPolicy, retention:prune)|[0,*]:count päeva (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count päev (platform:prune-activity)|[0,*]:count päeva (platform:prune-activity)',
    'rule_guest_documents' => '30 päeva pärast väljaregistreerimist (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Loendatav andmete inventuur (:file) pole sellel serveril juurutatud, seega ei saa kategooriaid siin kokku lugeda.',
    'reason_unreadable' => 'Loendatav andmete inventuur (:file) pole loetav, seega ei saa kategooriaid siin kokku lugeda.',
];
