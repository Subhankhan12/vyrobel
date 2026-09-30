<?php

declare(strict_types=1);

require_once __DIR__.'/../Mutations/land.php';

// ⚠️⚠️ ADMIN 03B — a mutation whose target text moved is a mutation that tests nothing, and nothing said so: five of
// 115 went silent this way (a Phase 3 refactor, then ADMIN 03's own edits) and were found only because someone happened
// to re-run the scratchpad scripts. The harness (tests/Mutations/run.php) now refuses to count one — but it only runs
// when someone runs it. THIS runs with the suite: move a guarded line, and the suite goes red in the same change until
// the mutation is re-anchored. It does not run a single mutation; it checks that every one of them still CAN.

/** @return list<array<string, mixed>> */
function mutationSets(): array
{
    $rows = [];
    foreach (glob(base_path('tests/Mutations/sets/*.php')) ?: [] as $file) {
        foreach (loadMutationSet($file) as $row) {   // its own scope — a set's variables cannot reach `$rows` here
            $rows[] = $row + ['set' => basename($file, '.php')];
        }
    }

    return $rows;
}

it('⚠️⚠️ every mutation still LANDS — its target text exists exactly once, and the mutation changes the file', function () {
    $rows = mutationSets();
    expect(count($rows))->toBeGreaterThanOrEqual(132);   // the instrument must be reading the sets, not nothing

    $dead = [];
    foreach ($rows as $m) {
        $path = base_path($m['file']);
        if (! is_file($path)) {
            $dead[] = "{$m['id']} — {$m['file']} does not exist";

            continue;
        }
        $land = landMutation((string) file_get_contents($path), $m['old'], $m['new']);
        if (! $land['ok']) {
            $dead[] = "{$m['id']} ({$m['name']}) — {$land['why']}";
        }
    }

    expect($dead)->toBe([], "these mutations no longer land — re-anchor each to the moved code so ONLY its guard changes:\n".implode("\n", $dead));
})->group('security');

it('every mutation names a unique id and an aimed test file that exists', function () {
    $rows = mutationSets();
    $ids = array_column($rows, 'id');
    expect(count($ids))->toBe(count(array_unique($ids)));

    $missing = [];
    foreach ($rows as $m) {
        expect($m['tests'])->not->toBe([]);
        foreach ($m['tests'] as $spec) {
            if (! is_file(base_path($spec['file']))) {
                $missing[] = "{$m['id']} → {$spec['file']}";
            }
        }
    }
    expect($missing)->toBe([]);
});

it('the landing rule refuses what proves nothing — a missing, doubled, empty or no-op target', function () {
    expect(landMutation("a\nb\n", 'zzz', 'y')['ok'])->toBeFalse()
        ->and(landMutation("x\nx\n", 'x', 'y')['ok'])->toBeFalse()
        ->and(landMutation("a\nb\n", '', 'y')['ok'])->toBeFalse()
        ->and(landMutation("a\nb\n", 'a', 'a')['ok'])->toBeFalse()
        ->and(landMutation("a\nb\n", 'a', 'c'))->toBe(['ok' => true, 'why' => '', 'mutated' => "c\nb\n"]);
});

it('⚠️ loads each set in a scope of its own — a set\'s variables cannot reach the code that loads it', function () {
    // 2026-09-30: the units-list set's top-level `$rows = '…UnitRows.php'` overwrote mutationSets()'s own `$rows`, and both
    // tests above errored ("[] operator not supported for strings") — the loader, not the set, was the thing to fix.
    $probe = (string) tempnam(sys_get_temp_dir(), 'vyset');
    file_put_contents($probe, "<?php\n\$rows = 'a file path';\n\$row = 7;\n\nreturn [['id' => 'PROBE']];\n");

    try {
        $rows = ['kept'];
        $row = 'kept too';
        $loaded = loadMutationSet($probe);
    } finally {
        unlink($probe);
    }

    expect($loaded)->toBe([['id' => 'PROBE']])
        ->and($rows)->toBe(['kept'])
        ->and($row)->toBe('kept too');
});
