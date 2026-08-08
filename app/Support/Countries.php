<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The ONE country list — ISO-3166-1 alpha-2 code ⇄ display name (audit 2026-08-07 · parity).
 *
 * ⚠️ THIS EXISTS BECAUSE A DROPDOWN COULD ONLY MAKE THE DATA WRONG. The owner Tax Center's residency
 * `<select>` emitted country NAMES as its option values:
 *
 *     <option value="Portugal">Portugal</option>
 *
 * while `owners.tax_country` is an ISO CODE by contract — the `tax:set-rate` command's own signature
 * says so ("matches owners.tax_country, e.g. PT"), the seeded example rates are keyed `PT`, and the
 * demo owner's stored value is `PT`. So the two consumers of that column —
 *
 *     WithholdingService  → TaxRateService::rateFor($owner->tax_country, TYPE_WITHHOLDING)
 *     OwnerVatService     → the same lookup for VAT
 *
 * — would look up "Portugal", match nothing, and silently compute a ZERO withholding. ⚠️ That is a
 * MONEY defect, not a cosmetic one: withholding is a real deduction from a real payout, and the failure
 * is silent in the worst direction (a founder configures PT, an owner picks "Portugal", the rate never
 * applies and no error is ever raised). The identifier contract now has one definition, here.
 *
 * Codes are the storage form everywhere; names are display only. {@see toCode()} accepts either, so a
 * stale cached form or a legacy row cannot poison the column.
 */
final class Countries
{
    /**
     * ISO-3166-1 alpha-2 → display name. Deliberately the set the product actually serves rather than
     * all ~250 — a residency the operator has no tax rate for is not a useful option — and it is the
     * SAME set the booking-summary PDF was already carrying privately.
     *
     * @var array<string, string>
     */
    private const NAMES = [
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'CA' => 'Canada',
        'CH' => 'Switzerland',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'ES' => 'Spain',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'GR' => 'Greece',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'SE' => 'Sweden',
        'US' => 'United States',
    ];

    /**
     * The full code → name map, name-ordered for a dropdown.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = self::NAMES;
        asort($out);

        return $out;
    }

    /**
     * The display name for a stored code. An UNKNOWN code is returned as-is rather than blanked — a row
     * holding something this list does not know about must still render, not vanish.
     */
    public static function name(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $key = strtoupper(trim($code));

        return self::NAMES[$key] ?? trim($code);
    }

    /**
     * Normalise any inbound value to the STORAGE form (an ISO code).
     *
     * Accepts a code ("pt", "PT") or a display name ("Portugal"), because a stale cached form or a
     * legacy row may still submit a name. Anything unrecognised is passed through trimmed — this
     * normalises, it never silently discards a value the operator typed.
     */
    public static function toCode(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (isset(self::NAMES[strtoupper($trimmed)])) {
            return strtoupper($trimmed);
        }

        $byName = array_change_key_case(array_flip(self::NAMES), CASE_LOWER);

        return $byName[strtolower($trimmed)] ?? $trimmed;
    }

    /** Whether a value is a code this list knows — used to tell a real selection from a legacy one. */
    public static function isKnownCode(?string $value): bool
    {
        return $value !== null && isset(self::NAMES[strtoupper(trim($value))]);
    }
}
