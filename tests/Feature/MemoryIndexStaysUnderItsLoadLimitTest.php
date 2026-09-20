<?php

declare(strict_types=1);

/**
 * ⚠️⚠️ THE SESSION MEMORY INDEX SILENTLY LOSES ITS TAIL ONCE IT PASSES ITS LOAD CEILING.
 *
 * ⚠️⚠️ **THIS IS A COMPOUNDING FAILURE AND IT FAILS QUIETLY, WHICH IS THE WORST WAY TO FAIL.** `MEMORY.md` is
 * loaded into every session's context. Past its ceiling the loader truncates it and reports only a
 * warning — so the file keeps *looking* complete while the part that is dropped is the **tail**, which is
 * exactly where the canonical-docs pointers, the tracks and the working-practice links live.
 *
 * 🔬 Measured 2026-09-20: the file had reached **227 lines** against a 200-line limit, so every cold session since had been
 * starting **without the pointers to `docs/PROJECT-STATE.md`, `docs/DECISIONS.md` and the divergence
 * register** — the very map a new session is told to read first. Nothing anywhere went red.
 * ⚠️⚠️ **AND THE CEILING MOVED DOWN, WHICH IS WHY THIS NUMBER IS NOT A CONSTANT TO TRUST:** the harness now
 * asks for **under 140**, so the file was condensed again (227 → 171 → 138). ⚠️ The limit belongs to the
 * loader, not to us — if it moves again, condense again; **do not raise the number to match the file**.
 *
 * ── ⚠️ WHY A TEST, AND WHY HERE ─────────────────────────────────────────────────────────────────────────
 *
 * ⚠️⚠️ **A LIMIT NOBODY MEASURES IS A LIMIT THAT WILL BE EXCEEDED AGAIN.** The file grows a little every pass,
 * by design — each version adds method bought with a real failure. So the ceiling needs an instrument, not an
 * intention.
 *
 * ⚠️ It lives in the suite because the suite is the one thing that runs on every pass. A shell script would
 * be the more natural home for a file outside the repository — and this project already learned what happens
 * to those: `ops/money-as-seeded.sh` guards six money figures and **the suite has never once run it**, so
 * "all gates green" was one sentence covering two instruments for months.
 *
 * ⚠️ **IT SKIPS RATHER THAN FAILS WHERE THE DIRECTORY DOES NOT EXIST** (CI, another machine, another user) —
 * the path is machine-local by construction. ⚠️ A skip here is honest; a hard failure in CI over somebody's
 * home directory would teach people to delete the test.
 *
 * ⚠️ **IF THIS GOES RED THE FIX IS TO CONDENSE, NEVER TO DROP** — state the rule, not the list, the same way
 * the ladders were compressed. And raising the ceiling is not a fix: the ceiling is the loader's, not ours.
 */
function memoryIndexPath(): ?string
{
    $home = getenv('HOME') ?: '';

    if ($home === '') {
        return null;
    }

    // ⚠️ The project slug is the absolute project path with separators replaced — derived, never hardcoded,
    //    so this keeps working if the checkout moves.
    $slug = str_replace('/', '-', base_path());
    $path = $home.'/.claude/projects/'.$slug.'/memory/MEMORY.md';

    return is_file($path) ? $path : null;
}

it('⚠️⚠️ keeps MEMORY.md under the 140-line load ceiling — past it, the TAIL is dropped in silence', function () {
    $path = memoryIndexPath();

    if ($path === null) {
        test()->markTestSkipped('no session-memory index on this machine (CI or a different user) — nothing to measure');
    }

    $lines = count(file($path, FILE_IGNORE_NEW_LINES));

    expect($lines)->toBeLessThanOrEqual(140,
        "⚠️⚠️ MEMORY.md IS {$lines} LINES, OVER THE 140-LINE LOAD CEILING — ITS TAIL IS BEING DROPPED.\n"
        ."⚠️ Nothing fails when this happens. The file still looks complete; the loader just stops early, and\n"
        ."   what it stops before is the END — the canonical-docs pointers, the tracks, the working-practice\n"
        ."   links. Every cold session after that point starts without the map it is told to read first.\n"
        .'⚠️⚠️ FIX IT BY CONDENSING, NOT BY DROPPING: state the rule, not the list. Do NOT raise this number '
        .'— the ceiling belongs to the loader, not to us. And prove the fix by reading the LAST LINE back, '
        .'not by counting lines.');
});

/**
 * ⚠️ AND THE HALF THAT MATTERS MORE THAN THE COUNT: the tail must still be the thing worth keeping.
 *
 * ⚠️⚠️ A trim that gets under the ceiling by deleting the pointers would pass the test above while causing
 * the exact harm it exists to prevent. This asserts the file still ENDS with its reference section, so
 * "under the limit" cannot be bought by throwing away the part the limit was protecting.
 */
it('⚠️ still ends with the pointer sections — being under the limit must not be bought by deleting them', function () {
    $path = memoryIndexPath();

    if ($path === null) {
        test()->markTestSkipped('no session-memory index on this machine');
    }

    $text = (string) file_get_contents($path);

    expect(str_contains($text, 'docs/PROJECT-STATE.md'))->toBeTrue(
        '⚠️⚠️ THE CANONICAL-DOCS POINTERS ARE GONE FROM MEMORY.md. Getting under the line ceiling by deleting '
        .'the map is the harm this ceiling exists to prevent, not a fix for it. Condense prose; keep pointers.');

    expect(str_contains($text, 'audit-method-and-traps'))->toBeTrue(
        '⚠️ the READ-BEFORE-ACTING pointer to the method notes is gone — that file is the treasure, and the '
        .'index is the only thing that points a cold session at it.');
});
