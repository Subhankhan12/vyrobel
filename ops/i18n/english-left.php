<?php

declare(strict_types=1);
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Lang;

// ops/i18n/english-left.php — I18N-01 Phase D: what English is still on a translated page?
//
//   php ops/i18n/english-left.php <english-render-dir> <lang-render-dir> <lang> [max-per-class=40]
//
// Compares two render-all.php runs (English, and the language) page by page. A visible line that is IDENTICAL in both runs is
// English left on the translated page — or text that is legitimately the same (a name, a code, a number, people's content).
// Each identical line is classed:
//   LEAK      — it IS the English value of a key whose translation differs: the page should have said the translation, so
//               the text bypasses the locale (a hardcoded copy, a value stored in English, a locale not set). Must be 0.
//   SAME      — the English value of a key whose translation is identical on purpose (declared in out/<lang>/same/).
//   DATE      — a date with an English month or weekday the language does not use: format() where translatedFormat() belongs.
//   UNKEYED   — no key has this text: people's content, seeded demo data, brand, or visible English not yet a key.
// Lines without a letter (numbers, dates, money, codes) are skipped. Prints the classes with the pages each line was seen on.

require __DIR__.'/lib.php';

[$enDir, $lgDir, $lang] = [rtrim($argv[1] ?? '', '/'), rtrim($argv[2] ?? '', '/'), $argv[3] ?? ''];
$max = (int) ($argv[4] ?? 40);
if (! is_dir($enDir) || ! is_dir($lgDir) || $lang === '') {
    fwrite(STDERR, "usage: php ops/i18n/english-left.php <english-render-dir> <lang-render-dir> <lang>\n");
    exit(2);
}

$en = vyI18nEnglish();
$root = vyI18nRoot();
// the language's own values, read from lang/<lang> the same way Laravel does
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$byEnglish = [];   // normalised English text → [translation differs?]
foreach ($en as $key => $text) {
    $t = Lang::get($key, [], $lang, false);
    if (! is_string($t) || str_contains($text, ':') || vyI18nIsPlural($text)) {
        continue;   // parameterised and plural values never appear on a page verbatim
    }
    $norm = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($norm === '') {
        continue;
    }
    $differs = $t !== $text;
    $byEnglish[$norm] = ($byEnglish[$norm] ?? false) || $differs;
}

// a date printed by format() instead of translatedFormat() carries an ENGLISH month or weekday the language does not use
$englishNames = [];
$ownNames = [];
foreach (range(1, 12) as $m) {
    $d = CarbonImmutable::create(2026, $m, 1);
    $englishNames[] = $d->locale('en')->translatedFormat('M');
    $englishNames[] = $d->locale('en')->translatedFormat('F');
    $ownNames[] = mb_strtolower($d->locale($lang)->translatedFormat('M'));
    $ownNames[] = mb_strtolower($d->locale($lang)->translatedFormat('F'));
}
foreach (range(0, 6) as $i) {
    $d = CarbonImmutable::create(2026, 10, 25)->addDays($i);
    $englishNames[] = $d->locale('en')->translatedFormat('D');
    $englishNames[] = $d->locale('en')->translatedFormat('l');
    $ownNames[] = mb_strtolower($d->locale($lang)->translatedFormat('D'));
    $ownNames[] = mb_strtolower($d->locale($lang)->translatedFormat('l'));
}
$foreignDate = '/\b('.implode('|', array_diff(array_unique($englishNames), array_map('ucfirst', $ownNames), $ownNames)).')\b/';

$classes = ['LEAK' => [], 'DATE' => [], 'SAME' => [], 'UNKEYED' => []];
$pages = 0;
foreach (glob("$lgDir/*.txt") ?: [] as $lf) {
    $name = basename($lf);
    if ($name === '_problems.txt' || str_ends_with($name, '.csv.txt') || ! is_file("$enDir/$name")) {
        continue;
    }
    $pages++;
    $enLines = array_flip(array_map('trim', file("$enDir/$name", FILE_IGNORE_NEW_LINES) ?: []));
    foreach (array_unique(array_map('trim', file($lf, FILE_IGNORE_NEW_LINES) ?: [])) as $line) {
        if ($line === '' || ! isset($enLines[$line]) || ! preg_match('/\p{L}{2,}/u', $line)) {
            continue;
        }
        $class = array_key_exists($line, $byEnglish) ? ($byEnglish[$line] ? 'LEAK' : 'SAME')
            : (preg_match('/\d/', $line) && preg_match($foreignDate, $line) ? 'DATE' : 'UNKEYED');
        $classes[$class][$line][] = preg_replace('/\.txt$/', '', $name);
    }
}

echo "$lang: $pages page(s) compared with English\n";
foreach ($classes as $class => $lines) {
    uasort($lines, fn ($a, $b) => count($b) <=> count($a));
    echo "\n== $class: ".count($lines)." distinct line(s)\n";
    foreach (array_slice($lines, 0, $max, true) as $line => $where) {
        echo '  ['.count($where).'] '.mb_strimwidth($line, 0, 90, '…').'   e.g. '.$where[0]."\n";
    }
}
exit(count($classes['LEAK']) + count($classes['DATE']) === 0 ? 0 : 1);
