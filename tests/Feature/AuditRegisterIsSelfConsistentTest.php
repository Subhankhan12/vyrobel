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

// ── 2b. ⚠️⚠️ COVERAGE — A DETECTOR THAT READS HALF OF docs/ LOOKS EXACTLY LIKE A CLEAN REPOSITORY ──
//
// ⚠️⚠️ NOT HYPOTHETICAL. 🔬 The session memory's own index was 206 lines, over its limit, and had been
// SILENTLY TRUNCATING ITS TAIL for an unknown number of passes — part of the record was simply not being
// read, and every gap it caused was attributed to something else. A green scan proves nothing until the
// scan's own reach is asserted.
it('⚠️⚠️ proves how much it actually read — files and mentions, not just a green', function () {
    $c = vyRegisterScanCoverage();

    // ⚠️ NOTHING VANISHES SILENTLY: every markdown file under docs/ is either scanned or explicitly frozen.
    expect($c['files_scanned'] + $c['files_frozen'])->toBe($c['files_present'],
        'a file under docs/ was neither scanned nor frozen — the walker dropped it');

    // ⚠️ FLOORS, not equalities: docs/ grows, and a brittle equality would be edited away rather than
    // investigated. 🔬 Measured at FIX 48: 188 present = 170 scanned + 18 frozen · 467 mentions scanned
    // (7.85 MB) + 286 frozen = 753. A COLLAPSE in reach is what these catch.
    expect($c['files_scanned'])->toBeGreaterThanOrEqual(170,
        "coverage COLLAPSED: only {$c['files_scanned']} files scanned, was 170 at FIX 48");
    expect($c['mentions_scanned'])->toBeGreaterThanOrEqual(400,
        "coverage COLLAPSED: only {$c['mentions_scanned']} finding mentions scanned, was 467 at FIX 48");
    expect($c['bytes_scanned'])->toBeGreaterThanOrEqual(7_000_000,
        "coverage COLLAPSED: only {$c['bytes_scanned']} bytes read, was 7,852,989 at FIX 48");

    // The register itself must be inside what was read — a scan that skipped it would be vacuous.
    expect($c['mentions_scanned'])->toBeGreaterThan(0);
});

// ── 2c. ⚠️⚠️ THE SAME CLASS, ONE NAMESPACE OVER — THE RULINGS ─────────────────────────────────────
//
// ⚠️⚠️ THREE REAL CONTRADICTIONS WERE INVISIBLE TO THE F-ID SCAN BECAUSE THEY CARRY NO F-ID.
// `STATUS-LADDERS.md` said "R1 and R3 were NOT put and remain open" while its OWN ROWS below read ✅ RULED,
// and R6's block still read as undecided after being ruled and pinned. Both were found BY EYE, which is the
// method this file exists to replace. 🔬 Mutation-proved at FIX 49: restoring that exact sentence makes this
// fail with "R3 claimed UNDECIDED, authoritative DECIDED" and the file and line.
it('⚠️⚠️ no ruling-state claim anywhere under docs/ contradicts §4', function () {
    $offenders = vyRulingContradictions();

    $report = array_map(
        fn (array $o): string => sprintf(
            "%s:%d — %s is claimed %s but the record says %s\n      %s",
            $o['file'], $o['line'], $o['id'], $o['claimed'], $o['authoritative'], $o['text']
        ),
        $offenders
    );

    expect($offenders)->toBe([],
        "⚠️⚠️ A RULING IS DESCRIBED AS SOMETHING THE RECORD DOES NOT SAY IT IS.\n"
        ."⚠️ If a RULED behaviour is being called undecided, a pass is about to re-litigate a closed decision.\n"
        ."⚠️ If an UNRULED one is being called answered, somebody is about to skip asking.\n\n  "
        .implode("\n\n  ", $report)."\n"
    );
});

it('⚠️ derives the rulings from §4 first, and never lets the ladder file overwrite it', function () {
    $rulings = vyAuthoritativeRulings();

    // 🔬 §4's answers table holds 15 rows; R1/R3 sit in the table beneath it; R2/R5 come from the ladder.
    expect(count($rulings))->toBeGreaterThanOrEqual(19,
        'the ruling map collapsed — §4\'s table shape has changed');

    // ⚠️⚠️ THE REGRESSION THIS PINS: an earlier draft let the ladder loop run second and OVERWRITE §4, so
    // R6 and H4 — both SATISFIED in §4 — were reported UNDECIDED. Two sources of truth, wrong one winning,
    // inside a guard built to stop precisely that.
    expect($rulings['R6'] ?? null)->toBe('DECIDED', 'R6 is SATISFIED in §4 and pinned by OtaTerminalModificationTest');
    expect($rulings['H4'] ?? null)->toBe('DECIDED', 'H4 is SATISFIED in §4');
    expect($rulings['R-SEASON'] ?? null)->toBe('DECIDED', 'the named rulings must parse at all');

    // ⚠️ R5 is the one genuinely unruled transition and F-60 depends on it staying visible as such.
    expect($rulings['R5'] ?? null)->toBe('UNDECIDED', 'R5 is still unruled — F-60 closes when it is ruled');

    // ⚠️⚠️ H1–H3 WERE ABSENT BY DESIGN UNTIL FIX 51, AND THE REASON IS WORTH KEEPING. R-HK-11 ruled "keep the
    // housekeeping ladder permissive — all fourteen permitted moves stay", which answers them WITHOUT NAMING
    // THEM — the F-32 shape. This guard refused to assert a state the record did not state, which was right.
    // ⚠️ 🔬 FIX 51 verified membership from `HousekeepingService::ALLOWED_FROM` (exactly fourteen moves; H1 is
    //    one of them, H2 six, H3 four) and recorded it in §4, so the record now states what the ruling always
    //    covered. **"The ruling covers it" and "the record states it" are different facts — only the second
    //    is enforceable**, and this assertion is the enforcement.
    foreach (['H1', 'H2', 'H3'] as $covered) {
        expect($rulings[$covered] ?? null)->toBe('DECIDED',
            "{$covered} is covered by R-HK-11 and §4 must say so — if this is ABSENT the row was removed");
    }
});

