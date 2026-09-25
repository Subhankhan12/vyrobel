<?php

declare(strict_types=1);

/*
 * ⚠️⚠️ THE MUTATION HARNESS — one runner for every set in tests/Mutations/sets/.
 *
 *   php tests/Mutations/run.php                 every set
 *   php tests/Mutations/run.php --set=admin-03  one set
 *   php tests/Mutations/run.php --only=P1-M21,A03-N5
 *   php tests/Mutations/run.php --list
 *
 * A mutation removes one guard and runs the test aimed at it; the test must go RED. The result has THREE columns, and
 * only the first is good news:
 *
 *   LANDED · CAUGHT       the guard was really removed, and its test went red
 *   LANDED · NOT CAUGHT   the guard was really removed, and its test stayed green — the guard is unproven
 *   DID NOT LAND          the harness could not remove the guard as written — a HARNESS error, never a result
 *
 * ⚠️⚠️ WHY THIS FILE EXISTS (ADMIN 03B, 2026-09-25). Five of 115 mutations had silently stopped testing anything:
 * code moved under them (a refactor in Phase 3, then ADMIN 03's own edits), their needles matched nothing, and the
 * per-phase scripts that ran them lived in a session scratchpad where nobody re-ran them. "A mutation that did not
 * land is not a result" had already fired twice as a NOTE. This is it as a MECHANISM: before a mutation counts, the
 * harness asserts —
 *   1. the target text occurs EXACTLY ONCE in the file (0 = the code moved; 2+ = ambiguous);
 *   2. the mutated text DIFFERS from the original (a no-op mutation proves nothing);
 *   3. the file ON DISK holds the mutated text after the write (the write itself landed);
 *   4. the aimed test is GREEN on the clean tree first, and its filter matches at least one test — a test that is
 *      already red, or runs nothing, "catches" every mutation;
 *   5. every file is restored BYTE-IDENTICAL afterwards (sha256), or the run aborts.
 * Any failed assertion is DID NOT LAND. And the harness proves its own detector at the start of EVERY run (self-check
 * below): if it cannot tell a missing needle, a doubled needle or a no-op apart from a real mutation, it refuses to
 * report anything.
 *
 * Premises it refuses to run without: no other test run active (one suite at a time), and no cached routes/config —
 * a cached route file would make a routes/web.php mutation silently not apply.
 */

chdir(dirname(__DIR__, 2));

const LANDED_CAUGHT = 'LANDED · CAUGHT';
const LANDED_NOT_CAUGHT = 'LANDED · NOT CAUGHT';
const DID_NOT_LAND = 'DID NOT LAND';

require_once __DIR__.'/land.php';

/** @return array{ran: int, failed: bool, note: string}|null  null = no parseable result */
function runTest(array $spec): ?array
{
    $cmd = ['./vendor/bin/pest', $spec['file']];
    if (! empty($spec['filter'])) {
        $cmd[] = '--filter='.$spec['filter'];
    }
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($proc)) {
        return null;
    }
    $out = (string) stream_get_contents($pipes[1]);
    $err = (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    foreach (array_reverse(preg_split('/\R/', trim($out.PHP_EOL.$err)) ?: []) as $line) {
        $j = json_decode(trim($line), true);
        if (is_array($j) && ($j['tool'] ?? null) === 'pest') {
            $first = $j['failures'][0] ?? ($j['error_details'][0] ?? null);
            $note = $first ? preg_replace('/^.*__pest_evaluable_it_/', '', (string) $first['test']).' → '.mb_substr(str_replace("\n", ' ', (string) $first['message']), 0, 90) : '';

            return ['ran' => (int) ($j['tests'] ?? 0), 'failed' => ($j['result'] ?? '') !== 'passed' || $code !== 0, 'note' => $note];
        }
    }

    return null;
}

function specKey(array $spec): string
{
    return $spec['file'].(empty($spec['filter']) ? '' : ' --filter='.$spec['filter']);
}

