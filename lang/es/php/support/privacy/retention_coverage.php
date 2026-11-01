<?php

// I18N-01 — es translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count día (RetentionPolicy, retention:prune)|[0,*] :count días (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count día (platform:prune-activity)|[0,*] :count días (platform:prune-activity)',
    'rule_guest_documents' => '30 días después del check-out (guest-documents:purge-expired)',
    'reason_not_deployed' => 'El inventario en el que se basa el recuento (:file) no está desplegado en este servidor, así que aquí no se pueden contar las categorías.',
    'reason_unreadable' => 'No se pudo leer el inventario en el que se basa el recuento (:file), así que aquí no se pueden contar las categorías.',
];
