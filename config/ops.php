<?php

/*
|--------------------------------------------------------------------------
| Operations
|--------------------------------------------------------------------------
|
| ⚠️⚠️ KNOWN-LIMITS L14 — WHY THIS FILE EXISTS. Production caches its config (`config:cache`), and with a cached
| config Laravel NEVER reads `.env` again: an `env()` call outside `config/` returns NULL, always, whatever the
| .env says. 🔬 Measured on the host. The heartbeat URL was read with a raw `env()` in routes/console.php, so the
| external dead-man's-switch could NEVER arm — setting the variable changed nothing.
|
| Reading it HERE means `config:cache` captures the value once at deploy time and `config()` returns it after.
| ⚠️ Setting OPS_HEARTBEAT_PING_URL therefore needs a config rebuild (every deploy already does one) — not
| just an edit of .env.
|
*/

return [

    // ⛔ FOUNDER STEP: a Healthchecks.io / Better Stack / Cronitor URL that expects a ping every minute and
    // alarms when it STOPS. Empty = the switch is not armed — the health board shows that, amber, by name.
    'heartbeat_ping_url' => env('OPS_HEARTBEAT_PING_URL'),

];
