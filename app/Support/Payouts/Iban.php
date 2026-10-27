<?php

declare(strict_types=1);

namespace App\Support\Payouts;

use App\Support\Countries;

/**
 * IBAN validation + the bits derived FROM ITS STRUCTURE (Phase 2 · Sub-gate 5). Real, offline, no registry:
 * the ISO 7064 mod-97 checksum proves a well-formed IBAN, and the country (first two letters) + last four
 * digits come straight from the string. That is the honest extent of "auto-detect" — the BANK NAME needs a
 * BIC/bank-code registry we don't have, so it is NEVER fabricated (the owner labels the account themselves).
 *
 * The full IBAN is stored ENCRYPTED; only {@see last4()} + {@see country()} are kept in the clear, so the UI
 * can mask it (···· 4821) without ever decrypting the whole number to render a list.
 */
final class Iban
{
    /** The IBAN countries shown by name; any other code is shown as the code. */
    private const NAMED_COUNTRIES = ['PT', 'IE', 'ES', 'FR', 'GB', 'DE', 'IT', 'NL', 'BE', 'LU', 'AT', 'FI', 'GR', 'PL', 'SE', 'DK', 'CH', 'NO'];

    /** Uppercase, strip all whitespace — the canonical form the checksum runs on. */
    public static function normalize(string $iban): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', trim($iban)));
    }

    /** A well-formed IBAN: country + check digits + BBAN, passing the ISO 7064 mod-97 check (== 1). */
    public static function isValid(string $iban): bool
    {
        $iban = self::normalize($iban);

        if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban) !== 1) {
            return false;
        }

        // Move the first four chars to the end, then map A–Z → 10–35 and take mod 97.
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $ch) {
            $numeric .= ctype_alpha($ch) ? (string) (ord($ch) - 55) : $ch;
        }

        $remainder = 0;
        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

    /** The two-letter country code (assumes a normalized/valid IBAN). */
    public static function country(string $iban): string
    {
        return substr(self::normalize($iban), 0, 2);
    }

    /** The country's display name, or the raw code if we don't map it. */
    public static function countryName(string $iban): string
    {
        return self::nameForCode(self::country($iban));
    }

    /**
     * A stored 2-letter country code → display name for the countries we surface (or the code itself if unmapped),
     * translated at call time in the reader's language.
     */
    public static function nameForCode(string $code): string
    {
        $code = strtoupper($code);

        // I18N-01 — ONE country list: the names come from {@see Countries::everywhere()} (ICU's, in the reader's language;
        // its English is word for word what this list printed). Outside these codes the code is shown, as it always was.
        return in_array($code, self::NAMED_COUNTRIES, true) ? (Countries::everywhere()[$code] ?? $code) : $code;
    }

    /** The last four characters, for masked display. */
    public static function last4(string $iban): string
    {
        return substr(self::normalize($iban), -4);
    }

    /** The masked form the UI shows in place of the full number: "···· 4821". */
    public static function masked(string $last4): string
    {
        return '···· '.$last4;
    }
}
