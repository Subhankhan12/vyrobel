<?php

declare(strict_types=1);
use App\Support\Platform\ConsoleFields;

// ⚠️ ADMIN 03 — every console form names its fields in the words the admin sees (App\Support\Platform\ConsoleFields).
// A validate() that forgets the map puts "The organization id field is required" back on screen — nothing else would
// notice, because every other test asserts error KEYS, never the words. Tokenised, so a comment cannot satisfy it.

/** @return list<string> "file:line" of every console `->validate(` whose arguments do not pass ConsoleFields::NAMES */
function consoleValidateCallsWithoutFieldNames(): array
{
    $files = array_merge(
        glob(base_path('app/Http/Controllers/Admin/*.php')) ?: [],
        glob(base_path('app/Http/Controllers/Admin/Auth/*.php')) ?: [],
        [base_path('app/Support/Platform/AdminReason.php')],
    );

    $missing = [];
    foreach ($files as $file) {
        $tokens = array_values(array_filter(
            PhpToken::tokenize((string) file_get_contents($file)),
            fn (PhpToken $t) => ! $t->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]),
        ));

        foreach ($tokens as $i => $t) {
            if (! ($t->is(T_OBJECT_OPERATOR) && ($tokens[$i + 1] ?? null)?->text === 'validate' && ($tokens[$i + 2] ?? null)?->text === '(')) {
                continue;
            }
            $depth = 0;
            $passes = false;
            for ($j = $i + 2; $j < count($tokens); $j++) {
                $depth += match ($tokens[$j]->text) {
                    '(', '[' => 1, ')', ']' => -1, default => 0
                };
                if ($tokens[$j]->text === 'ConsoleFields' && in_array(($tokens[$j + 2] ?? null)?->text, ['NAMES', 'names'], true)) {   // I18N-01: names() = the map in the reader's language
                    $passes = true;
                }
                if ($depth === 0) {
                    break;
                }
            }
            if (! $passes) {
                $missing[] = str_replace(base_path().'/', '', $file).':'.$t->line;
            }
        }
    }

    return $missing;
}

it('⚠️ every console validate() passes ConsoleFields::NAMES — the instrument sees the calls, and none is missing it', function () {
    // The instrument must be seeing something: a scan that finds no calls would pass while blind.
    $all = 0;
    foreach (array_merge(glob(base_path('app/Http/Controllers/Admin/*.php')) ?: [], glob(base_path('app/Http/Controllers/Admin/Auth/*.php')) ?: []) as $f) {
        $all += substr_count((string) file_get_contents($f), '->validate(');
    }
    expect($all)->toBeGreaterThan(20)
        ->and(consoleValidateCallsWithoutFieldNames())->toBe([]);
})->group('security');

it('⚠️ every conditional rule (required_if) in the console has a sentence — never "…when about is relayed_guest"', function () {
    $files = array_merge(glob(base_path('app/Http/Controllers/Admin/*.php')) ?: [], [base_path('app/Support/Billing/PlanIntegrity.php')]);
    $fields = [];
    foreach ($files as $f) {
        preg_match_all("/'([a-z_.*]+)'\s*=>\s*\[\s*'required_if:/", (string) file_get_contents($f), $m);
        $fields = array_merge($fields, $m[1]);
    }
    expect(count($fields))->toBeGreaterThanOrEqual(5);   // the scan must be seeing the rules

    $missing = array_values(array_filter($fields, fn (string $f) => ! isset(ConsoleFields::MESSAGES[$f.'.required_if'])));
    expect($missing)->toBe([]);
})->group('security');