// A mutated file must never outlive the harness — restored on normal exit, on a fatal, and on Ctrl-C.
$pending = null;   // [path, original bytes]
$restore = function () use (&$pending): void {
    if ($pending !== null) {
        file_put_contents($pending[0], $pending[1]);
        fwrite(STDERR, "  restored {$pending[0]} on the way out\n");
        $pending = null;
    }
};
register_shutdown_function($restore);
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    foreach ([SIGINT, SIGTERM] as $sig) {
        pcntl_signal($sig, function () use ($restore) {
            $restore();
            exit(130);
        });
    }
}

$opts = getopt('', ['set:', 'only:', 'list']);
$sets = [];
foreach (glob('tests/Mutations/sets/*.php') ?: [] as $file) {
    $name = basename($file, '.php');
    if (isset($opts['set']) && $opts['set'] !== $name) {
        continue;
    }
    foreach (require $file as $row) {
        $sets[] = $row + ['set' => $name];
    }
}
if (isset($opts['only'])) {
    $only = array_map('trim', explode(',', (string) $opts['only']));
    $sets = array_values(array_filter($sets, fn ($m) => in_array($m['id'], $only, true)));
}
$ids = array_column($sets, 'id');
if (count($ids) !== count(array_unique($ids))) {
    fwrite(STDERR, "✗ duplicate mutation ids — a result could be reported against the wrong guard\n");
    exit(3);
}
if (isset($opts['list'])) {
    foreach ($sets as $m) {
        echo str_pad($m['id'], 10).' '.$m['set'].'  '.$m['name'].PHP_EOL;
    }
    exit(0);
}
if ($sets === []) {
    fwrite(STDERR, "✗ no mutations selected — a run over nothing reports nothing\n");
    exit(3);
}

// ── PREMISES ─────────────────────────────────────────────────────────────────────────────────────────────────────
exec('bash ops/proc-check.sh -q '.escapeshellarg('pest|artisan test').' 2>&1', $o, $busy);
if ($busy !== 0) {
    fwrite(STDERR, "✗ another test run is active — one run at a time (ops/proc-check.sh)\n");
    exit(2);
}
foreach (glob('bootstrap/cache/{routes,config}*.php', GLOB_BRACE) ?: [] as $cached) {
    fwrite(STDERR, "✗ {$cached} exists — a cached route/config file would make a mutation silently not apply\n");
    exit(2);
}

// ── SELF-CHECK — the harness proves its own detector, every run ─────────────────────────────────────────────────
$self = [
    'a missing needle' => landMutation("a\nb\n", 'zzz', 'y')['ok'] === false,
    'a doubled needle' => landMutation("x\nx\n", 'x', 'y')['ok'] === false,
    'a no-op mutation' => landMutation("a\nb\n", 'a', 'a')['ok'] === false,
    'an empty needle' => landMutation("a\nb\n", '', 'y')['ok'] === false,
    'a real mutation' => landMutation("a\nb\n", 'a', 'c') === ['ok' => true, 'why' => '', 'mutated' => "c\nb\n"],
];
foreach ($self as $case => $ok) {
    if (! $ok) {
        fwrite(STDERR, "✗ SELF-CHECK FAILED on {$case} — the harness cannot tell a landed mutation from one that did not land; refusing to report\n");
        exit(3);
    }
}
echo '  self-check: '.count($self).'/'.count($self)." — missing, doubled, no-op and empty needles are refused; a real mutation lands\n";

