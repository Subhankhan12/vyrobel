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
