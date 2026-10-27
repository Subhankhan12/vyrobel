<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;

// ops/i18n/check-file.php — I18N-01 Phase A: the check a converted file must pass before it is handed back.
//
//   php ops/i18n/check-file.php <file> [<file> …]          (paths relative to the repo)
//
// Per file: it COMPILES and PARSES (a Blade view through Laravel's own compiler, then the PHP tokenizer; a class through the
// tokenizer) · every literal key it uses EXISTS in lang/en (a missing one prints as a raw key on the page) · the English
// still hardcoded in it (scan.php — each must be converted or be genuinely not text: a brand, a code, people's content) ·
// GLUED: two translation calls side by side in a view (word order fixed in English) · RAWVAR: a {!! __(…, [ … ]) !!} whose
// replacement is not escaped with e() (an XSS hole: data printed raw). Exit 1 on any error; hardcoded strings are listed.

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
require __DIR__.'/scan.php';

$errors = 0;
foreach (array_slice($argv, 1) as $rel) {
    $f = str_starts_with($rel, '/') ? $rel : $root.'/'.$rel;
    if (! is_file($f)) {
        echo "ERROR  $rel: no such file\n";
        $errors++;

        continue;
    }
    $src = (string) file_get_contents($f);
    $isBlade = str_ends_with($f, '.blade.php');
    $problems = [];
    try {
        token_get_all($isBlade ? Blade::compileString($src) : $src, TOKEN_PARSE);
    } catch (Throwable $e) {
        $problems[] = 'PARSE '.$e->getMessage();
    }
    preg_match_all('/(?<![\w>$:])(?:__|trans|trans_choice|@lang|@choice)\s*\(\s*([\'"])([^\'"$]+?)\1/', $src, $m);
    foreach (array_unique($m[2]) as $key) {
        if (! str_contains($key, '.') || str_contains($key, ' ')) {
            $problems[] = "KEYFORM '$key' — a named key (group.item) is required, never the English text";

            continue;
        }
        if (! Lang::has($key, 'en', false)) {
            $problems[] = "MISSING $key";
        }
    }
    if ($isBlade) {
        if (preg_match_all('/\}\}\s*(?:<br\s*\/?>)?\s*\{\{\s*__\(/', $src, $g)) {
            $problems[] = 'GLUED '.count($g[0]).' place(s): two translations side by side — make it ONE key with placeholders';
        }
        if (preg_match_all('/\{!!\s*__\([^!]*?\[(.*?)\]\s*\)\s*!!\}/s', $src, $raw)) {
            foreach ($raw[1] as $repl) {
                foreach (preg_split('/,(?![^(\[]*[)\]])/', $repl) ?: [] as $pair) {
                    if (preg_match('/=>\s*(.+)$/s', trim($pair), $pv) && ! preg_match('/^\s*(e\(|\'[^\']*\'|"[^"]*"|\d|__\(|trans|route\(|url\(|Js::|htmlspecialchars|\(int\)|number_format|MoneyFormatter|\$\w+Html\b)/', $pv[1])) {
                        $problems[] = 'RAWVAR {!! !!} prints a replacement unescaped: '.trim(mb_strimwidth($pv[1], 0, 70, '…')).' — wrap it in e()';
                    }
                }
            }
        }
    }
    $left = $isBlade ? (function ($r) {
        return array_merge($r['text'], $r['attr'], $r['code'], array_map(fn ($s) => 'js: '.$s, $r['js']));
    })(scanBlade($src))
        : (function ($r) {
            return array_merge($r['visible'], array_map(fn ($s) => 'maybe: '.substr($s, 1), $r['maybe']));
        })(scanPhp($src));
    $errors += count($problems);
    echo ($problems === [] ? 'OK     ' : 'ERROR  ').$rel.'  ·  hardcoded left: '.count($left)."\n";
    foreach ($problems as $p) {
        echo "       ✗ $p\n";
    }
    foreach (array_slice($left, 0, 60) as $l) {
        echo '       · '.mb_strimwidth($l, 0, 140, '…')."\n";
    }
}
exit($errors > 0 ? 1 : 0);
