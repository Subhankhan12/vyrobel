<?php

declare(strict_types=1);

namespace App\Support\I18n;

/**
 * THE translation checks (I18N-01, Phase E) — one definition, read by `php artisan lang:check`, its `--self-test`, the suite
 * (LangCheckTest) and the release preflight. Pure functions over strings and files, so each detector can be proven on a
 * planted needle without touching the real lang files.
 *
 * Codes (each an ERROR — it blocks a release):
 *  - PLACEHOLDER — a `:name` added, dropped or renamed against the English;
 *  - HTML / ATTR — a tag or attribute added or dropped / an attribute value changed (classes and `:url` stay as English wrote them);
 *  - JSQUOTE — a straight `"` or `'` the English did not have: a value printed inside a quoted JS/HTML string ends it;
 *  - LOST — a key that was translated (lang/.applied/<l>.json) and is no longer in the language's files;
 *  - PARSE — a lang file that does not return an array;
 *  - GLUED — two translations side by side in a view (word order fixed in English);
 *  - RAWVAR — `{!! __(…, [ … ]) !!}` printing a replacement that is not escaped with e() (data printed raw);
 *  - COLLAPSE — an array KEYED by a translation (`[__('…') => …]`): two labels that translate alike collapse, an entry vanishes.
 * Not errors here (lang:todo reports them): a key not translated yet (English shows), a translation whose English changed.
 */
final class LangChecks
{
    /** @return list<string> */
    /**
     * I18N-01 — per KEY, the English tokens that glue a unit or an ending onto a real parameter (':hoursh' prints "5h":
     * Laravel replaces ':hours' inside it). A translation may write the parameter with its own unit (':hours Std.').
     * lang/.glued-placeholders.json is the one list (ops/i18n reads it too); never a blanket rule — ':days' is a real
     * parameter elsewhere.
     *
     * @return array<string, string>
     */
    public static function glued(string $key): array
    {
        static $map = null;
        $map ??= (array) json_decode((string) @file_get_contents(lang_path('.glued-placeholders.json')), true);

        return array_filter((array) ($map[$key] ?? []), 'is_string');
    }

    public static function placeholders(string $s, ?string $key = null): array
    {
        // a placeholder is wherever Laravel would replace it — glued to a word too, in ANY script (the old lookbehind
        // skipped one after a Latin letter but counted one after an Arabic letter, so a correct translation failed)
        preg_match_all('/(?<!:):([a-zA-Z_][a-zA-Z0-9_]*)/', $s, $m);
        $glued = $key !== null ? self::glued($key) : [];
        $p = array_values(array_unique(array_map(fn (string $t): string => $glued[$t] ?? $t, $m[1])));
        sort($p);

        return $p;
    }

    /** @return list<string> */
    public static function tags(string $s): array
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

    /** @return list<string> */
    public static function attributeValues(string $s): array
    {
        preg_match_all('/<[^>]+>/', $s, $tags);
        preg_match_all('/\s[a-zA-Z\-:]+\s*=\s*"([^"]*)"/', implode(' ', $tags[0]), $m);
        $v = $m[1];
        sort($v);

        return $v;
    }

    /**
     * Problems of ONE translated value against its English. @return list<array{0: string, 1: string}> [code, detail]
     */
    public static function value(string $english, string $translation, ?string $key = null): array
    {
        $p = [];
        if (self::placeholders($english, $key) !== self::placeholders($translation, $key)) {
            $p[] = ['PLACEHOLDER', implode(',', self::placeholders($english, $key)).' ≠ '.implode(',', self::placeholders($translation, $key))];
        }
        if (self::isPlural($english)) {
            // a language with more plural forms than English (Arabic six) repeats the markup in each — compared FORM BY FORM:
            // every translated form carries exactly the tags and attribute values of one English form (ops/i18n/lib.php's rule)
            $shapes = array_map(fn (string $f): array => [self::tags($f), self::attributeValues($f)], self::pluralForms($english));
            foreach (self::pluralForms($translation) as $form) {
                if (! in_array([self::tags($form), self::attributeValues($form)], $shapes, true)) {
                    $p[] = ['HTML', 'plural form "'.mb_strimwidth($form, 0, 60, '…').'" has markup no English form has'];
                    break;
                }
            }
        } elseif (self::tags($english) !== self::tags($translation)) {
            $p[] = ['HTML', implode(' ', self::tags($english)).' ≠ '.implode(' ', self::tags($translation))];
        } elseif (self::attributeValues($english) !== self::attributeValues($translation)) {
            $p[] = ['ATTR', 'an attribute value differs from the English'];
        }
        foreach (['"', "'"] as $q) {
            if (str_contains(strip_tags($translation), $q) && ! str_contains(strip_tags($english), $q)) {
                $p[] = ['JSQUOTE', "a straight $q the English does not have"];
            }
        }

        return $p;
    }

    private static function isPlural(string $s): bool
    {
        return str_contains($s, '|') && preg_match('/^\s*(\{[\d,]+\}|\[[\d*,]+\])/', $s) === 1;
    }

    /** A plural's forms without their range prefixes. @return list<string> */
    private static function pluralForms(string $s): array
    {
        return array_map(fn (string $f): string => (string) preg_replace('/^\s*(\{[\d,]+\}|\[[\d*,]+\])\s*/', '', $f), explode('|', $s));
    }