// ── BASELINE — every aimed test must be GREEN on the clean tree, and must run something ──────────────────────────
$baseline = [];
foreach ($sets as $m) {
    foreach ($m['tests'] as $spec) {
        $baseline[specKey($spec)] ??= $spec;
    }
}
echo '  baseline: '.count($baseline)." distinct test runs on the clean tree…\n";
$baselineBad = [];
foreach ($baseline as $key => $spec) {
    $r = runTest($spec);
    if ($r === null || $r['ran'] === 0 || $r['failed']) {
        $baselineBad[$key] = $r === null ? 'no result' : ($r['ran'] === 0 ? 'filter matched 0 tests' : 'RED on the clean tree: '.$r['note']);
        echo "    ✗ {$key} — {$baselineBad[$key]}\n";
    }
}
echo '  baseline: '.(count($baseline) - count($baselineBad)).'/'.count($baseline)." green\n\n";

// ── THE RUN ──────────────────────────────────────────────────────────────────────────────────────────────────────
$rows = [];
foreach ($sets as $m) {
    $path = $m['file'];
    if (! is_file($path)) {
        $rows[] = [$m, DID_NOT_LAND, "file {$path} does not exist"];

        continue;
    }
    $bad = array_values(array_filter(array_map(fn ($s) => $baselineBad[specKey($s)] ?? null, $m['tests'])));
    if ($bad !== []) {
        $rows[] = [$m, DID_NOT_LAND, 'aimed test unusable — '.$bad[0]];

        continue;
    }

    $original = (string) file_get_contents($path);
    $hash = hash('sha256', $original);
    $land = landMutation($original, $m['old'], $m['new']);
    if (! $land['ok']) {
        $rows[] = [$m, DID_NOT_LAND, $land['why']];

        continue;
    }

    $pending = [$path, $original];
    file_put_contents($path, $land['mutated']);
    try {
        if ((string) file_get_contents($path) !== $land['mutated']) {
            $rows[] = [$m, DID_NOT_LAND, 'the write did not land on disk'];

            continue;
        }
        $verdicts = [];
        foreach ($m['tests'] as $spec) {
            $r = runTest($spec);
            $verdicts[] = $r === null ? ['x', 'no result from the test run'] : ($r['ran'] === 0 ? ['x', 'filter matched 0 tests'] : ($r['failed'] ? ['red', $r['note']] : ['green', specKey($spec).' stayed green']));
        }
        $harness = array_values(array_filter($verdicts, fn ($v) => $v[0] === 'x'));
        $green = array_values(array_filter($verdicts, fn ($v) => $v[0] === 'green'));
        $rows[] = $harness !== [] ? [$m, DID_NOT_LAND, $harness[0][1]]
            : ($green !== [] ? [$m, LANDED_NOT_CAUGHT, $green[0][1]] : [$m, LANDED_CAUGHT, $verdicts[0][1]]);
    } finally {
        file_put_contents($path, $original);
        $pending = null;
        if (hash('sha256', (string) file_get_contents($path)) !== $hash) {
            fwrite(STDERR, "✗✗ {$path} was NOT restored byte-identical — stopping now\n");
            exit(4);
        }
        if (str_ends_with($path, '.blade.php')) {
            exec('php artisan view:clear 2>&1');   // a compiled copy of the mutated view must not serve the next run
        }
    }
}

foreach ($rows as [$m, $status, $detail]) {
    echo '  '.str_pad($status, 20).' '.str_pad($m['id'], 9).' '.str_pad(mb_substr($m['name'], 0, 44), 45).' '.mb_substr((string) $detail, 0, 110).PHP_EOL;
}
$count = fn (string $s) => count(array_filter($rows, fn ($r) => $r[1] === $s));
echo PHP_EOL.'  ── '.count($rows).' mutations · '.LANDED_CAUGHT.' '.$count(LANDED_CAUGHT).' · '.LANDED_NOT_CAUGHT.' '.$count(LANDED_NOT_CAUGHT).' · '.DID_NOT_LAND.' '.$count(DID_NOT_LAND)." ──\n";
echo '  ⚠️ only the first column is good news; the other two must be zero.'.PHP_EOL;

exit($count(LANDED_NOT_CAUGHT) + $count(DID_NOT_LAND) === 0 ? 0 : 1);
