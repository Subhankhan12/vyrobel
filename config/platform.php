<?php

/*
|--------------------------------------------------------------------------
| ADMIN 01 · PHASE 3 — PLATFORM CONSOLE: BREAK-GLASS
|--------------------------------------------------------------------------
|
| ⚠️⚠️ SHIPPED DORMANT — and the switch is a LITERAL, deliberately not `env()`. Break-glass turns "someone
| guessed the admin password" into "someone can read a hotel's guests", so it must not go live while the
| production admin's password is still the known default (ADMIN 02: `Hash::check('123456', <admin hash>)` must
| return FALSE first). Turning it on is therefore a REVIEWED, SEPARATE DEPLOY that changes `false` to `true`
| here — never a value somebody edits on the host, and never a runtime setting (L14: production caches config).
|
| While it is false: every break-glass entry point answers 404, the console shows no button, and a leftover
| session key is ignored and cleared — the same dormant pattern the social providers used before their
| credentials existed.
|
*/

return [
    'break_glass' => [
        'enabled' => false,

        // The time box. A session ends by itself at expiry — enforced on every request, not by a cron.
        'minutes' => 30,
    ],
];
