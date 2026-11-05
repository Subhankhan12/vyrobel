<?php

// I18N-01 — ko translation of lang/en/php/support/privacy/retention_coverage.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'rule_retention_policy' => '{1}:count일(RetentionPolicy, retention:prune)|[0,*]:count일(RetentionPolicy, retention:prune)',
    'rule_login_activity' => '{1}:count일(platform:prune-activity)|[0,*]:count일(platform:prune-activity)',
    'rule_guest_documents' => '체크아웃 후 30일(guest-documents:purge-expired)',
    'reason_not_deployed' => '집계 기준인 데이터 인벤토리(:file)가 이 호스트에 배포되지 않아 이곳에서는 범주를 집계할 수 없습니다.',
    'reason_unreadable' => '집계 기준인 데이터 인벤토리(:file)를 읽을 수 없어 이곳에서는 범주를 집계할 수 없습니다.',
];
