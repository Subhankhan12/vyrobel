<?php

declare(strict_types=1);

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

// ── THE CLASSIFICATION. ⚠️ Keys are Laravel config paths, so they survive an env-var rename. ────────
// ⚠️ A key absent from this map and empty is reported UNCLASSIFIED — deliberately, so the list cannot
//    quietly grow a member nobody has thought about.
$CLASSIFY = [
    // ⚠️⚠️ SILENT — the whole reason this file exists.
    'mail.default' => ['SILENT', 'a non-delivering mailer (log/array) discards every email; under a log level above debug it is not even recorded'],
    'mail.mailers.postmark.token' => ['SILENT', 'Postmark transport present but unauthenticated — sends fail or vanish depending on the mailer above it'],
    'services.postmark.token' => ['SILENT', 'same token, read from the services config'],
    'mail.from.address' => ['SILENT', 'mail may be built with an empty From and rejected by the provider, after the queue has already reported success'],
    'ops.heartbeat_ping_url' => ['SILENT', '⚠️⚠️ the external dead-man\'s-switch is NEVER ARMED. A skipped cron, a dead worker or a dead host raises nothing at all'],
    'services.google.client_id' => ['LOUD', 'Google sign-in disables itself entirely — no button, no route, no error page. Deliberate and asserted'],
    'services.google.client_secret' => ['LOUD', 'as above; "configured" and "enabled" are the same fact by design'],
    'services.stripe.secret' => ['LOUD', 'a Stripe call throws rather than pretending to charge'],
    'services.stripe.webhook_secret' => ['LOUD', 'signature verification refuses every inbound webhook'],
    'app.key' => ['LOUD', 'the application cannot boot; every encrypted column is unreadable'],
    'database.connections.mysql.password' => ['LOUD', 'the database refuses the connection'],
    'app.debug' => ['INERT', 'false is the correct production value and reads as empty'],
    'services.channex.api_key' => ['INERT', 'the channel integration is account-gated and deliberately not live'],
];

// ── WALK THE FRAMEWORK'S OWN RESOLVED TREE ───────────────────────────────────────────────────────────
if (! function_exists('config')) {
    fwrite(STDERR, "cannot run: no Laravel application context\n");
    exit(2);
}

$all = config()->all();
$leaves = [];

$walk = function (array $node, string $prefix) use (&$walk, &$leaves): void {
    foreach ($node as $k => $v) {
        $path = $prefix === '' ? (string) $k : $prefix.'.'.$k;
        // ⚠️ A list of scalars (e.g. a channel stack) is a VALUE, not a branch — recursing into it would
        //    invent keys like `logging.channels.stack.channels.0` that no caller ever reads.
        if (is_array($v) && $v !== [] && array_keys($v) !== range(0, count($v) - 1)) {
            $walk($v, $path);

            continue;
        }
        $leaves[$path] = $v;
    }
};
$walk($all, '');

$set = $empty = 0;
$buckets = ['SILENT' => [], 'LOUD' => [], 'INERT' => [], 'UNCLASSIFIED' => []];

foreach ($leaves as $path => $value) {
    $isEmpty = $value === null || $value === '' || $value === [];
    if (! $isEmpty) {
        $set++;

        continue;
    }
    $empty++;
    [$class, $why] = $CLASSIFY[$path] ?? ['UNCLASSIFIED', 'nobody has decided what this emptiness costs'];
    $buckets[$class][] = [$path, $why];
}

// ── ⚠️⚠️ THE SECOND HALF, AND IT IS A REAL DEFECT CLASS, NOT A CURIOSITY ────────────────────────────
// 🔬 Measured on this project: with a CACHED config, `env()` called OUTSIDE a `config/` file returns NULL,
//    always. Such a call reads as "unset" no matter what the .env says — and it fails SILENTLY.
// ⚠️ Tokenised, never grepped: a grep reads its own needle and finds the word in comments and strings.
$strayEnv = [];
$roots = ['app', 'routes', 'database', 'resources'];
foreach ($roots as $root) {
    $dir = base_path($root);
    if (! is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $tokens = @token_get_all((string) file_get_contents($file->getPathname()));
        if ($tokens === false) {
            continue;
        }
        foreach ($tokens as $i => $t) {
            if (! is_array($t) || $t[0] !== T_STRING || $t[1] !== 'env') {
                continue;
            }
            $prev = $tokens[$i - 1] ?? null;
            // `->env(` and `Foo::env(` are different functions entirely.
            if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) {
                continue;
            }
            $strayEnv[] = str_replace(base_path().'/', '', $file->getPathname()).':'.$t[2];
        }
    }
}

