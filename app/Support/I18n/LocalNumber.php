<?php

declare(strict_types=1);

namespace App\Support\I18n;

use Illuminate\Http\Request;
use NumberFormatter;

/**
 * ⚠️⚠️ D-MONEY-COMMA (ruled 2026-10-28: "Go ahead and make sure it real") — the ONE reader of a number a person TYPED.
 *
 * 19 of the 27 languages write a decimal COMMA ("150,50"); the old parser dropped every comma as a thousands mark and read
 * "150,50" as 15050 — a 100× amount on a rate, a payment or a refund — and the float casts beside it read "150,50" as 150
 * (the cents silently gone). This reads what the person meant, the same way on every page, and REFUSES what cannot be told
 * apart rather than guessing:
 *
 *   · both marks present ("1.234,50", "1,234.50")  → the LAST one is the decimal mark, the other groups thousands;
 *   · one mark, followed by 1–2 digits ("150,50", "150.50") → the decimal mark (a thousands group always has 3 digits);
 *   · one mark repeated ("1.234.567")              → thousands groups;
 *   · one mark followed by exactly 3 digits ("1.234") → the ONLY ambiguity, settled by the page language: the language's
 *     thousands mark → a whole number (German "1.234" = 1234); its decimal mark → REFUSED (3 decimals is not an amount
 *     this reader accepts, and "1234" would be a guess);
 *   · spaces, no-break spaces and apostrophes are thousands marks; € £ $ and the other currency glyphs are ignored;
 *     Arabic-Indic and Persian digits are read as digits, ٫ as the decimal mark and ٬ as the thousands mark.
 *
 * Because one mark followed by 1–2 digits is ALWAYS the decimal, a browser's <input type="number"> (which always sends
 * "150.50") reads the same on a German page as on an English one. English is unchanged except that "150,50" now means
 * 150.50 instead of 15050.00 — the defect itself.
 */
final class LocalNumber
{
    private const DIGITS = ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9'];

    /**
     * The canonical form ("1234.5", "150") of a non-negative number typed in the page language, or null when it is not a
     * number, has more than $maxDecimals decimals, or cannot be told apart.
     */
    public static function normalize(string $input, int $maxDecimals = 2, ?string $locale = null): ?string
    {
        $s = strtr(trim($input), self::DIGITS + ['٫' => '.', '٬' => ',']);
        // currency glyphs, and every kind of space or apostrophe used to group thousands
        $s = (string) preg_replace('/[€£$¥₺₽₹₩₫฿₸₾\s\x{00A0}\x{202F}\x{2009}\'’]/u', '', $s);
        if ($s === '' || preg_match('/^[0-9.,]+$/', $s) !== 1 || preg_match('/[0-9]/', $s) !== 1) {
            return null;
        }

        $dots = substr_count($s, '.');
        $commas = substr_count($s, ',');
        $decimal = null;

        if ($dots > 0 && $commas > 0) {
            $decimal = strrpos($s, '.') > strrpos($s, ',') ? '.' : ',';
        } elseif ($dots + $commas === 1) {
            $mark = $dots === 1 ? '.' : ',';
            $after = strlen($s) - strpos($s, $mark) - 1;
            if ($after >= 1 && $after <= 2) {
                $decimal = $mark;
            } elseif ($after === 3) {
                if ($mark === self::decimalMark($locale)) {
                    return null;   // "1,234" on a decimal-comma page: 1.234 or 1234? — refused, never guessed
                }
                // the language's thousands mark — a whole number
            } else {
                return null;       // "150." or "1.2345"
            }
        }

        $group = $decimal === null ? ($dots > 0 ? '.' : ($commas > 0 ? ',' : null)) : ($decimal === '.' ? ',' : '.');
        [$whole, $fraction] = $decimal === null ? [$s, ''] : array_pad(explode($decimal, $s), 2, '');
        if ($decimal !== null && substr_count($s, $decimal) !== 1) {
            return null;           // two decimal marks
        }
        if ($fraction !== '' && (strlen($fraction) > $maxDecimals || str_contains($fraction, (string) $group))) {
            return null;
        }
        if ($decimal !== null && $fraction === '') {
            return null;
        }
        if ($group !== null && str_contains($whole, $group) && ! self::validGroups(explode($group, $whole))) {
            return null;
        }

        $digits = $group !== null ? str_replace($group, '', $whole) : $whole;
        if ($digits === '' || preg_match('/^[0-9]+$/', $digits) !== 1) {
            return null;
        }
        $digits = ltrim($digits, '0') === '' ? '0' : ltrim($digits, '0');

        return $fraction === '' ? $digits : $digits.'.'.$fraction;
    }

    /** The page language's decimal mark, '.' or ',' (ICU). Persian's ٫ reads as '.'. */
    public static function decimalMark(?string $locale = null): string
    {
        $tag = str_replace('_', '-', Locales::tag($locale ?? (string) app()->getLocale()));
        $mark = class_exists(NumberFormatter::class) ? (new NumberFormatter($tag, NumberFormatter::DECIMAL))->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL) : '.';

        return $mark === ',' ? ',' : '.';
    }

    /** "150,50" or "150.50" — how this page's language writes an example amount (for a hint or an error). */
    public static function example(?string $locale = null): string
    {
        return self::decimalMark($locale) === ',' ? '150,50' : '150.50';
    }

    /**
     * Rewrite typed number fields of a request to their canonical form BEFORE `numeric` validation runs, so a decimal comma
     * is accepted where the language writes one. A value this reader cannot read is left as typed — `numeric` then refuses
     * it, as it always did. `taxes.*`-style fields are rewritten entry by entry.
     *
     * @param  list<string>  $fields
     */
    public static function mergeInto(Request $request, array $fields, int $maxDecimals = 2): void
    {
        $replace = [];
        foreach ($fields as $field) {
            if (str_ends_with($field, '.*')) {
                $base = substr($field, 0, -2);
                $values = $request->input($base);
                if (is_array($values)) {
                    $replace[$base] = array_map(fn ($v) => is_string($v) && trim($v) !== '' ? (self::normalize($v, $maxDecimals) ?? $v) : $v, $values);
                }

                continue;
            }
            $value = $request->input($field);
            if (is_string($value) && trim($value) !== '') {
                $replace[$field] = self::normalize($value, $maxDecimals) ?? $value;
            }
        }
        if ($replace !== []) {
            $request->merge($replace);
        }
    }

    /** Thousands groups: the first 1–3 digits, then groups of 3 (or 2 before the last — Indian lakh grouping). @param list<string> $groups */
    private static function validGroups(array $groups): bool
    {
        $last = count($groups) - 1;
        foreach ($groups as $i => $g) {
            $len = strlen($g);
            $ok = match (true) {
                $i === 0 => $len >= 1 && $len <= 3,
                $i === $last => $len === 3,
                default => $len === 2 || $len === 3,
            };
            if (! $ok) {
                return false;
            }
        }

        return true;
    }
}
