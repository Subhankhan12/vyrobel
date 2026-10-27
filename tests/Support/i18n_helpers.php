<?php

declare(strict_types=1);

/**
 * I18N-01 — a source file as an English reader sees its words. Phase A moved a page's words into lang/en/** without changing
 * them, so a test that reads a view's (or a class's) SOURCE for a sentence or a piece of markup keeps its claim exactly by
 * reading it through this. Each call is put back the way the text was written before the move:
 *
 *   {{ __('g.key') }} / {!! __('g.key') !!}             → the English (escaped as the echo prints it)
 *   {{ __('g.key', ['name' => $expr]) }}                → the English with `{{ $expr }}` where `:name` stands
 *   {{ trans_choice('g.key', $n, ['count' => $n]) }}    → the same, from the plural form ("{1} …|[2,*] Sleeps :count")
 *   __('g.key') anywhere else (PHP, @php, attributes)    → 'English' — a quoted string, as the literal was
 *
 * Only calls whose arguments it can read are rewritten; anything else is left as it is.
 */
function i18nEnglishSource(string $path): string
{
    $src = (string) file_get_contents($path);

    $english = function (string $key): ?string {
        $text = __($key, [], 'en');

        return is_string($text) && $text !== $key ? $text : null;
    };

    // the English form a plural shows for "many" — the last form, without its range
    $pluralForm = fn (string $text): string => (string) preg_replace('/^\s*(\{[\d,]+\}|\[[\d*,]+\])\s*/', '', (string) last(explode('|', $text)));

    // `'name' => expr, …` at the top level of a short array → [name => expr]
    $params = function (string $array): array {
        $out = [];
        $depth = 0;
        $buf = '';
        $parts = [];
        foreach (str_split($array) as $ch) {
            $depth += match ($ch) {
                '(', '[' => 1, ')', ']' => -1, default => 0
            };
            if ($ch === ',' && $depth === 0) {
                $parts[] = $buf;
                $buf = '';

                continue;
            }
            $buf .= $ch;
        }
        $parts[] = $buf;
        foreach ($parts as $p) {
            if (preg_match('/^\s*\'([a-z_][a-z0-9_]*)\'\s*=>\s*(.+?)\s*$/si', $p, $m)) {
                $out[$m[1]] = $m[2];
            }
        }

        return $out;
    };

    // echoed calls, with or without parameters
    $balanced = '(\[(?:[^\[\]]++|(?-1))*\])';
    $src = (string) preg_replace_callback(
        '/\{\{\s*(__|trans_choice)\(\s*\'([^\'$]+)\'(?:\s*,\s*(\$\w+(?:\[\'[^\']*\'\]|\[\$?\w+\])*|[^,\[\]()]+?|\([^()]*\)))?(?:\s*,\s*'.$balanced.')?\s*\)\s*\}\}'
        .'|\{!!\s*__\(\s*\'([^\'$]+)\'\s*\)\s*!!\}/s',
        function (array $m) use ($english, $pluralForm, $params): string {
            if (($m[5] ?? '') !== '') {   // {!! __('key') !!}
                return $english($m[5]) ?? $m[0];
            }
            $text = $english($m[2]);
            if ($text === null) {
                return $m[0];
            }
            if ($m[1] === 'trans_choice') {
                $text = $pluralForm($text);
            } elseif (($m[3] ?? '') !== '' && ! str_starts_with(trim($m[3]), '[')) {
                return $m[0];   // a second argument that is not an array — not a shape this reads
            }
            $array = $m[4] ?? (str_starts_with(trim($m[3] ?? ''), '[') ? trim($m[3]) : '');
            $out = e($text);
            foreach ($params($array !== '' ? substr($array, 1, -1) : '') as $name => $expr) {
                $out = (string) preg_replace('/:'.preg_quote($name, '/').'(?![a-zA-Z0-9_])/', '{{ '.str_replace(['\\', '$'], ['\\\\', '\\$'], $expr).' }}', $out);
            }

            return $out;
        },
        $src,
    );

    // …and a bare `__('group.key')` in PHP, @php or an attribute — the quoted string it used to be
    return (string) preg_replace_callback('/(?<![\w>$:])__\(\s*\'([^\'$]+)\'\s*\)/', function (array $m) use ($english): string {
        $text = $english($m[1]);

        return $text === null ? $m[0] : "'".$text."'";
    }, $src);
}
