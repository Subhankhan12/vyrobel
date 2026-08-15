<?php

/*
 * THE JOINER/CLASSIFIER — the third piece of the contrast probe (collector -> sampler -> this).
 *
 * Joins sampler output back to the candidate metadata so a failure names the element it belongs to, refuses a
 * broken capture outright, and splits findings PAGE / SHELL / DECIDED so a page gate does not absorb a family
 * that is not its to fix.
 *
 * ⚠️ MOVED HERE FROM `.playwright-mcp/report.php` (DO-RESTORE-5, 2026-08-15) — that directory is GITIGNORED, so
 * every correction made to this file across five passes existed in one session's working tree only. It is the
 * exact failure `contrast-collect.js` names in its own header: "a correction that is not committed is a
 * correction hoped." The classification below is a record of founder decisions; losing it silently re-opens
 * settled questions.
 */
$png = $argv[1];
$json = $argv[2];
$cands = json_decode((string) file_get_contents($json), true);
$by = [];
foreach ($cands['candidates'] as $c) {
    $by[$c['t']] = $c;
}
/* ⚠️ FAIL LOUD ON A BROKEN CAPTURE CONTRACT — the numbers are meaningless if the shot and the rects disagree. */
[$imgW, $imgH] = getimagesize($png);
if (isset($cands['viewportH']) && $cands['docHeight'] > $cands['viewportH']) {
    exit(sprintf("REFUSING: document is %dpx but the viewport was %dpx — resize the viewport to the document and re-capture.\n",
        $cands['docHeight'], $cands['viewportH']));
}
if (isset($cands['viewportH']) && $imgH < $cands['viewportH']) {
    exit(sprintf("REFUSING: screenshot is %dpx tall but the viewport was %dpx.\n", $imgH, $cands['viewportH']));
}

$out = json_decode((string) shell_exec(sprintf('php %s %s %s 2>&1',
    escapeshellarg(__DIR__.'/contrast-sampler.php'), escapeshellarg($png), escapeshellarg($json))), true);

if (! $out) {
    exit("sampler produced no JSON\n");
}

/* ⚠️ SCOPE GAP 3 travels with the result. An element under a dimmed ancestor never reaches the sampler, so
   it cannot appear in `failed` — a page can show 0 failures purely because its text was never measured. */
/* GAP A closed: dimmed elements are now MEASURED (composited at their effective alpha), not refused. This
   counts how many were measured that way, so the compositing path stays visible in every report. */
$dimmed = array_values(array_filter($cands['candidates'] ?? [], fn ($c) => isset($c['alpha']) && $c['alpha'] < 1));

printf("%s — measured %d · FAILED %d · unmeasurable %d %s · dimmed-and-COMPOSITED %d\n", basename($png),
    $out['measured'], $out['failed'], $out['unmeasurable'], json_encode($out['unmeasurableReasons'] ?? []),
    count($dimmed));

foreach (array_slice($dimmed, 0, 4) as $d) {
    printf("  · composited %-24s alpha %.2f (by %s)\n",
        substr($d['sel'], 0, 24), $d['alpha'], substr((string) ($d['dimmedBy'] ?? '?'), 0, 20));
}

/*
 * Classified so a page gate does not silently absorb two families that are NOT its to fix:
 *  - SHELL: the sidebar/topbar chrome, shared by every operator page — retuning it from a page gate is the
 *    mistake the owner walk recorded (O25/O27). Logged, not fixed here.
 *  - KNOWN: white-on-accent-gradient, already SURFACED realm-wide as needing its own a11y gate.
 */
$shell = '/vy-acct-role|vy-search-pill|vy-topbar|vy-nav-/';

/* ⚠️ DECIDED — founder rulings DO-RESTORE-1(a) and DO-RESTORE-3 put these deliberately below AA. They are
   ACCEPTED EXCEPTIONS, not defects: a pass that re-reports them as findings is re-litigating a decision. */
$decided = '/btn--accent|pill--accent|act--accent|fd-seg-btn|vy-brand-mark/';
/* ⚠️ THE AVATAR FAMILY LEFT THIS LIST (DO-RESTORE-5, 2026-08-15) — it is no longer a decided exception, it
   simply PASSES. `.vy-avatar` / `.vy-owner-avatar` / `.vy-arr-av` / `.vy-thread-av` stopped riding a gradient
   and now sit on the flat `--vy-avatar-bg` at 7.60; `.vy-plan-tag` is solid --vy-accent + white at 5.18.
   ⚠️ REMOVING them from `$decided` is the point, not housekeeping: while they were listed, a REGRESSION on any
   of them would have been silently filed as "accepted". They must be able to fail again. The CTA entries stay
   — DO-RESTORE-1(a) is untouched and genuinely sub-AA. */

$groups = ['PAGE' => [], 'SHELL' => [], 'DECIDED (DO-RESTORE)' => []];
foreach ($out['failures'] ?? [] as $f) {
    $c = $by[$f['text']] ?? [];
    $sel = $c['sel'] ?? '?';
    $g = preg_match($decided, $sel) ? 'DECIDED (DO-RESTORE)' : (preg_match($shell, $sel) ? 'SHELL' : 'PAGE');
    $groups[$g][] = sprintf('  %-5s %-5.2f (need %.1f)  fg %-18s plate %-18s  %-28s | %s',
        $f['text'], $f['ratio'], $f['need'], $f['colour'], $f['plate'], substr($sel, 0, 28), substr($c['txt'] ?? '', 0, 42));
}
foreach ($groups as $name => $rows) {
    if (! $rows) {
        continue;
    }
    echo ' ['.$name.' '.count($rows)."]\n".implode("\n", $rows)."\n";
}
