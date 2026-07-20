<?php

declare(strict_types=1);

namespace App\Support\Payouts;

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
    /** ISO 3166 → display name for the countries we surface; anything else falls back to the raw code. */
    private const COUNTRIES = [
        'PT' => 'Portugal', 'IE' => 'Ireland', 'ES' => 'Spain', 'FR' => 'France', 'GB' => 'United Kingdom',
        'DE' => 'Germany', 'IT' => 'Italy', 'NL' => 'Netherlands', 'BE' => 'Belgium', 'LU' => 'Luxembourg',
        'AT' => 'Austria', 'FI' => 'Finland', 'GR' => 'Greece', 'PL' => 'Poland', 'SE' => 'Sweden',
        'DK' => 'Denmark', 'CH' => 'Switzerland', 'NO' => 'Norway',
    ];

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

    /** A stored 2-letter country code → display name (or the code itself if unmapped). */
    public static function nameForCode(string $code): string
    {
        $code = strtoupper($code);

        return self::COUNTRIES[$code] ?? $code;
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
