<?php

declare(strict_types=1);

namespace App\Support\I18n;

/**
 * Capital letters that respect the SCRIPT (I18N-01). `mb_strtoupper()` turns a Georgian (Mkhedruli) letter into its Mtavruli
 * twin, which Georgian never uses for an initial, a weekday or a month label; Arabic, Hebrew, CJK, Thai and Devanagari have
 * no case and pass through `mb_strtoupper()` untouched on their own. So this uppercases everything EXCEPT Georgian runs —
 * and for any text without Georgian it is exactly `mb_strtoupper()`, so English, Cyrillic and Greek print as they always did.
 */
final class LocalText
{
    public static function upper(string $text): string
    {
        if (preg_match('/\p{Georgian}/u', $text) !== 1) {
            return mb_strtoupper($text);
        }

        $parts = preg_split('/(\p{Georgian}+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {   // the delimiter-captured runs sit at the odd offsets
                $parts[$i] = mb_strtoupper($part);
            }
        }

        return implode('', $parts);
    }

    /** The first letter in capitals (an initial): {@see upper()} on one character. */
    public static function initial(string $text, int $length = 1): string
    {
        return self::upper(mb_substr($text, 0, $length));
    }

    /**
     * A one-line stylesheet that switches `text-transform` off where it would capitalise Georgian, or '' in every other
     * language (so every other page is byte for byte what it was). Printed inside a shell's <head>.
     */
    public static function caselessCss(?string $locale = null): string
    {
        $locale ??= (string) app()->getLocale();

        return $locale === 'ka' ? '<style>:lang(ka),:lang(ka) *{text-transform:none!important}</style>' : '';
    }
}
