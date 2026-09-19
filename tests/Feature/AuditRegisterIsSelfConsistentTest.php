<?php

/**
 * ⚠️⚠️ THE REGISTER MAY NOT CONTRADICT ITSELF — FIX 48, 2026-09-19.
 *
 * ⚠️⚠️ WHY THIS EXISTS, AND WHY A SIXTH HAND SWEEP WOULD HAVE FAILED TOO. The finding register contradicted
 * itself on FIVE CONSECUTIVE PASSES. Each pass fixed the stale claims it happened to see and each left more
 * behind. FIX 47 closed F-35, swept §8's open list and F-35's own register row, **reported a clean sweep** —
 * and never knew a THIRD surface existed in §4's table. The verification found it the next day.
 *
 * ⚠️⚠️ **A STATUS IS NOT A ROW. IT IS A CLAIM WITH MORE SURFACES THAN ANYONE COUNTS.** This is the F-44 shape
 * turned on the record instead of the code: *a register is one reader's sweep; a guard finds the class.* On
 * its first run it found **five** stale surfaces across THREE findings, where the verification had named two.
 *
 * ⚠️ IT ASSERTS THREE THINGS: the census re-derives from the rows with no gaps · no claim anywhere under
 * `docs/` disagrees with its authoritative row · and the frozen-file list has not grown.
 *
 * ⚠️⚠️ AND IT PROVES ITS OWN DISCRIMINATION. A detector whose green means "it does nothing" is worse than no
 * detector, so the classifier is driven against the exact shapes that have fooled a scan in this repository
 * before — the "DEFERS"/"DEFERRED" prose match, a lower-case narrative word, a quoted historical status, and
 * the six real strings that contain a literal `F-<digits>` and are not register ids.
 */

use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// ── 1. THE CENSUS, RE-DERIVED FROM THE ROWS ───────────────────────────────────────────────────────
it('⚠️ re-derives the finding census from §2 and §2b with no gaps and no duplicates', function () {
    $register = vyAuthoritativeRegister();
    $ids = array_keys($register);

    expect($ids)->not->toBeEmpty('the register parsed to nothing — the table shape has changed');

    $expected = range(1, max($ids));
    $missing = array_values(array_diff($expected, $ids));

    expect($missing)->toBe([], 'the finding numbering has a GAP — ids must be continuous and never reused: F-'
        .implode(', F-', $missing).' have no authoritative row');

    // Every row resolved to one of the five states — an unrecognised status is a parser failure, not a pass.
    foreach ($register as $id => $row) {
        expect($row['status'])->toBeIn(['CLOSED', 'ACCEPTED', 'CONTAINED', 'PARTIAL', 'DEFERRED', 'OPEN'],
            "F-{$id} resolved to an unrecognised state");
    }
});

// ── 2. ⚠️⚠️ THE CLASS, PINNED ─────────────────────────────────────────────────────────────────────
it('⚠️⚠️ no finding-status claim anywhere under docs/ contradicts its authoritative row', function () {
    $offenders = vyRegisterContradictions();

    $report = array_map(
        fn (array $o): string => sprintf(
            "%s:%d — F-%d is claimed %s but its register row says %s\n      %s",
            $o['file'], $o['line'], $o['id'], $o['claimed'], $o['authoritative'], $o['text']
        ),
        $offenders
    );

    expect($offenders)->toBe([],
        "⚠️⚠️ THE REGISTER CONTRADICTS ITSELF. A contradiction here is a FINDING, not a typo — five consecutive\n"
        ."passes each fixed what they saw and left more. Fix EVERY surface, then re-run:\n\n  "
        .implode("\n\n  ", $report)."\n"
    );
});

// ── 3. ⚠️ THE ALLOW-LIST, CAPPED ──────────────────────────────────────────────────────────────────
it('⚠️ the frozen-file list has not grown — an allow-list is how a guard dies quietly', function () {
    $frozen = vyFrozenFindingSurfaces();

    // ⚠️ 🔬 18 entries at FIX 48, of which SIX currently suppress a hit; the other twelve are the same kind
    // of artefact and are listed so the RULE is visible rather than only its load-bearing members.
    expect($frozen)->toHaveCount(18,
        "⚠️⚠️ THE FROZEN LIST CHANGED. It admits exactly one kind of file: a report of ONE DATED pass, phase or\n"
        ."audit, whose claims are true of its own date and are superseded by the register rather than corrected\n"
        ."in place. A LIVE surface — anything a reader consults to learn the CURRENT state — may NEVER be added.\n"
        .'If a live file is failing the guard, FIX THE FILE.'
    );

    foreach ($frozen as $path => $reason) {
        expect(base_path($path))->toBeReadableFile("frozen entry {$path} does not exist — remove it");
        expect(trim($reason))->not->toBeEmpty("frozen entry {$path} carries no reason");
    }
});

