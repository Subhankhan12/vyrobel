<?php

use Illuminate\Support\Facades\File;

// ══ AUDIT 2026-08-29 · H-4 — MAKE THE SHAPE MECHANICALLY IMPOSSIBLE ══════════════════════════════════
//
// ⚠️ Pest's `toContain(mixed ...$needles)` and `toContainEqual(mixed ...$needles)` are VARIADIC and take
// NO $message. Passing a human-readable message therefore adds a SECOND NEEDLE — and the NEGATED form
// `->not->toContain(A, B)` passes as soon as EITHER is absent. Since a written-English message can never
// occur in the subject, such an assertion is UNCONDITIONALLY TRUE and proves nothing.
//
// ⚠️ THIS BUG WAS FOUND, FIXED, DOCUMENTED AND WARNED ABOUT IN TWO FILES — AND RECURRED ANYWAY, on the
// PII log guard (LogPiiRedactionTest). A comment is not a control. This test is the control.
//
// ⚠️ SCOPE, DELIBERATELY NARROW: only the NEGATED multi-argument form is banned. The POSITIVE multi-arg
// form is legitimate and in real use here (4 sites) — it requires every needle to appear. Those two
// matchers are the ONLY variadic matchers Pest defines, so this rule is complete rather than a sample.
//
// ⚠️ §N.27.5 — a source-scanning guard must read what RUNS, not what is written ABOUT it: this scans the
// TOKEN STREAM, so a matcher named in a comment (including the long explanation above) cannot trip it.

/**
 * Every `toContain`/`toContainEqual` call in the test tree: file, line, arg count, negated?
 *
 * @return list<array{file: string, line: int, args: int, negated: bool}>
 */
function variadicMatcherCalls(): array
{
    $found = [];

    foreach (File::allFiles(base_path('tests')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $tokens = token_get_all(File::get($file->getPathname()));
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (! is_array($token) || $token[0] !== T_STRING
                || ! in_array($token[1], ['toContain', 'toContainEqual'], true)) {
                continue;
            }

            // Negated when `not` appears in the immediately preceding method chain.
            $negated = false;
            for ($k = $i - 1; $k >= max(0, $i - 4); $k--) {
                if (is_array($tokens[$k]) && $tokens[$k][0] === T_STRING && $tokens[$k][1] === 'not') {
                    $negated = true;
                    break;
                }
            }

            // Walk to the opening paren, then count TOP-LEVEL commas (nested calls/arrays don't count).
            $j = $i + 1;
            while ($j < $count && $tokens[$j] !== '(') {
                $j++;
            }
            if ($j >= $count) {
                continue;
            }

            $depth = 0;
            $commas = 0;
            $sentenceAfterFirst = null;   // a message-shaped literal in ANY argument after the first
            for ($k = $j; $k < $count; $k++) {
                $c = $tokens[$k];
                // ⚠️⚠️ BRACES COUNT TOO, AND THE FIRST VERSION OF THIS WALKER FORGOT THEM. 🔬 It tracked only
                //    `(` and `[`, so `toContain(match ($x) { 'a' => '…', 'b' => '…' })` — ONE argument — had
                //    its match-arm commas counted as TOP-LEVEL and was reported as a 4-argument call. It
                //    fired on `ReservationLadderTest:73`, a correct assertion, the moment the new rule ran.
                //    ⚠️ The miscount was latent in the NEGATED ban above from the day it was written; no
                //    negated `match` call happened to exist, so nothing revealed it. **An instrument that
                //    does not understand the syntax it parses is the recurring shape in this project.**
                if ($c === '(' || $c === '[' || $c === '{') {
                    $depth++;
                } elseif ($c === ')' || $c === ']' || $c === '}') {
                    $depth--;
                    if ($depth === 0 && $c === ')') {
                        break;   // ⚠️ the CALL's own closing paren, never a stray `}` at the same depth
                    }
                } elseif ($c === ',' && $depth === 1) {
                    $commas++;
                } elseif ($commas >= 1 && is_array($c) && $c[0] === T_CONSTANT_ENCAPSED_STRING) {
                    // ⚠️ WHAT COUNTS AS A MESSAGE RATHER THAN A NEEDLE, AND WHY THIS SHAPE. A needle is short
                    //    and usually a token: 'no_show', 'closed_to_arrival', even 'Aaron Busy'. A human
                    //    message is a SENTENCE. 🔬 Calibrated against every positive multi-arg site in this
                    //    suite — the longest legitimate needle is 23 characters with no spaces — so
                    //    "≥30 characters AND ≥3 spaces" separates them with room to spare, and is checked
                    //    per-argument so a concatenated message ('…: '.json_encode($x)) is caught by its
                    //    leading literal.
                    $lit = trim($c[1], "'\"");
                    if (strlen($lit) >= 30 && substr_count($lit, ' ') >= 3) {
                        $sentenceAfterFirst ??= $lit;
                    }
                }
            }

            $found[] = [
                'file' => str_replace(base_path().'/', '', $file->getPathname()),
                'line' => (int) $token[2],
                'args' => $commas + 1,
                'negated' => $negated,
                'sentence' => $sentenceAfterFirst,
            ];
        }
    }

    return $found;
}

