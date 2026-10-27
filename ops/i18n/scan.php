<?php

declare(strict_types=1);

// ops/i18n/scan.php — the command-line face of App\Support\I18n\TextScanner (the one scanner). The tools that require this
// file (check-file.php, the inventory) boot the app first, so the class autoloads; these names keep their call sites short.

use App\Support\I18n\TextScanner;

if (! function_exists('looksEnglish')) {
    function looksEnglish(...$a)
    {
        return TextScanner::looksEnglish(...$a);
    }
}
if (! function_exists('countKeys')) {
    function countKeys(...$a)
    {
        return TextScanner::countKeys(...$a);
    }
}
if (! function_exists('stringLiterals')) {
    function stringLiterals(...$a)
    {
        return TextScanner::stringLiterals(...$a);
    }
}
if (! function_exists('scanBlade')) {
    function scanBlade(...$a)
    {
        return TextScanner::scanBlade(...$a);
    }
}
if (! function_exists('looksEnglishText')) {
    function looksEnglishText(...$a)
    {
        return TextScanner::looksEnglishText(...$a);
    }
}
if (! function_exists('visiblePhpStrings')) {
    function visiblePhpStrings(...$a)
    {
        return TextScanner::visiblePhpStrings(...$a);
    }
}
if (! function_exists('scanPhp')) {
    function scanPhp(...$a)
    {
        return TextScanner::scanPhp(...$a);
    }
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    $f = $argv[1] ?? '';
    $src = (string) file_get_contents($f);
    $res = str_ends_with($f, '.blade.php') ? scanBlade($src) : scanPhp($src);
    echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
}
