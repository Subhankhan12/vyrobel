<?php

// I18N-01 — pt translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count dia (RetentionPolicy, retention:prune)|[0,*] :count dias (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count dia (platform:prune-activity)|[0,*] :count dias (platform:prune-activity)',
    'rule_guest_documents' => '30 dias após o check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'O inventário em que se baseia (:file) não está instalado neste servidor, por isso as categorias não podem ser contadas aqui.',
    'reason_unreadable' => 'Não foi possível ler o inventário em que se baseia (:file), por isso as categorias não podem ser contadas aqui.',
];
