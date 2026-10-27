<?php

declare(strict_types=1);

// ops/i18n/export.php — I18N-01: number every English key ONCE and pack the keys not yet in a batch into new batches.
//
//   php ops/i18n/export.php [words-per-batch=2200]
//
// Writes ~/vyrobel-local/i18n/data/manifest.json ({key: id}) and batches/NNN.json ([{id, key, en, ctx, kind}]).
// ⚠️ IDS NEVER MOVE: an existing key keeps its id and its batch forever (hrmzy's rule — renumbering after translation began
// detaches every translation from its text). A key whose English CHANGED keeps its id; lang:todo reports it as CHANGED.
// ctx = the file (and line) that uses the key, read from the code — so the translator knows the screen; kind = what it is.

require __DIR__.'/lib.php';

$wordsPer = (int) ($argv[1] ?? 2200);
$work = VY_I18N_WORK;
$root = vyI18nRoot();
$en = vyI18nEnglish();
$manifest = vyI18nJson("$work/data/manifest.json");
$next = $manifest === [] ? 1 : max($manifest) + 1;

// where each key is used: one pass over the code
$usage = [];
foreach ([$root.'/app', $root.'/resources/views'] as $dir) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $fi) {
        $f = $fi->getPathname();
        if (! str_ends_with($f, '.php')) {
            continue;
        }
        foreach (file($f) ?: [] as $n => $line) {
            if (preg_match_all('/(?:__|trans_choice|trans|@lang)\s*\(\s*[\'"]([^\'"$]+)[\'"]/', $line, $m)) {
                foreach ($m[1] as $k) {
                    $usage[$k] ??= str_replace($root.'/', '', $f).':'.($n + 1);
                }
            }
        }
    }
}

$kind = function (string $key, string $en): string {
    $item = substr($key, strrpos($key, '.') + 1);

    return match (true) {
        vyI18nIsPlural($en) => 'plural',
        str_starts_with($key, 'validation.') || str_starts_with($key, 'auth.') || str_starts_with($key, 'passwords.') || str_starts_with($key, 'pagination.') => 'framework message',
        str_contains($key, 'emails/') || str_contains($key, '/mail/') => 'email',
        str_contains($key, 'pdfs/') => 'PDF',
        (bool) preg_match('/(button|_cta|^save|^cancel|^confirm|^delete|^add|^close)/', $item) => 'button',
        (bool) preg_match('/(title|heading)/', $item) => 'title',
        (bool) preg_match('/(placeholder)/', $item) => 'placeholder',
        (bool) preg_match('/(aria|label)/', $item) => 'label',
        (bool) preg_match('/(empty|none|no_)/', $item) => 'empty state',
        str_word_count($en) <= 3 => 'label',
        default => 'sentence',
    };
};

$new = [];
foreach ($en as $key => $text) {
    if (! isset($manifest[$key])) {
        $manifest[$key] = $next++;
        $new[] = $key;
    }
}

$existing = glob("$work/batches/*.json") ?: [];
$batchNo = $existing === [] ? 1 : (int) basename(end($existing), '.json') + 1;
$cur = [];
$words = 0;
$written = [];
$flush = function () use (&$cur, &$words, &$batchNo, &$written, $work) {
    if ($cur !== []) {
        $name = sprintf('%03d', $batchNo++);
        vyI18nWriteJson("$work/batches/$name.json", $cur);
        $written[] = "$name (".count($cur).' strings)';
    }
    $cur = [];
    $words = 0;
};
$lastGroup = null;
foreach ($new as $key) {
    $group = substr($key, 0, strrpos($key, '.'));
    $w = str_word_count(strip_tags($en[$key])) + 1;
    if ($words + $w > $wordsPer && $group !== $lastGroup) {
        $flush();
    }
    $cur[] = ['id' => $manifest[$key], 'key' => $key, 'en' => $en[$key], 'ctx' => $usage[$key] ?? 'lang/en only (framework or unused)', 'kind' => $kind($key, $en[$key])];
    $words += $w;
    $lastGroup = $group;
}
$flush();
vyI18nWriteJson("$work/data/manifest.json", $manifest);

$unused = array_filter(array_keys($en), fn ($k) => ! isset($usage[$k]) && ! preg_match('/^(validation|auth|passwords|pagination)\./', $k));
echo 'English keys: '.count($en).' · new: '.count($new).' · batches written: '.($written ? implode(', ', $written) : 'none')
    .' · keys no code uses (REMOVED candidates): '.count($unused)."\n";
