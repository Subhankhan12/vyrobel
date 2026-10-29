<?php

// I18N-01 — ar translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{0} :count أيام (RetentionPolicy, retention:prune)|يوم واحد (RetentionPolicy, retention:prune)|يومان (RetentionPolicy, retention:prune)|:count أيام (RetentionPolicy, retention:prune)|:count يومًا (RetentionPolicy, retention:prune)|:count يوم (RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{0} :count أيام (platform:prune-activity)|يوم واحد (platform:prune-activity)|يومان (platform:prune-activity)|:count أيام (platform:prune-activity)|:count يومًا (platform:prune-activity)|:count يوم (platform:prune-activity)',
    'rule_guest_documents' => '30 يومًا بعد تسجيل المغادرة (guest-documents:purge-expired)',
    'reason_not_deployed' => 'قائمة الجرد التي يعتمد عليها العد (:file) غير منشورة على هذا الخادم، لذا لا يمكن عد الفئات هنا.',
    'reason_unreadable' => 'تعذرت قراءة قائمة الجرد التي يعتمد عليها العد (:file)، لذا لا يمكن عد الفئات هنا.',
];