it('⚠️⚠️ reads a bare R<n> as a ladder ruling ONLY where that numbering applies (F-63)', function () {
    $scope = vyRulingNumberedScope();

    // 🔬 F-63: `R2` names a DIFFERENT, already-ruled question in HOUSEKEEPING-LADDER.md and a third thing
    // again in DECISIONS.md. Treating the token as global would MANUFACTURE the false conclusion F-63 warns
    // about — a reader concluding the reservation R2 was answered because the housekeeping R2 was.
    expect(array_keys($scope))->toBe(['docs/AUDIT-SUMMARY.md', 'docs/STATUS-LADDERS.md'],
        '⚠️⚠️ THE NUMBERED-RULING SCOPE CHANGED. It is a NAMESPACE rule, not a list of excused defects: '
        .'outside these two files a bare R<n> is a different question (F-63). Adding a file here asserts '
        .'that its R-numbers mean the reservation ladder — prove that before you do it.');

    foreach ($scope as $path => $reason) {
        expect(base_path($path))->toBeReadableFile("scoped file {$path} does not exist");
        expect(trim($reason))->not->toBeEmpty("scoped file {$path} carries no reason");
    }
});

it('⚠️ classifies a ruling state and ignores prose', function (string $text, ?string $expected) {
    expect(vyRulingClaimIn($text))->toBe($expected, "misclassified: {$text}");
})->with([
    ['✅ **BUILT (FIX 35)**', 'DECIDED'],
    ['**SATISFIED**', 'DECIDED'],
    ['⛔ **NOT BUILT — QUARANTINED (FIX 38)**', 'DECIDED'],   // ⚠️ a quarantine IS an answer
    ['were NOT put and remain open', 'UNDECIDED'],
    ['is still unruled', 'UNDECIDED'],
    ['the rate was rebuilt and the room was cleaned', null],  // lower-case prose is not a marker
]);

// ── 2d. ⚠️⚠️ F-63's ENFORCEMENT HALF — SAFE WAS NOT THE SAME AS ENFORCED ──────────────────────────
//
// ⚠️⚠️ THE NAMESPACE RULE PROTECTED THE PARSER; THIS PROTECTS THE READER. Because a bare `R<n>` is read as a
// ladder ruling only inside its home files, a document ANYWHERE ELSE saying "R2 is settled" passed in
// SILENCE — and that sentence is the entire risk F-63 names. A reader who searches rather than reads lands
// on a yes that answers a different question.
//
// ⚠️ THE RULE IS NARROW BECAUSE THE COUNT MADE IT NARROW. 🔬 211 bare cross-file numeric references exist,
// across SEVEN families including HTML heading levels — a blanket "qualify everything" rule is
// unimplementable. 🔬 Only THREE of the 211 make a RULING-STATE CLAIM, and a claim is the whole risk.
//
// ⚠️⚠️ MUTATION-PROVED BOTH WAYS, which is the only proof that counts: planting the exact sentence above in
// a file under `docs/` makes this RED, naming file, line, id and claimed state; writing it in the QUALIFIED
// form ("the reservation-ladder R2 is SATISFIED") passes. **The rule is satisfiable, not a trap** — a guard
// nobody can comply with is a guard somebody switches off.
it('⚠️⚠️ F-63 — a cross-file ruling-state claim must name which ladder it means', function () {
    $offenders = vyUnqualifiedCrossFileRulingClaims();

    $report = array_map(
        fn (array $o): string => sprintf(
            "%s:%d — %s is called %s, without saying WHICH %s\n      %s",
            $o['file'], $o['line'], $o['id'], $o['claimed'], $o['id'], $o['text']
        ),
        $offenders
    );

    // ⚠️⚠️ IT SHIPS WITH NO ALLOW-LIST AT ALL, AND THAT IS DELIBERATE. 🔬 Every cross-file ruling claim in the
    // record today is already qualified by its own sentence, so there was nothing to excuse. If this ever
    // needs an exception, read it as a signal first: the record is asserting a ruling state somewhere that
    // cannot say which ruling it means, and the fix is the sentence, not the list.
    expect($offenders)->toBe([],
        "⚠️⚠️ A RULING STATE IS ASSERTED WITHOUT SAYING WHICH LADDER IT BELONGS TO (F-63).\n"
        ."⚠️ `R<n>` names a DIFFERENT question in `HOUSEKEEPING-LADDER.md` and a third thing in `DECISIONS.md`,\n"
        ."⚠️ so a reader who searches lands on the answer to something else. Name the ladder — e.g.\n"
        ."⚠️ \"the reservation-ladder R2\" — rather than deleting the sentence:\n\n  "
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
