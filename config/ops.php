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

    /*
    |--------------------------------------------------------------------------
    | The daily backup (VY-12, 2026-11-24)
    |--------------------------------------------------------------------------
    |
    | `backup:run` (scheduled daily) runs ops/backup.sh — the script ops/restore-drill.sh proves.
    |
    | ⚠️⚠️ `passphrase_file` IS A PATH, NEVER THE PASSPHRASE. The archive is encrypted with a passphrase the founder types
    | into a file OUTSIDE the application folder (mode 600), and only its path is put in `.env` as
    | OPS_BACKUP_PASSPHRASE_FILE. The passphrase itself is never in `.env`, never in the cached config, never on a
    | command line and never in a log: the command reads the file and hands it to the script in the process
    | environment. Unset → the scheduled backup does not run and the console's health board says so in red.
    | ⚠️ The founder keeps a SECOND copy of that passphrase off the server: without it no archive can be opened.
    |
    | `offsite_disk` names a filesystem disk (config/filesystems.php) each archive is also copied to. Unset → archives
    | stay on this server only, and the health board says that too (amber).
    |
    | `offsite_pull` (2026-11-25) names an OUTSIDE MACHINE that pulls the newest archive itself (ops/backup-pull.sh over
    | SSH: copied, proven by its checksum, and only then marked). A label, never a path or a secret. Either this or
    | `offsite_disk` counts as an off-server copy; the marker's age decides green or amber either way.
    |
    */
    'backup' => [
        'script' => env('OPS_BACKUP_SCRIPT', base_path('ops/backup.sh')),
        'passphrase_file' => env('OPS_BACKUP_PASSPHRASE_FILE'),
        'dest' => env('OPS_BACKUP_DEST', storage_path('backups')),
        'retain_days' => (int) env('OPS_BACKUP_RETAIN_DAYS', 14),
        'offsite_disk' => env('OPS_BACKUP_OFFSITE_DISK'),
        'offsite_pull' => env('OPS_BACKUP_OFFSITE_PULL'),
    ],

];
