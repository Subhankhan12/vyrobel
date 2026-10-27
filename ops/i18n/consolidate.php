<?php

declare(strict_types=1);

// ops/i18n/consolidate.php — I18N-01: apply the consistency editor's fixes to one language.
//
//   php ops/i18n/consolidate.php <lang> <fixes.json>      fixes.json: {"<id>": ["<expected old>", "<new>", "<why>"]}
//
// Refuses a fix whose OLD text no longer matches (someone changed it meanwhile — stale-safe), and reports it.

require __DIR__.'/lib.php';

[$lang, $fixFile] = [$argv[1] ?? '', $argv[2] ?? ''];
$work = VY_I18N_WORK;
$fixes = vyI18nJson($fixFile);
$applied = $stale = 0;
foreach (glob("$work/out/$lang/*.json") ?: [] as $f) {
    $out = vyI18nJson($f);
    $changed = false;
    foreach ($fixes as $id => [$old, $new]) {
        if (! array_key_exists((string) $id, $out)) {
            continue;
        }
        if ($out[(string) $id] !== $old) {
            echo "STALE $id: expected «{$old}», found «{$out[(string) $id]}»\n";
            $stale++;

            continue;
        }
        $out[(string) $id] = $new;
        $changed = true;
        $applied++;
    }
    if ($changed) {
        vyI18nWriteJson($f, $out);
    }
}
echo "$lang: $applied fixes applied · $stale stale\n";
exit($stale > 0 ? 1 : 0);
