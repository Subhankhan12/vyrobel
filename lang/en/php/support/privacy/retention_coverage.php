<?php

// app/Support/Privacy/RetentionCoverage.php — the platform console's retention meter: what the code prunes, and why it cannot count.

return [
    // each rule names the code that enforces it (class / command names stay as they are)
    'rule_retention_policy' => '{1} :count days (RetentionPolicy, retention:prune)|[0,*] :count days (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count days (platform:prune-activity)|[0,*] :count days (platform:prune-activity)',
    'rule_guest_documents' => '30 days after check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'The inventory it counts (:file) is not deployed on this host, so the categories cannot be counted here.',
    'reason_unreadable' => 'The inventory it counts (:file) could not be read, so the categories cannot be counted here.',
];
