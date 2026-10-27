<?php

declare(strict_types=1);

// ops/i18n/render-diff.php — I18N-01: compare two render-all.php runs. Exit 0 only when every page's status AND visible
// text are identical (the Phase A proof that English did not move). Prints each difference, page by page.
//
//   php ops/i18n/render-diff.php <before-dir> <after-dir> [max-lines-per-page=12] [subset]
// `subset`: compare only the pages the AFTER run rendered (a run limited to one realm) — never for the final proof.

[$a, $b] = [rtrim($argv[1] ?? '', '/'), rtrim($argv[2] ?? '', '/')];
$max = (int) ($argv[3] ?? 12);
$subset = ($argv[4] ?? '') === 'subset';
if (! is_dir($a) || ! is_dir($b)) {
    fwrite(STDERR, "usage: php ops/i18n/render-diff.php <before-dir> <after-dir>\n");
    exit(2);
}
$rows = function (string $dir): array {
    $out = [];
    foreach (file("$dir/_index.tsv", FILE_IGNORE_NEW_LINES) ?: [] as $l) {
        $c = explode("\t", $l);
        if (count($c) >= 3) {
            $out[$c[0]."\t".$c[1]] = $c[2].(isset($c[4]) && str_contains($c[4], '→') ? ' '.substr($c[4], strpos($c[4], '→')) : '');
        }
    }

    return $out;
};
[$ra, $rb] = [$rows($a), $rows($b)];
$diffs = 0;
$wrapOnly = 0;
if ($subset) {
    $ra = array_intersect_key($ra, $rb);
}
foreach (array_unique([...array_keys($ra), ...array_keys($rb)]) as $k) {
    if (($ra[$k] ?? 'absent') !== ($rb[$k] ?? 'absent')) {
        $diffs++;
        echo "STATUS  $k: ".($ra[$k] ?? 'absent').' → '.($rb[$k] ?? 'absent')."\n";
    }
}
$files = array_unique([...($subset ? [] : array_map('basename', glob("$a/*.txt") ?: [])), ...array_map('basename', glob("$b/*.txt") ?: [])]);
sort($files);
foreach ($files as $f) {
    $ta = is_file("$a/$f") ? file("$a/$f", FILE_IGNORE_NEW_LINES) : [];
    $tb = is_file("$b/$f") ? file("$b/$f", FILE_IGNORE_NEW_LINES) : [];
    if ($ta === $tb) {
        continue;
    }
    // the same words with different line breaks (a sentence once wrapped over two source lines, now one key) is the same
    // page to a reader: counted apart, never as a difference — attribute lines stay their own lines either way
    $norm = fn (array $lines) => trim(preg_replace('/\s+/u', ' ', implode(' ', $lines)) ?? '');
    if ($norm($ta) === $norm($tb)) {
        $wrapOnly++;

        continue;
    }
    $diffs++;
    echo "TEXT    $f\n";
    $gone = array_diff($ta, $tb);
    $new = array_diff($tb, $ta);
    $n = 0;
    foreach ($gone as $l) {
        if ($n++ < $max) {
            echo "   - $l\n";
        }
    }
    foreach ($new as $l) {
        if ($n++ < $max) {
            echo "   + $l\n";
        }
    }
    if ($gone === [] && $new === []) {
        echo "   (same lines, different order or count)\n";
    }
}
$wrapNote = $wrapOnly > 0 ? " ($wrapOnly with the same words, only line breaks moved)" : '';
echo $diffs === 0 ? 'IDENTICAL — '.count($ra).' renders, '.count($files)." pages of text$wrapNote\n" : "DIFFERENT — $diffs page(s)$wrapNote\n";
exit($diffs === 0 ? 0 : 1);