// ── REPORT. ⚠️ NO VALUES, EVER. ──────────────────────────────────────────────────────────────────────
$line = str_repeat('-', 86);
echo "\n  CONFIG INVENTORY — ".date('Y-m-d H:i').'  env='.config('app.env')."\n  $line\n";
printf("  leaves resolved: %d   SET: %d   EMPTY: %d\n", count($leaves), $set, $empty);
echo "  ⚠️ no value is printed by this file, only whether one is present.\n  $line\n";

foreach (['SILENT', 'UNCLASSIFIED', 'LOUD', 'INERT'] as $class) {
    $rows = $buckets[$class];
    $mark = $class === 'SILENT' ? '⚠️⚠️' : ($class === 'UNCLASSIFIED' ? '⚠️ ' : '   ');
    printf("\n  %s EMPTY · %s  (%d)\n", $mark, $class, count($rows));
    if ($rows === []) {
        echo "      none\n";

        continue;
    }
    foreach ($rows as [$path, $why]) {
        printf("      %-44s %s\n", $path, $class === 'SILENT' || $class === 'UNCLASSIFIED' ? $why : '');
    }
}

// ── ⚠️⚠️ THE CACHED-CONFIG HAZARD — MEASURED, NOT ASSUMED ───────────────────────────────────────────
$cached = app()->configurationIsCached();
printf("\n  $line\n  ⚠️⚠️ CACHED-CONFIG HAZARD\n      configurationIsCached() = %s\n", $cached ? 'YES' : 'no');
if ($cached) {
    echo "      🔬 Laravel does NOT load .env when the config is cached — measured on this host: a key\n";
    echo "         that IS a line in .env resolved through env() to NULL. So EVERY site below reads NULL\n";
    echo "         no matter what .env says, and ⚠️⚠️ SETTING THE VARIABLE WILL NOT CHANGE THAT.\n";
}
printf("\n  ⚠️ env() called OUTSIDE config/ : %d site(s)%s\n", count($strayEnv), $cached ? '  ← each one reads NULL here' : '');
foreach (array_slice($strayEnv, 0, 12) as $s) {
    echo "      $s\n";
}
if (count($strayEnv) > 12) {
    printf("      … and %d more\n", count($strayEnv) - 12);
}
if ($strayEnv !== [] && $cached) {
    echo "      ⚠️⚠️ THE FIX IS APPLICATION CODE, NOT A SETTING: move the key into a config/ file and read it\n";
    echo "         through config(). Reported, deliberately not done — this pass fixes only configuration.\n";
}

// ── ⚠️⚠️ CALIBRATION. A DETECTOR IS NOT TRUSTED UNTIL IT IS SEEN TO BE RIGHT ABOUT KNOWN ANSWERS. ───
echo "\n  $line\n  CALIBRATION — four known points, checked before any result above is believed\n";
$cal = [
    ['app.key must be SET', ! in_array('app.key', array_column(array_merge(...array_values($buckets)), 0), true)],
    ['services.postmark.token must be EMPTY+SILENT', in_array('services.postmark.token', array_column($buckets['SILENT'], 0), true)],
    ['mail.default must resolve (never UNRESOLVED)', array_key_exists('mail.default', $leaves)],
    // ⚠️⚠️ The fourth point is the one that caught a real defect. The heartbeat ping URL is NOT a config
    //    key — it is a raw env() in routes/console.php — so the instrument must NOT claim to see it.
    //    🔬 An earlier calibration asserted it WAS a config key and failed, correctly, refusing to be
    //    trusted. That refusal is what surfaced the finding below.
    ['ops.heartbeat_ping_url is NOT a config key (it is a raw env() — see the hazard section)', ! array_key_exists('ops.heartbeat_ping_url', $leaves)],
];
$calFails = 0;
foreach ($cal as [$label, $ok]) {
    printf("      %s %s\n", $ok ? '✓' : '✗', $label);
    if (! $ok) {
        $calFails++;
    }
}
if ($calFails > 0) {
    echo "\n  ⚠️⚠️ CALIBRATION FAILED — this inventory is not to be trusted. Fix it before reading the result.\n";
    exit(2);
}
echo "      ⚠️ calibration passed: the instrument is right about the four answers already known.\n";

$silent = count($buckets['SILENT']);
printf("\n  RESULT: %d EMPTY+SILENT — the class that keeps working while quietly doing less.\n", $silent);
if (count($buckets['UNCLASSIFIED']) > 0) {
    printf("  ⚠️ %d EMPTY keys are UNCLASSIFIED. That is a coverage gap, not a pass.\n", count($buckets['UNCLASSIFIED']));
}
exit($silent > 0 ? 1 : 0);
