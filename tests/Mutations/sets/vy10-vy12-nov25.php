<?php

declare(strict_types=1);

// Mutation set vy10-vy12-nov25 — run with `php tests/Mutations/run.php --set=vy10-vy12-nov25`. Each row: the exact text a guard lives
// in (`old`), what it becomes with the guard removed (`new`), and the test aimed at it. See tests/Mutations/run.php.
//
// ⚠️ DELIBERATELY NOT HERE: removing ops/secrets.sh's terminal check. With it gone, its test would run the helper for real — and the
//    helper's next line connects to PRODUCTION. A guard whose mutation would reach production is proven by reading, not by running.

return [
    [
        'id' => 'VY1012-M1',
        'name' => 'secret mode takes any key',
        'file' => 'ops/host/env-set.php',
        'old' => '        in_array($key, SECRET_KEYS, true) || fail(',
        'new' => '        true || fail(',
        'tests' => [['file' => 'tests/Feature/Vy10Vy12EnvSetToolTest.php', 'filter' => 'refuses a secret key from an argument']],
    ],
    // VY1012-M2 (set mode takes a secret key) RETIRED 2026-11-25: held twice — the per-key value check (`match … default => false`)
    // refuses any key it has no rule for, so lifting the list check alone survives truthfully (measured: NOT CAUGHT, exit 2 still).
    [
        'id' => 'VY1012-M3',
        'name' => 'secret printed',
        'file' => 'ops/host/env-set.php',
        'old' => 'say($secret ? "$key: set — read back from the file (the value is not shown)" : "$key: $value — read back from the file");',
        'new' => 'say("$key: $value — read back from the file");',
        'tests' => [['file' => 'tests/Feature/Vy10Vy12EnvSetToolTest.php', 'filter' => 'never prints it']],
    ],
    [
        'id' => 'VY1012-M4',
        'name' => 'line break accepted',
        'file' => 'ops/host/env-set.php',
        'old' => '    if (str_contains($value, "\n") || str_contains($value, "\r") || str_contains($value, "\0")) {',
        'new' => '    if (false) {',
        'tests' => [['file' => 'tests/Feature/Vy10Vy12EnvSetToolTest.php', 'filter' => 'line break']],
    ],
    [
        'id' => 'VY1012-M5',
        'name' => 'passphrase file allowed inside the app',
        'file' => 'ops/host/env-set.php',
        'old' => "            if (str_contains(\$path.'/', \$inside)) {",
        'new' => '            if (false) {',
        'tests' => [['file' => 'tests/Feature/Vy10Vy12EnvSetToolTest.php', 'filter' => 'refuses a passphrase file inside the application']],
    ],
    [
        'id' => 'VY1012-M6',
        'name' => 'any ping host accepted',
        'file' => 'ops/host/env-set.php',
        'old' => "            if (\$scheme !== 'https' || ! in_array(\$host, \$known, true)) {",
        'new' => '            if (false) {',
        'tests' => [['file' => 'tests/Feature/Vy10Vy12EnvSetToolTest.php', 'filter' => 'known ping service']],
    ],
    [
        'id' => 'VY1012-M7',
        'name' => 'old .env not kept',
        'file' => 'ops/host/env-set.php',
        'old' => '$ok = copy($envPath, $saved) && chmod($saved, 0600)',
        'new' => '$ok = true',
        'tests' => [['file' => 'tests/Feature/Vy10Vy12EnvSetToolTest.php', 'filter' => 'keeps the previous .env']],
    ],
    // VY1012-M8 (unparseable .env written anyway) RETIRED 2026-11-25: held twice — the NEW text must parse too, and it still carries
    // the broken line, so lifting the first check alone survives truthfully (measured: NOT CAUGHT, exit 1 still).
    [
        'id' => 'VY1012-M9',
        'name' => 'pull ignores the backup-time checksum',
        'file' => 'ops/backup-pull.sh',
        'old' => '[ "${WRITTEN}" = "${NOW}" ] || die',
        'new' => 'true || die',
        'tests' => [['file' => 'tests/Feature/Vy12BackupPullTest.php', 'filter' => 'refuses an archive whose checksum']],
    ],
    [
        'id' => 'VY1012-M10',
        'name' => 'pull keeps every copy',
        'file' => 'ops/backup-pull.sh',
        'old' => 'tail -n "+$((KEEP+1))" | while read -r OLD; do rm -f "${OLD}" "${OLD}.sha256"; done',
        'new' => 'tail -n "+$((KEEP+1))" | while read -r OLD; do :; done',
        'tests' => [['file' => 'tests/Feature/Vy12BackupPullTest.php', 'filter' => 'copies kept are capped']],
    ],
    [
        'id' => 'VY1012-M11',
        'name' => 'card ignores a pulled copy',
        'file' => 'app/Support/Ops/BackupStatus.php',
        'old' => '        return self::offsiteDisk() !== null || self::offsitePull() !== null;',
        'new' => '        return self::offsiteDisk() !== null;',
        'tests' => [['file' => 'tests/Feature/Vy12BackupPullTest.php', 'filter' => 'counts a PULLED copy']],
    ],
    [
        'id' => 'VY1012-M12',
        'name' => 'helper prints the secret',
        'file' => 'ops/secrets.sh',
        'old' => '  put_secret secret MAIL_PASSWORD; SECRET=',
        'new' => '  say "${SECRET}"; put_secret secret MAIL_PASSWORD; SECRET=',
        'tests' => [['file' => 'tests/Feature/Vy12BackupPullTest.php', 'filter' => 'never prints, logs or writes the secret']],
    ],
    [
        'id' => 'VY1012-M13',
        'name' => 'drill lists drift apart',
        'file' => 'ops/restore-drill.sh',
        'old' => 'SUM_COLS="payouts.amount_minor owner_statements.net_payable_minor folio_items.amount_minor"',
        'new' => 'SUM_COLS="payouts.amount_minor owner_statements.net_payable_minor"',
        'tests' => [['file' => 'tests/Feature/Vy12BackupPullTest.php', 'filter' => 'THE SAME spot-check']],
    ],
];
