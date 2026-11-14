<?php

declare(strict_types=1);

// Mutation set csp-probe — the public checks' "our Content-Security-Policy arrives whole" probe (D-PROD-CSP, 2026-11-14), one mutation per guard.
// Run with `php tests/Mutations/run.php --set=csp-probe`.

$t = 'tests/Feature/PublicChecksSecondLookTest.php';
$s = 'ops/public-checks.sh';

return [
    [
        'id' => 'CP-M1',
        'name' => 'the probe stops asking for object-src (one of the four protections can vanish unseen)',
        'file' => $s,
        'old' => 'CSP_NEEDED="frame-ancestors form-action base-uri object-src"',
        'new' => 'CSP_NEEDED="frame-ancestors form-action base-uri"',
        'tests' => [['file' => $t, 'filter' => 'fails when ANY one of the four directives is missing']],
    ],
    [
        'id' => 'CP-M2',
        'name' => 'a Report-Only policy (which enforces nothing) counts as our policy',
        'file' => $s,
        'old' => "grep -i '^content-security-policy:' \"\$1\" 2>/dev/null | grep -qi -- \"\$d\" || return 1",
        'new' => "grep -i '^content-security-policy' \"\$1\" 2>/dev/null | grep -qi -- \"\$d\" || return 1",
        'tests' => [['file' => $t, 'filter' => 'does not count a Report-Only policy']],
    ],
    [
        'id' => 'CP-M3',
        'name' => 'one directive present is enough (the October failure — a bare upgrade-insecure-requests — passes)',
        'file' => $s,
        'old' => "grep -i '^content-security-policy:' \"\$1\" 2>/dev/null | grep -qi -- \"\$d\" || return 1",
        'new' => "grep -i '^content-security-policy:' \"\$1\" 2>/dev/null | grep -qi -- \"\$d\" || true",
        'tests' => [['file' => $t, 'filter' => 'fails on the October 2026 failure']],
    ],
    [
        'id' => 'CP-M4',
        'name' => 'the function says yes to a headers file that does not exist',
        'file' => $s,
        'old' => "    for d in \$CSP_NEEDED; do\n        grep -i '^content-security-policy:'",
        'new' => "    return 0\n    for d in \$CSP_NEEDED; do\n        grep -i '^content-security-policy:'",
        'tests' => [['file' => $t, 'filter' => 'says no when there is no headers file']],
    ],
];