    /**
     * Problems in one view's SOURCE. @return list<array{0: string, 1: string}>
     */
    public static function view(string $source): array
    {
        $p = [];
        $src = preg_replace('/\{\{--.*?--\}\}/s', '', $source) ?? $source;
        if (preg_match_all('/\}\}[ \t]*(?:<br\s*\/?>)?\s*\{\{\s*__\(/', $src, $g)) {
            $p[] = ['GLUED', count($g[0]).' place(s): two translations side by side'];
        }
        if (preg_match_all('/\{!!\s*__\(\s*[\'"][^\'"]+[\'"]\s*,\s*\[(.*?)\]\s*(?:,\s*[^)]*)?\)\s*!!\}/s', $src, $raw)) {
            foreach ($raw[1] as $repl) {
                foreach (preg_split('/,(?![^(\[]*[)\]])/', $repl) ?: [] as $pair) {
                    if (preg_match('/=>\s*(.+)$/s', trim($pair), $pv) && ! self::safeReplacement(trim($pv[1])) && ! self::safeHtmlVariable(trim($pv[1]), $src)) {
                        $p[] = ['RAWVAR', 'unescaped replacement: '.mb_strimwidth(trim($pv[1]), 0, 60, '…')];
                    }
                }
            }
        }
        if (preg_match_all('/(?<![\w>$:])(?:__|trans)\(\s*[\'"][^\'"]+[\'"](?:\s*,[^()]*(?:\([^()]*\))?[^()]*)?\)\s*=>/', $src, $c)) {
            $p[] = ['COLLAPSE', count($c[0]).' array entr(y/ies) keyed by a translation'];
        }

        return $p;
    }

    /**
     * A `$…Html` replacement is safe only when the SAME view assigns it with no data outside e() or Js::from(): static markup (an Alpine
     * `x-text` span is filled as plain text in the browser) or parts escaped one by one. Proven per file, never by its name.
     */
    private static function safeHtmlVariable(string $expr, string $src): bool
    {
        if (! preg_match('/^\$(\w+Html)$/', $expr, $m)) {
            return false;
        }
        if (! preg_match_all('/\$'.$m[1].'\s*=\s*(.+?);?\s*(?:\)\s*$|$)/m', $src, $as)) {
            return false;
        }
        foreach ($as[1] as $rhs) {
            $prev = null;
            while ($prev !== $rhs) {
                $prev = $rhs;
                $rhs = preg_replace('/\be\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)/', "''", $rhs) ?? $rhs;
                // Js::from() escapes for a JS expression inside an HTML attribute (Laravel's own JSON_HEX_* flags)
                $rhs = preg_replace('/(?:\\\\?Illuminate\\\\Support\\\\)?Js::from\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)(?:->toHtml\(\))?/', "''", $rhs) ?? $rhs;
            }
            if (str_contains($rhs, '$')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Problems in PHP or Blade CODE. @return list<array{0: string, 1: string}>
     */
    public static function code(string $source): array
    {
        // read as PHP TOKENS: a needle written inside a string literal (this class's own self-test) is never code
        try {
            $tokens = token_get_all(str_starts_with(ltrim($source), '<?php') ? $source : '<?php '.$source);
        } catch (\Throwable) {
            return [];
        }
        $sig = array_values(array_filter($tokens, fn ($t) => ! is_array($t) || ! in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
        $count = 0;
        $n = count($sig);
        for ($i = 0; $i < $n - 2; $i++) {
            $t = $sig[$i];
            if (! is_array($t) || $t[0] !== T_STRING || ! in_array($t[1], ['__', 'trans', 'trans_choice'], true) || $sig[$i + 1] !== '(') {
                continue;
            }
            $prev = $sig[$i - 1] ?? null;
            if (is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                continue;
            }
            $depth = 0;
            for ($j = $i + 1; $j < $n; $j++) {
                if ($sig[$j] === '(') {
                    $depth++;
                } elseif ($sig[$j] === ')' && --$depth === 0) {
                    break;
                }
            }
            $next = $sig[$j + 1] ?? null;
            if (is_array($next) && $next[0] === T_DOUBLE_ARROW) {
                $count++;
            }
        }

        return $count > 0 ? [['COLLAPSE', $count.' array entr(y/ies) keyed by a translation']] : [];
    }

    private static function safeReplacement(string $expr): bool
    {
        return (bool) preg_match('/^(e\(|\'(?:[^\'\\\\]|\\\\.)*\'$|"[^"]*"$|\d|__\(|trans(_choice)?\(|route\(|url\(|Js::|htmlspecialchars\(|\(int\)|number_format\()/', $expr);
    }

    /**
     * A locale's problems against English: every translated value checked, LOST and PARSE included.
     *
     * @param  array<string, string>  $english  key => text
     * @param  array<string, string>  $translated  key => text (what lang/<locale> holds)
     * @param  array<string, string>  $applied  key => English hash at apply time (lang/.applied/<locale>.json)
     * @return list<array{0: string, 1: string, 2: string}> [code, key, detail]
     */
    public static function locale(array $english, array $translated, array $applied): array
    {
        $out = [];
        foreach ($translated as $key => $text) {
            if (! isset($english[$key])) {
                continue;   // a key English no longer has: lang:todo's REMOVED, not an error
            }
            foreach (self::value($english[$key], $text, $key) as [$code, $detail]) {
                $out[] = [$code, $key, $detail];
            }
        }
        foreach (array_keys($applied) as $key) {
            if (isset($english[$key]) && ! isset($translated[$key])) {
                $out[] = ['LOST', $key, 'translated at apply time, now missing from the language files'];
            }
        }

        return $out;
    }
}