// ── 1. ⛔ THE BAN — a negated multi-argument variadic matcher fails the build ────────────────────────
it('bans a negated multi-argument toContain, which passes on its own message', function () {
    $offenders = array_values(array_filter(
        variadicMatcherCalls(),
        fn (array $c): bool => $c['negated'] && $c['args'] > 1,
    ));

    $detail = implode(', ', array_map(fn (array $c): string => "{$c['file']}:{$c['line']} ({$c['args']} args)", $offenders));

    expect($offenders)->toBe([],
        'A negated multi-arg toContain/toContainEqual is UNCONDITIONALLY TRUE — the message becomes a '
        ."second needle. Use expect(str_contains(\$subject, \$needle))->toBeFalse(\$message). Found: {$detail}");
});

// ── 2. ⚠️ THE GUARD IS NOT ITSELF VACUOUS ───────────────────────────────────────────────────────────
// An empty scan would satisfy test 1 for the wrong reason — the exact defect class this file exists for.
// So pin that the scanner really reached the suite and really parsed calls.
it('actually scanned the test tree and found calls to inspect', function () {
    $calls = variadicMatcherCalls();

    expect(count($calls))->toBeGreaterThan(500)                       // ~1,347 sites today
        ->and(count(array_filter($calls, fn (array $c): bool => $c['args'] > 1)))->toBeGreaterThan(0)
        ->and(count(array_unique(array_column($calls, 'file'))))->toBeGreaterThan(100);
});

// ── 3. the positive multi-arg form stays LEGAL — this rule must not ban a correct pattern ───────────
it('leaves the positive multi-argument form alone', function () {
    $positiveMulti = array_filter(
        variadicMatcherCalls(),
        fn (array $c): bool => ! $c['negated'] && $c['args'] > 1,
    );

    expect(count($positiveMulti))->toBeGreaterThan(0);   // real, in use, and correct
});

// ── 4. ⚠️⚠️ THE POSITIVE FORM WITH A MESSAGE — FOURTH OCCURRENCE, AND THE ONE THIS FILE LET THROUGH ──
//
// ⚠️⚠️ THIS FILE'S ORIGINAL SCOPE WAS DELIBERATELY NARROW — "only the NEGATED multi-argument form is banned"
// — and that was right for what it knew. ⚠️ It could not catch the POSITIVE form, and on 2026-09-22 the
// positive form bit: `expect($msg)->toContain('already reversed', "the loser failed for the wrong reason: ".json_encode($loser))`.
//
// ⚠️⚠️ THE TWO FORMS FAIL IN OPPOSITE DIRECTIONS, WHICH IS WHY BOTH NEED BANNING AND WHY ONLY ONE WAS:
//   · NEGATED + message → the assertion is UNCONDITIONALLY TRUE. Silent. Proves nothing. (Banned above.)
//   · POSITIVE + message → UNCONDITIONALLY FALSE. Loud — but it fails while pointing at the SUBJECT, so the
//     reader concludes the subject is wrong when the ASSERTION is malformed.
//
// ⚠️ AND WHERE IT BIT IS THE part worth keeping: inside the test written to enforce failing-for-the-right-
// reason. **The guard against wrong-reason failures failed for the wrong reason.** A fourth occurrence, by
// this project's own counting rule, is what turns a note into a mechanism.
it('⚠️⚠️ bans a toContain whose extra argument is a human message rather than a needle', function () {
    $offenders = array_values(array_filter(
        variadicMatcherCalls(),
        fn (array $c): bool => $c['args'] > 1 && $c['sentence'] !== null,
    ));

    $detail = implode("\n      ", array_map(
        fn (array $c): string => "{$c['file']}:{$c['line']} — extra arg begins \"".substr($c['sentence'], 0, 60).'…"',
        $offenders
    ));

    expect($offenders)->toBe([],
        "⚠️⚠️ A `toContain`/`toContainEqual` CALL IS PASSING A MESSAGE AS A NEEDLE.\n"
        ."⚠️ The matcher is VARIADIC and takes NO \$message — the sentence becomes a second thing the subject\n"
        ."   must contain, so the assertion can never pass and fails while blaming the subject.\n"
        ."⚠️ Use: expect(str_contains(\$subject, \$needle))->toBeTrue(\$message)\n\n      ".$detail."\n");
});

// ── 5. ⚠️ THE NEW RULE IS NOT VACUOUS, AND DOES NOT BAN A REAL NEEDLE ───────────────────────────────
it('⚠️ still permits a legitimate multi-needle call, including needles that contain a space', function () {
    // ⚠️ THE CALIBRATION, ASSERTED RATHER THAN CLAIMED. `toContain('Aaron Busy', 'Zoe Free')` is a real,
    //    correct call in this suite: two needles, one space each. A rule that banned it would be worse than
    //    the bug — it would push people away from a correct pattern.
    $calls = variadicMatcherCalls();
    $multi = array_filter($calls, fn (array $c): bool => $c['args'] > 1);

    expect(count($multi))->toBeGreaterThan(0)                                  // the scan really sees them
        ->and(count(array_filter($multi, fn (array $c): bool => $c['sentence'] === null)))
        ->toBeGreaterThan(0, 'every multi-arg call was flagged — the sentence heuristic is too broad');
});
