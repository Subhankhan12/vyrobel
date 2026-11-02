<?php

// I18N-01 — it translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count giorno (RetentionPolicy, retention:prune)|[0,*] :count giorni (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count giorno (platform:prune-activity)|[0,*] :count giorni (platform:prune-activity)',
    'rule_guest_documents' => '30 giorni dopo il check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'L’inventario su cui si basa il conteggio (:file) non è presente su questo host, quindi qui non è possibile contare le categorie.',
    'reason_unreadable' => 'Non è stato possibile leggere l’inventario su cui si basa il conteggio (:file), quindi qui non è possibile contare le categorie.',
];
