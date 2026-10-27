<?php

declare(strict_types=1);

// ops/i18n/apply.php — I18N-01: write one language's translations into the app.
//
//   php ops/i18n/apply.php <lang> [--dry]
//
// For every English group (lang/en/**.php) writes lang/<lang>/<group>.php with the SAME keys in the SAME order, from
// ~/vyrobel-local/i18n/out/<lang>/*.json (ids via data/manifest.json). A key with no translation yet is left out — Laravel
// shows its English (lang:todo lists it as NEW). Refuses to apply while check.php reports errors for that language.
// Records the hash of each applied key's ENGLISH text in lang/.applied/<lang>.json: when the English changes later,
// lang:todo reports the translation as CHANGED (stale) instead of letting it say something the English no longer says.

require __DIR__.'/lib.php';

$lang = $argv[1] ?? '';
$dry = in_array('--dry', $argv, true);
if ($lang === '' || $lang === 'en' || ! preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $lang)) {
    fwrite(STDERR, "usage: php ops/i18n/apply.php <lang> [--dry]\n");
    exit(2);
}
passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__DIR__.'/check.php').' '.escapeshellarg($lang).' > /dev/null', $rc);
if ($rc !== 0 && ! $dry) {
    fwrite(STDERR, "REFUSED: php ops/i18n/check.php $lang reports errors — fix them first.\n");
    exit(1);
}
$work = VY_I18N_WORK;
$root = vyI18nRoot();
$manifest = vyI18nJson("$work/data/manifest.json");
$byId = [];
foreach (glob("$work/out/$lang/*.json") ?: [] as $f) {
    foreach (vyI18nJson($f) as $id => $t) {
        $byId[(string) $id] = (string) $t;
    }
}
$en = vyI18nEnglish();
// group = everything before the FIRST dot after the last slash
$groupOf = function (string $key): string {
    $slash = strrpos(substr($key, 0, (int) strpos($key.'.', '.', (int) strrpos($key, '/'))), '/');
    $afterSlash = $slash === false ? 0 : $slash + 1;

    return substr($key, 0, (int) strpos($key, '.', $afterSlash));
};
$out = [];
$hashes = [];
$count = 0;
foreach ($en as $key => $text) {
    $id = isset($manifest[$key]) ? (string) $manifest[$key] : null;
    if ($id === null || ! isset($byId[$id])) {
        continue;
    }
    $g = $groupOf($key);
    $out[$g][substr($key, strlen($g) + 1)] = $byId[$id];
    $hashes[$key] = substr(sha1($text), 0, 16);
    $count++;
}
foreach ($out as $g => $flat) {
    $file = "$root/lang/$lang/$g.php";
    if (! $dry) {
        @mkdir(dirname($file), 0755, true);
        file_put_contents($file, vyI18nPhpFile($flat, "I18N-01 — $lang translation of lang/en/$g.php (written by ops/i18n/apply.php; edit through the pipeline, not by hand)"));
    }
}
if (! $dry) {
    ksort($hashes);
    vyI18nWriteJson("$root/lang/.applied/$lang.json", $hashes);
}
echo ($dry ? '[dry] ' : '')."$lang: $count of ".count($en).' keys applied into '.count($out)." files\n";
