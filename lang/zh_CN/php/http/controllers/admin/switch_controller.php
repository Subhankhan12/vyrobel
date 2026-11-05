<?php

// I18N-01 — zh_CN translation of lang/en/php/http/controllers/admin/switch_controller.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)

return [
    'confirm_no_lockout' => '请勾选此框以确认——目前没有账号只能通过此第三方登录方式登录，但如果关闭时有账号只能通过它登录，该账号将被锁定。',
    'confirm_lockout' => '{1}请确认您了解：此操作将锁定 :count 个只能通过此第三方登录方式登录的账号。|[0,*]请确认您了解：此操作将锁定 :count 个只能通过此第三方登录方式登录的账号。',
    'now_on' => '“:switch”现已开启。将在下一次请求时生效。',
    'now_off' => '“:switch”现已关闭。将在下一次请求时生效。',
];
