<?php

declare(strict_types=1);

namespace App\Support\Money;

/**
 * Parse a HAND-TYPED money amount to integer minor units — the one place that turns a text field into the
 * minor units the {@see Money} value object and the folio/rate tables store (convention #3 / invariant #17).
 *
 * ⚠️ STRING arithmetic, never a float multiply. `175.57 * 100` in IEEE-754 is `17556.9999…`, so a naive
 * `(int) round($x * 100)` can drop a cent; splitting on the decimal point and combining as integers cannot.
 * Shared by every operator write that accepts a typed amount (rates, payments) so the rule lives in ONE
 * place and cannot drift between surfaces.
 */
final class MoneyInput
{
    /**
     * Minor units for a positive amount with up to two decimals (an optional currency glyph and thousands
     * commas are tolerated); null for anything invalid, non-positive, or above $maxMinor.
     */
    public static function toMinor(string $input, int $maxMinor = PHP_INT_MAX): ?int
    {
        $clean = str_replace([' ', ','], '', trim($input));
        $clean = ltrim($clean, '€£$');

        if ($clean === '' || preg_match('/^\d+(\.\d{1,2})?$/', $clean) !== 1) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', $clean), 2, '');
        $fraction = str_pad($fraction, 2, '0');           // "5" → "50", "" → "00"
        $minor = ((int) $whole) * 100 + (int) $fraction;  // integer arithmetic only

        if ($minor <= 0 || $minor > $maxMinor) {
            return null;
        }

        return $minor;
    }
}
