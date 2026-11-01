<?php

// I18N-01 — tr translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1} :count gün (RetentionPolicy, retention:prune)|[0,*] :count gün (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1} :count gün (platform:prune-activity)|[0,*] :count gün (platform:prune-activity)',
    'rule_guest_documents' => 'Çıkış tarihinden 30 gün sonra (guest-documents:purge-expired)',
    'reason_not_deployed' => 'Saydığı envanter (:file) bu sunucuda yüklü değil; bu nedenle kategoriler burada sayılamaz.',
    'reason_unreadable' => 'Saydığı envanter (:file) okunamadı; bu nedenle kategoriler burada sayılamaz.',
];
