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
            for ($k = $j; $k < $count; $k++) {
                $c = $tokens[$k];
                if ($c === '(' || $c === '[') {
                    $depth++;
                } elseif ($c === ')' || $c === ']') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                } elseif ($c === ',' && $depth === 1) {
                    $commas++;
                }
            }

            $found[] = [
                'file' => str_replace(base_path().'/', '', $file->getPathname()),
                'line' => (int) $token[2],
                'args' => $commas + 1,
                'negated' => $negated,
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
