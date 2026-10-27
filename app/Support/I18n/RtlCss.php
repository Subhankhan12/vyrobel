<?php

declare(strict_types=1);

namespace App\Support\I18n;

/**
 * Mirror CSS for a right-to-left page (I18N-01 — the founder's ruling: Arabic and Persian are RTL and "the design should be
 * completely fine"). The way rtlcss does it: physical left/right become right/left in property NAMES (margin-left →
 * margin-right), in VALUES of the properties that take a side (text-align, float, clear, background-position, transform-origin,
 * `to left` gradients), and in the order of four-value shorthands (margin, padding, inset, border-width/-style/-color,
 * border-radius). Flexbox and grid already follow `dir="rtl"`, so with this the whole layout reads from the right.
 *
 * Applied only when the page's language is right-to-left (MirrorForRtl), on the RESPONSE — every view's own CSS stays as
 * written, and an English page is never touched. Not mirrored (by design, as rtlcss's defaults): translateX, box-shadow and
 * angle gradients; positions a script computes; and a time axis — whether the rack, calendar and schedule run right to left
 * follows the founder's look.
 */
final class RtlCss
{
    private const SIDES = ['left' => 'right', 'right' => 'left'];

    private const SIDE_VALUE_PROPS = ['text-align', 'float', 'clear', 'background-position', 'background-position-x', 'transform-origin', 'caption-side', 'justify-self', 'justify-items'];

    private const FOUR = ['margin', 'padding', 'inset', 'border-width', 'border-style', 'border-color', 'scroll-margin', 'scroll-padding'];

    /** Mirror every declaration block of a stylesheet (innermost braces — media queries and keyframes keep their structure). */
    public static function flipStylesheet(string $css): string
    {
        return preg_replace_callback('/\{([^{}]*)\}/', fn (array $m) => '{'.self::flipDeclarations($m[1]).'}', $css) ?? $css;
    }

    /** Mirror one declaration list (`a: b; c: d`), as found inside a block or a style="" attribute. */
    public static function flipDeclarations(string $decls): string
    {
        return preg_replace_callback(
            '/(^|;|\s)(\s*)([a-zA-Z-]+)(\s*:\s*)([^;]+)/',
            function (array $m): string {
                [$all, $lead, $ws, $prop, $colon, $value] = $m;
                $p = strtolower($prop);
                if (str_starts_with($p, '--')) {
                    return $all;   // custom properties hold design tokens, never a side
                }

                return $lead.$ws.self::flipProperty($prop).$colon.self::flipValue($p, $value);
            },
            $decls,
        ) ?? $decls;
    }

    private static function flipProperty(string $prop): string
    {
        // margin-left ↔ margin-right, border-top-left-radius ↔ border-top-right-radius, left ↔ right …
        return preg_replace_callback('/(?<![a-z])(left|right)(?![a-z])/i', fn ($m) => self::SIDES[strtolower($m[1])], $prop) ?? $prop;
    }

    private static function flipValue(string $prop, string $value): string
    {
        $important = '';
        if (preg_match('/\s*!important\s*$/i', $value, $imp)) {
            $important = $imp[0];
            $value = substr($value, 0, -strlen($imp[0]));
        }
        $base = preg_replace('/-(left|right)/', '', $prop) ?? $prop;

        if (in_array($prop, self::SIDE_VALUE_PROPS, true)) {
            $value = self::swapWords($value);
        } elseif (in_array($prop, self::FOUR, true)) {
            $parts = self::parts($value);
            if (count($parts) === 4) {
                $value = implode(' ', [$parts[0], $parts[3], $parts[2], $parts[1]]);
            }
        } elseif ($prop === 'border-radius' && ! str_contains($value, '/')) {
            $parts = self::parts($value);
            $value = match (count($parts)) {
                2 => implode(' ', [$parts[1], $parts[0]]),
                3 => implode(' ', [$parts[1], $parts[0], $parts[1], $parts[2]]),
                4 => implode(' ', [$parts[1], $parts[0], $parts[3], $parts[2]]),
                default => $value,
            };
        } elseif (str_contains($value, 'gradient(') && preg_match('/\bto (left|right)\b/', $value)) {
            $value = preg_replace_callback('/\bto (left|right)\b/', fn ($m) => 'to '.self::SIDES[$m[1]], $value) ?? $value;
        } elseif ($base === 'cursor') {
            $value = strtr($value, ['e-resize' => 'w-resize', 'w-resize' => 'e-resize', 'ne-resize' => 'nw-resize', 'nw-resize' => 'ne-resize', 'se-resize' => 'sw-resize', 'sw-resize' => 'se-resize']);
        }

        return $value.$important;
    }

    private static function swapWords(string $value): string
    {
        return preg_replace_callback('/(?<![\w-])(left|right)(?![\w-])/', fn ($m) => self::SIDES[$m[1]], $value) ?? $value;
    }

    /** Top-level space-separated parts (a calc() or var() stays whole). @return list<string> */
    private static function parts(string $value): array
    {
        $out = [];
        $depth = 0;
        $cur = '';
        foreach (str_split(trim($value)) as $ch) {
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            }
            if ($depth === 0 && ctype_space($ch)) {
                if ($cur !== '') {
                    $out[] = $cur;
                    $cur = '';
                }

                continue;
            }
            $cur .= $ch;
        }
        if ($cur !== '') {
            $out[] = $cur;
        }

        return $out;
    }

    /** Mirror the <style> blocks and style="" attributes of an HTML document or fragment. */
    public static function flipHtml(string $html): string
    {
        $html = preg_replace_callback('#(<style\b[^>]*>)(.*?)(</style>)#is', fn ($m) => $m[1].self::flipStylesheet($m[2]).$m[3], $html) ?? $html;

        return preg_replace_callback('/(\sstyle\s*=\s*)(["\'])(.*?)\2/is', fn ($m) => $m[1].$m[2].self::flipDeclarations($m[3]).$m[2], $html) ?? $html;
    }
}
