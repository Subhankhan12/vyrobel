<?php

declare(strict_types=1);

// ops/i18n/consistency.php — I18N-01: SHORT English texts (labels, buttons: ≤ max words) translated more than one way across
// batches. Long sentences legitimately vary with context; one short UI term must not. Input for the consistency editor.
//
//   php ops/i18n/consistency.php <lang> [max-words=4]

require __DIR__.'/lib.php';

$lang = $argv[1] ?? '';
$max = (int) ($argv[2] ?? 4);
$work = VY_I18N_WORK;
$seen = [];
foreach (glob("$work/batches/*.json") ?: [] as $bf) {
    $out = vyI18nJson("$work/out/$lang/".basename($bf));
    foreach (vyI18nJson($bf) as $row) {
        $en = (string) $row['en'];
        $t = $out[(string) $row['id']] ?? null;
        if ($t === null || str_word_count(strip_tags($en)) > $max) {
            continue;
        }
        $seen[mb_strtolower(trim($en))][(string) $t][] = $row['id'].' '.$row['key'];
    }
}
$n = 0;
ksort($seen);
foreach ($seen as $en => $variants) {
    if (count($variants) < 2) {
        continue;
    }
    $n++;
    echo "\"$en\"\n";
    foreach ($variants as $t => $where) {
        echo '   '.count($where)."× $t  — ".implode('; ', array_slice($where, 0, 3)).(count($where) > 3 ? ' …' : '')."\n";
    }
}
echo "$lang: $n short texts with more than one translation\n";
