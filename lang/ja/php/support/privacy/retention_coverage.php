<?php

// I18N-01 — ja translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count日（RetentionPolicy, retention:prune）|[0,*]:count日（RetentionPolicy, retention:prune）',
    'rule_login_activity' => '{1}:count日（platform:prune-activity）|[0,*]:count日（platform:prune-activity）',
    'rule_guest_documents' => 'チェックアウトから30日後（guest-documents:purge-expired）',
    'reason_not_deployed' => '集計に使うデータ一覧（:file）がこのホストにデプロイされていないため、ここではカテゴリを集計できません。',
    'reason_unreadable' => '集計に使うデータ一覧（:file）を読み込めなかったため、ここではカテゴリを集計できません。',
];
