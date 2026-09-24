<?php

declare(strict_types=1);
use App\Support\Ops\ConfigInventory;

/**
 * ops/config-inventory.php — ⚠️⚠️ WHICH CONFIGURATION VALUES ARE EMPTY, AND WHAT DOES EACH EMPTINESS DO?
 *
 * ⚠️⚠️ WHY THIS EXISTS. Two of this project's worst surprises were a single empty config value each, and
 * BOTH were found by accident rather than by looking:
 *   · `MAIL_MAILER=log` under a higher log level → every email discarded AND not even recorded.
 *   · `OPS_HEARTBEAT_PING_URL` empty → the external dead-man's-switch was never armed, so the alarm that
 *     exists to notice silence was itself silent.
 * ⚠️ Nobody had ever asked the GENERAL question. This asks it.
 *
 * ⚠️⚠️ IT ASKS THE FRAMEWORK, IT DOES NOT MODEL IT. The values come from Laravel's own resolved config tree
 * (`config()->all()`), never from re-parsing `.env` or re-deriving how `env()` layers over defaults.
 * 🔬 A detector that reimplements the rule it checks fails in exactly the places the code does — the
 * mass-assignment detector reported **91 false drops** until it was fixed to ask `isFillable()` instead.
 *
 * ⚠️⚠️ IT NEVER PRINTS A VALUE. Set / empty / unresolved only. An inventory that echoes secrets is worse
 * than no inventory, and this file is designed to be safe to paste into a report.
 *
 * ⚠️ THREE VERDICTS, NEVER TWO:
 *   SET        — a non-empty value is present.
 *   EMPTY      — null, empty string, or empty array. ⚠️ THIS IS THE ONE THAT MATTERS.
 *   UNRESOLVED — the key could not be resolved at all. ⚠️ A COVERAGE GAP, NOT A PASS.
 *
 * ⚠️⚠️ AND EVERY EMPTY IS CLASSIFIED BY WHAT ITS EMPTINESS *DOES*, because that is the actual question:
 *   SILENT — the product keeps working and quietly does less. ⚠️⚠️ THE DANGEROUS CLASS; THE DELIVERABLE.
 *   LOUD   — something throws, refuses, or visibly disables itself. Safe: somebody finds out.
 *   INERT  — genuinely optional; empty is a normal, correct state.
 *   ⚠️ UNCLASSIFIED — nobody has decided. Reported separately and never counted as fine.
 *
 * USAGE (on the host, inside the release):
 *   /opt/alt/php84/usr/bin/php artisan tinker --execute='require "ops/config-inventory.php";'
 *   …or:  ops/config-inventory.php --json
 *
 * EXIT: 0 = ran · 1 = at least one EMPTY+SILENT key · 2 = could not run.
 */

// ⚠️⚠️ ADMIN 01 · PHASE 1 — THIS SCRIPT IS NOW A PRINTER. The classification, the tree walk, the stray-env()
//    scan and the calibration all live in App\Support\Ops\ConfigInventory, which the console's health board
//    ALSO calls. One implementation, two callers — never two copies free to drift.

if (! function_exists('config')) {
    fwrite(STDERR, "cannot run: no Laravel application context\n");
    exit(2);
}

$inv = ConfigInventory::inventory();
$buckets = $inv['buckets'];
$strayEnv = ConfigInventory::strayEnvSites();

// ── REPORT. ⚠️ NO VALUES, EVER — the class never returns one. ──────────────────────────────────────────
$line = str_repeat('-', 86);
printf("\n  %s\n  CONFIG INVENTORY — %d leaves · %d SET · %d EMPTY\n", $line, $inv['leaves'], $inv['set'], $inv['empty']);
foreach (['SILENT', 'UNCLASSIFIED', 'LOUD', 'INERT'] as $class) {
    $rows = $buckets[$class];
    $mark = $class === 'SILENT' ? '⚠️⚠️' : ($class === 'UNCLASSIFIED' ? '⚠️ ' : '   ');
    printf("\n  %s EMPTY + %-12s %d\n", $mark, $class, count($rows));
    foreach ($rows as [$path, $why]) {
        printf("      %-44s %s\n", $path, $class === 'SILENT' || $class === 'UNCLASSIFIED' ? $why : '');
    }
}

$cached = app()->configurationIsCached();
printf("\n  $line\n  ⚠️⚠️ CACHED-CONFIG HAZARD\n      configurationIsCached() = %s\n", $cached ? 'YES' : 'no');
if ($cached) {
    echo "      🔬 Laravel does NOT load .env when the config is cached — measured on this host: a key\n";
    echo "         that IS a line in .env resolved through env() to NULL. So EVERY site below reads NULL\n";
    echo "         no matter what .env says, and ⚠️⚠️ SETTING THE VARIABLE WILL NOT CHANGE THAT.\n";
}
printf("\n  ⚠️ env() called OUTSIDE config/ : %d site(s)%s\n", count($strayEnv), $cached ? '  ← each one reads NULL here' : '');
foreach (array_slice($strayEnv, 0, 12) as $site) {
    echo "      $site\n";
}
if (count($strayEnv) > 12) {
    printf("      … and %d more\n", count($strayEnv) - 12);
}

echo "\n  $line\n  CALIBRATION — known points, checked before any result above is believed\n";
$calFails = 0;
foreach (ConfigInventory::calibration($inv) as [$label, $ok]) {
    printf("      %s %s\n", $ok ? '✓' : '✗', $label);
    $calFails += $ok ? 0 : 1;
}
if ($calFails > 0) {
    echo "\n  ⚠️⚠️ CALIBRATION FAILED — this inventory is not to be trusted. Fix it before reading the result.\n";
    exit(2);
}
echo "      ⚠️ calibration passed: the instrument is right about the answers already known.\n";

$silent = count($buckets['SILENT']);
printf("\n  RESULT: %d EMPTY+SILENT — the class that keeps working while quietly doing less.\n", $silent);
if (count($buckets['UNCLASSIFIED']) > 0) {
    printf("  ⚠️ %d EMPTY keys are UNCLASSIFIED. That is a coverage gap, not a pass.\n", count($buckets['UNCLASSIFIED']));
}
exit($silent > 0 ? 1 : 0);
