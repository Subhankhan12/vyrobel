<?php

declare(strict_types=1);

// ops/i18n/lib.php — I18N-01: shared helpers for the translation pipeline (export, check, apply, consistency) and the
// lang:check / lang:todo commands. Plain PHP, no app boot needed. The English source is lang/en/**/*.php.

const VY_I18N_WORK = '/Users/fatimasubhan/vyrobel-local/i18n';

function vyI18nRoot(): string
{
    return dirname(__DIR__, 2);
}

/** Every English key => text, in file order, from lang/en (group = path below lang/en without .php; nested arrays flattened). @return array<string, string> */
function vyI18nEnglish(?string $root = null): array
{
    return vyI18nLocaleStrings('en', $root);
}

/** @return array<string, string> */
function vyI18nLocaleStrings(string $locale, ?string $root = null): array
{
    $dir = ($root ?? vyI18nRoot()).'/lang/'.$locale;
    if (! is_dir($dir)) {
        return [];
    }
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $fi) {
        if (str_ends_with($fi->getFilename(), '.php')) {
            $files[] = $fi->getPathname();
        }
    }
    sort($files);
    $out = [];
    foreach ($files as $f) {
        $group = substr($f, strlen($dir) + 1, -4);
        $data = include $f;
        if (is_array($data)) {
            vyI18nFlatten($data, $group.'.', $out);
        }
    }

    return $out;
}

/** @param array<mixed> $a @param array<string, string> $out */
function vyI18nFlatten(array $a, string $prefix, array &$out): void
{
    foreach ($a as $k => $v) {
        if (is_array($v)) {
            vyI18nFlatten($v, $prefix.$k.'.', $out);
        } elseif (is_string($v)) {
            $out[$prefix.$k] = $v;
        }
    }
}

/** Placeholders (`:name`) in a text, sorted. @return list<string> */
/** lang/.glued-placeholders.json — per key, the English tokens that glue a unit onto a real parameter (':hoursh' → 'hours'). */
function vyI18nGlued(): array
{
    static $map = null;

    return $map ??= (array) json_decode((string) @file_get_contents(dirname(__DIR__, 2).'/lang/.glued-placeholders.json'), true);
}

/** @param  string|null  $key  the translation key, so a glued token reads as the parameter it really is */
function vyI18nPlaceholders(string $s, ?string $key = null): array
{
    // a placeholder is wherever Laravel would replace it — glued to a word too ("stay:place"), in ANY script: the old
    // rule skipped one after a Latin letter but counted one after an Arabic letter, so a correct translation failed
    preg_match_all('/(?<!:):([a-zA-Z_][a-zA-Z0-9_]*)/', $s, $m);
    $glued = $key !== null ? (array) (vyI18nGlued()[$key] ?? []) : [];
    $p = array_values(array_unique(array_map(fn (string $t): string => (string) ($glued[$t] ?? $t), $m[1])));
    sort($p);

    return $p;
}

/** HTML tags with their attribute NAMES (values may hold placeholders), in order. @return list<string> */
function vyI18nTags(string $s): array
{
    preg_match_all('/<\/?([a-zA-Z][a-zA-Z0-9]*)([^>]*)>/', $s, $m, PREG_SET_ORDER);
    $out = [];
    foreach ($m as $t) {
        preg_match_all('/\s([a-zA-Z\-:]+)\s*=/', $t[2], $am);
        $out[] = (str_starts_with($t[0], '</') ? '/' : '').strtolower($t[1]).($am[1] ? '['.implode(',', $am[1]).']' : '');
    }
    sort($out);

    return $out;
}

/** Attribute VALUES of the tags (they must stay as English wrote them — classes, :url). @return list<string> */
function vyI18nTagValues(string $s): array
{
    preg_match_all('/\s[a-zA-Z\-:]+\s*=\s*"([^"]*)"/', implode(' ', vyI18nRawTags($s)), $m);
    $v = $m[1];
    sort($v);

    return $v;
}

/** @return list<string> */
function vyI18nRawTags(string $s): array
{
    preg_match_all('/<[^>]+>/', $s, $m);

    return $m[0];
}

/** A plural string's segment count ({1} …|[0,*] …) — translations may add forms (Russian, Arabic) but never drop to a plain text. */
/**
 * A plural's forms, without their range prefix ("{1} …", "[2,*] …"). A language with more forms than English (Arabic six,
 * Russian three) repeats the markup in each, so markup is compared FORM BY FORM, never by its total over the string.
 *
 * @return list<string>
 */
function vyI18nPluralForms(string $s): array
{
    return array_map(fn (string $f): string => (string) preg_replace('/^\s*(\{[\d,]+\}|\[[\d*,]+\])\s*/', '', $f), explode('|', $s));
}

/** Every translated plural form carries exactly the tags (and attribute values) of one of the English forms. */
function vyI18nPluralMarkupMatches(string $en, string $t): bool
{
    $shapes = array_map(fn (string $f): array => [vyI18nTags($f), vyI18nTagValues($f)], vyI18nPluralForms($en));
    foreach (vyI18nPluralForms($t) as $form) {
        if (! in_array([vyI18nTags($form), vyI18nTagValues($form)], $shapes, true)) {
            return false;
        }
    }

    return true;
}

function vyI18nIsPlural(string $s): bool
{
    return str_contains($s, '|') && preg_match('/^\s*(\{[\d,]+\}|\[[\d*,]+\])/', $s) === 1;
}

/** The scripts a language must be written in (a hard error when a translation has none of its letters). */
function vyI18nScriptRegex(string $locale): ?string
{
    return match ($locale) {
        'ar', 'fa' => '/\p{Arabic}/u',
        'ru', 'bg', 'sr' => '/\p{Cyrillic}/u',
        'el' => '/\p{Greek}/u',
        'ka' => '/\p{Georgian}/u',
        'hi' => '/\p{Devanagari}/u',
        'th' => '/\p{Thai}/u',
        'zh_CN', 'zh_TW' => '/\p{Han}/u',
        'ja' => '/[\p{Han}\p{Hiragana}\p{Katakana}]/u',
        'ko' => '/\p{Hangul}/u',
        default => null,
    };
}

/** @return array<string, mixed> */
function vyI18nJson(string $path, array $default = []): array
{
    if (! is_file($path)) {
        return $default;
    }
    $d = json_decode((string) file_get_contents($path), true);

    return is_array($d) ? $d : $default;
}

function vyI18nWriteJson(string $path, mixed $data): void
{
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
}

/** A PHP lang file for one group, keys in the English order; nested keys rebuilt. @param array<string, string> $flat */
function vyI18nPhpFile(array $flat, string $comment): string
{
    $tree = [];
    foreach ($flat as $k => $v) {
        $ref = &$tree;
        $parts = explode('.', $k);
        $last = array_pop($parts);
        foreach ($parts as $p) {
            $ref[$p] ??= [];
            $ref = &$ref[$p];
        }
        $ref[$last] = $v;
        unset($ref);
    }
    $render = function (array $a, int $depth) use (&$render): string {
        $pad = str_repeat('    ', $depth);
        $s = "[\n";
        foreach ($a as $k => $v) {
            $s .= $pad.'    '.var_export((string) $k, true).' => '.(is_array($v) ? $render($v, $depth + 1) : var_export($v, true)).",\n";
        }

        return $s.$pad.']';
    };

    return "<?php\n\n// ".$comment."\n\nreturn ".$render($tree, 0).";\n";
}
