<?php

declare(strict_types=1);

namespace App\Support\I18n;

use Illuminate\Support\Facades\App;
use IntlDatePatternGenerator;

/**
 * The ORDER of a date in the page's language (I18N-01, D-I18N-DATE-ORDER). Every date people read is printed with
 * `translatedFormat()`, which translates month and weekday NAMES but keeps the pattern's order — so German read "1 Sep 2026"
 * where it writes "1. Sep. 2026", and Japanese would read "1 9月 2026". Call sites keep their English pattern and pass it
 * through here: `->translatedFormat(LocalDate::pattern('j M Y'))`.
 *
 *   · English: the pattern, unchanged — byte for byte what every page printed before.
 *   · Any other language: ICU's best pattern for the same fields in that language (IntlDatePatternGenerator — German
 *     "d. MMM y", Japanese "y年M月d日", Russian "d MMM y 'г'."), turned back into PHP's letters, so Carbon still prints its
 *     own translated names. Nobody translates a format string by hand, and all 27 languages are covered by the same rule.
 *   · A single field ("M", "D", "F", "l", "j") has no order to fix and passes unchanged.
 */
final class LocalDate
{
    /**
     * PHP date letter → the ICU skeleton field that asks for the same thing. ⚠️ `H`/`G` (a 24-hour clock in the English source)
     * ask for the 24-hour skeleton `H`, NOT `j` ("the language's preferred hour"): `j` made Greek, Hindi, Arabic, Albanian,
     * Traditional Chinese and Korean print "3:00 ΜΜ" where the source said 15:00. `g`/`h` (12-hour) keep `j`.
     */
    private const SKELETON = [
        'j' => 'd', 'd' => 'd', 'M' => 'MMM', 'F' => 'MMMM', 'n' => 'M', 'm' => 'M', 'Y' => 'y', 'y' => 'yy',
        'D' => 'EEE', 'l' => 'EEEE', 'H' => 'H', 'G' => 'H', 'g' => 'j', 'h' => 'j', 'i' => 'mm',
    ];

    /** @var array<string, string> */
    private static array $cache = [];

    public static function pattern(string $pattern, ?string $locale = null): string
    {
        $locale ??= (string) App::getLocale();
        if ($locale === Locales::SOURCE || ! Locales::isPlanned($locale) || ! class_exists(IntlDatePatternGenerator::class)) {   // a code we do not know keeps the pattern
            return $pattern;
        }

        return self::$cache[$locale."\0".$pattern] ??= self::localise($pattern, $locale);
    }

    private static function localise(string $pattern, string $locale): string
    {
        $fields = '';
        $count = 0;
        $zone = false;
        foreach (preg_split('//u', $pattern, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
            if ($ch === 'T') {
                $zone = true;   // a time-zone abbreviation is appended after the localised pattern, as the English shows it

                continue;
            }
            if (isset(self::SKELETON[$ch]) && ! str_contains($fields, self::SKELETON[$ch])) {
                $fields .= self::SKELETON[$ch];
                $count++;
            }
        }
        if ($count < 2) {
            return $pattern;
        }

        // ⚠️ the GREGORIAN calendar, always: Persian's default calendar is Solar Hijri and its date patterns come back in the
        //    wrong order for the Gregorian dates this app prints ("2026 نوامبر 5, پنجشنبه" for "l j F Y")
        $generator = new IntlDatePatternGenerator(str_replace('_', '-', Locales::tag($locale)).'-u-ca-gregory');
        $icu = $generator->getBestPattern($fields);
        if ($icu === false || $icu === '') {
            return $pattern;
        }

        return self::toPhp($icu).($zone ? ' T' : '');
    }

    /** An ICU pattern ("d. MMM y, HH:mm", "y年M月d日", "d MMM y 'г'.") in PHP's date() letters, literal text escaped. */
    private static function toPhp(string $icu): string
    {
        $out = '';
        $n = strlen($icu);
        for ($i = 0; $i < $n;) {
            $c = $icu[$i];
            if ($c === "'") {   // quoted literal text ('' is a literal apostrophe)
                $end = strpos($icu, "'", $i + 1);
                $literal = $end === false ? substr($icu, $i + 1) : substr($icu, $i + 1, $end - $i - 1);
                $out .= $literal === '' ? "'" : (string) preg_replace('/[A-Za-z]/', '\\\\$0', $literal);
                $i = $end === false ? $n : $end + 1;

                continue;
            }
            if (($c >= 'a' && $c <= 'z') || ($c >= 'A' && $c <= 'Z')) {   // ASCII letters only — ctype_alpha() takes a UTF-8 lead byte for one
                $run = strspn($icu, $c, $i);
                $out .= match ($c) {
                    'd' => $run >= 2 ? 'd' : 'j',
                    'M', 'L' => match (true) {
                        $run >= 4 => 'F', $run === 3 => 'M', $run === 2 => 'm', default => 'n'
                    },
                    'y' => $run === 2 ? 'y' : 'Y',
                    'E', 'c' => $run >= 4 ? 'l' : 'D',
                    'H' => $run >= 2 ? 'H' : 'G',
                    'h' => $run >= 2 ? 'h' : 'g',
                    'm' => 'i',
                    'a', 'b', 'B' => 'A',
                    default => '',   // a field this app never asks for (era, week, quarter) is left out
                };
                $i += $run;

                continue;
            }
            $out .= $c;   // punctuation, spaces and non-Latin literal characters (年, 月, 日, ، …) pass as they are
            $i++;
        }

        return $out;
    }
}