// ── 4. ⚠️⚠️ THE DETECTOR DISCRIMINATES — proven against the shapes that have fooled a scan here ────
it('⚠️⚠️ classifies a status MARKER and ignores narrative prose', function (string $text, ?string $expected) {
    expect(vyRegisterClaimIn($text))->toBe($expected, "misclassified: {$text}");
})->with([
    // Real markers, in the capitalisation the register actually uses.
    ['✅ CLOSED (FIX 03)', 'CLOSED'],
    ['⛔ **STILL OPEN — its own pass**', 'OPEN'],
    ['⚠️⚠️ **DEFERRED (2026-09-19)**', 'DEFERRED'],
    ['⚠️⚠️ **CONTAINED (FIX 38), NOT CLOSED.**', 'CONTAINED'],
    ['⚠️⚠️ **ACCEPTED BY RULING, 2026-09-19 — NEITHER OPEN NOR CLOSED.**', 'ACCEPTED'],

    // ⚠️⚠️ THE PROSE TRAP, VERBATIM: the verification's own first parse classified F-57 as DEFERRED by
    // matching the word "DEFERS" inside the DESCRIPTION OF ITS FIX. `\b`-anchoring is what stops it.
    ['`processDue()` now DEFERS the removal and rewrites the date', null],
    ['a deferral was recorded', null],

    // Lower-case narrative. These three are real lines from the register and all three were reported as
    // contradictions by an earlier, case-insensitive draft of this guard.
    ['(the `public` realm closed; the admin realm reverted)', null],
    ['🖥 All three dialogs open, **proven by clicking**', null],
    ['+ FIX 12\'s deferred controls', null],

    // Negation — a state named in order to exclude it is not a claim of that state.
    ['**Admin half NOT closed** — see the sibling row', null],

    // The case-insensitive exceptions, which cannot mean anything else. Without the first, this guard
    // misses V-1, whose wording is exactly "remains blocked on §4".
    ['remains blocked on §4', 'OPEN'],
    ['Only F-99 is still open, and it has its own pass', 'OPEN'],
]);

it('⚠️⚠️ ignores a status quoted as HISTORY — the register records what a surface USED to say', function () {
    // §8 item 4's real shape: the live status first, the superseded wording quoted beside it.
    expect(vyRegisterClaimIn(vyRegisterStripQuotedHistory(
        '— ✅ **CLOSED (FIX 35).** ⚠️ This item read *"BUILT AND UNWIRED. Blocked on R-GATE"* until FIX 41.'
    )))->toBe('CLOSED');

    // A backticked marker is a quotation too — it is how the verification reports a stale surface.
    expect(vyRegisterClaimIn(vyRegisterStripQuotedHistory(
        'table still shows it as `⛔ STILL OPEN`; FIX 47 closed it'
    )))->not->toBe('OPEN');
});

it('⚠️⚠️ the finding token rejects the six real strings in this repo that contain a literal F-<digits>', function (string $haystack) {
    expect(preg_match(VY_FINDING_TOKEN, $haystack))->toBe(0,
        "⚠️ an unbounded `F-\\d+` matches inside {$haystack} — that is ten files of false positives");
})->with([
    'UTF-16BE text tokens',                 // docs/audits/owner-portal-DEEP-parity
    'GUEST-MONEY-PATH-PROOF-2026-08-18.md', // docs/design/DIVERGENCE-REGISTER
    'the stamp is non-deterministic (`DO-PDF-2`)', // docs/PROJECT-STATE
    'RIF-1 (I1) — Wrap the guest Account page',    // docs/POLISH-SWEEP
    '%PDF-1.7 · 881,663 bytes',             // docs/REMAINING-OVERFLOW
    '**F-2a-04** | No login rate limit',    // docs/AUDIT-COMPLETE-2026-08-07 — the AUGUST register's scheme
]);

it('⚠️ the finding token still matches a real register id in the shapes the documents use', function (string $haystack, int $id) {
    expect(preg_match(VY_FINDING_TOKEN, $haystack, $m))->toBe(1)
        ->and((int) $m[1])->toBe($id);
})->with([
    ['| **F-35** | — | ⛔ |', 35],
    ['closed by FIX 46 (F-21) per its row', 21],
    ['F-53/F-54 remain contained', 53],
    ['F-1…F-58, no gaps', 1],
    ["F-49's tier was corrected", 49],
]);
