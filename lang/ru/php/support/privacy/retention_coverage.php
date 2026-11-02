<?php

// I18N-01 — ru translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count день (RetentionPolicy, retention:prune)|:count дня (RetentionPolicy, retention:prune)|:count дней (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count день (platform:prune-activity)|:count дня (platform:prune-activity)|:count дней (platform:prune-activity)',
    'rule_guest_documents' => '30 дней после выезда (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Перечень, по которому ведётся подсчёт (:file), не развёрнут на этом сервере, поэтому категории здесь посчитать нельзя.',
    'reason_unreadable' => 'Перечень, по которому ведётся подсчёт (:file), не удалось прочитать, поэтому категории здесь посчитать нельзя.',
];
