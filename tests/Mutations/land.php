<?php

declare(strict_types=1);

// The ONE landing rule — read by the harness (tests/Mutations/run.php) and by MutationSetsStillLandTest, so the suite
// and the harness cannot disagree about what "landed" means.

if (! function_exists('landMutation')) {
    /**
     * The landing rule, pure — the self-check proves it on every run.
     *
     * @return array{ok: bool, why: string, mutated: string}
     */
    function landMutation(string $original, string $old, string $new): array
    {
        $count = $old === '' ? 0 : substr_count($original, $old);
        if ($count !== 1) {
            return ['ok' => false, 'why' => "target text found {$count}× (must be exactly 1)", 'mutated' => $original];
        }
        if ($old === $new) {
            return ['ok' => false, 'why' => 'the mutation is a no-op (old === new)', 'mutated' => $original];
        }
        $mutated = str_replace($old, $new, $original);
        if ($mutated === $original) {
            return ['ok' => false, 'why' => 'the mutated file is identical to the original', 'mutated' => $original];
        }

        return ['ok' => true, 'why' => '', 'mutated' => $mutated];
    }
}

if (! function_exists('loadMutationSet')) {
    /**
     * ONE set file, required in a scope of its OWN — read by the harness and by MutationSetsStillLandTest.
     *
     * ⚠️ A required file runs in the scope that requires it, so a set's top-level helper variables (`$t`, `$ctl`, …) used to
     * land in its caller's: 2026-09-30, the units-list set's `$rows` (a file path) overwrote the list MutationSetsStillLandTest
     * was building, and the suite errored on "[] operator not supported for strings". Here a set can only overwrite `$path`,
     * which has already been read.
     *
     * @return list<array<string, mixed>>
     */
    function loadMutationSet(string $path): array
    {
        return require $path;
    }
}
