<?php

// I18N-01 — fr translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count jour (RetentionPolicy, retention:prune)|[0,*] :count jours (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count jour (platform:prune-activity)|[0,*] :count jours (platform:prune-activity)',
    'rule_guest_documents' => '30 jours après le check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'L’inventaire utilisé pour le comptage (:file) n’est pas déployé sur cet hôte : les catégories ne peuvent donc pas être comptées ici.',
    'reason_unreadable' => 'L’inventaire utilisé pour le comptage (:file) n’a pas pu être lu : les catégories ne peuvent donc pas être comptées ici.',
];
