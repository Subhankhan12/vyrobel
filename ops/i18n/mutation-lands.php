<?php

declare(strict_types=1);

// ops/i18n/mutation-lands.php — which mutations no longer LAND (their target text is not in the file exactly once), per set.
// Static: it reads files only — no test run, no database — so it is safe beside anything. The same check the suite makes in
// tests/Feature/MutationSetsStillLandTest.php, through the same tests/Mutations/land.php. It does NOT prove a mutation is
// caught; that is `php tests/Mutations/run.php --only=<ids>` (one run at a time).
//
//   php ops/i18n/mutation-lands.php                 every set: dead ones, grouped by set
//   php ops/i18n/mutation-lands.php --set=units-five

chdir(dirname(__DIR__, 2));
require 'vendor/autoload.php';
require 'tests/Mutations/land.php';

$only = null;
foreach (array_slice($argv, 1) as $a) {
    if (str_starts_with($a, '--set=')) {
        $only = substr($a, 6);
    }
}

$dead = [];
$total = 0;
foreach (glob('tests/Mutations/sets/*.php') ?: [] as $file) {
    $set = basename($file, '.php');
    if ($only !== null && $set !== $only) {
        continue;
    }
    foreach (loadMutationSet($file) as $m) {
        $total++;
        if (! is_file($m['file'])) {
            $dead[$set][] = "{$m['id']}  {$m['file']} does not exist";

            continue;
        }
        $land = landMutation((string) file_get_contents($m['file']), $m['old'], $m['new']);
        if (! $land['ok']) {
            $dead[$set][] = "{$m['id']}  {$m['file']} — {$land['why']}";
        }
    }
}

foreach ($dead as $set => $rows) {
    echo "== {$set} (".count($rows).")\n  ".implode("\n  ", $rows)."\n";
}
$n = array_sum(array_map('count', $dead));
echo ($n === 0 ? 'OK' : 'DEAD')." — {$n} of {$total} mutation(s) do not land\n";
exit($n === 0 ? 0 : 1);
