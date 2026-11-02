<?php

// I18N-01 — zh_TW translation of lang/en/php/http/controllers/admin/switch_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'confirm_no_lockout' => '請勾選以確認——目前沒有帳號僅依賴此第三方登入，但在關閉時仍僅依賴它的帳號將無法登入。',
    'confirm_lockout' => '{1}請確認您了解此操作會使 :count 個僅能透過此第三方登入進入的帳號無法登入。|[0,*]請確認您了解此操作會使 :count 個僅能透過此第三方登入進入的帳號無法登入。',
    'now_on' => '「:switch」已開啟。將於下一次請求時生效。',
    'now_off' => '「:switch」已關閉。將於下一次請求時生效。',
];
