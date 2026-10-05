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

    /**
     * EVERY ISO-3166-1 alpha-2 country, for where a person is FROM (a guest's country, 2026-10-04) — not the served set
     * above, which is about tax residency: a guest can come from anywhere, and the New reservation page's two-letter box
     * accepted any code. Names come from ICU (`ext-intl`, present locally and on production); a code ICU does not know
     * keeps its code as the name rather than vanishing. Name-ordered for a dropdown.
     *
     * @return array<string, string>
     */
    public static function everywhere(): array
    {
        static $built = null;   // built once per request — the Guests list reads it for every row (2026-10-05)
        if ($built !== null) {
            return $built;
        }
        $out = [];
        foreach (explode(' ', self::ISO) as $code) {
            $name = class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.$code, 'en') : '';
            $out[$code] = is_string($name) && $name !== '' && $name !== $code ? $name : $code;
        }
        // by the alphabet a person reads, not by bytes ("Åland Islands" sorted after Zimbabwe — the review)
        $collator = class_exists(\Collator::class) ? new \Collator('en') : null;
        $collator !== null ? uasort($out, fn (string $a, string $b): int => (int) $collator->compare($a, $b)) : asort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $built = $out;
    }

    /** The 249 officially assigned ISO-3166-1 alpha-2 codes. */
    private const ISO = 'AD AE AF AG AI AL AM AO AQ AR AS AT AU AW AX AZ BA BB BD BE BF BG BH BI BJ BL BM BN BO BQ BR BS BT BV BW BY BZ '
        .'CA CC CD CF CG CH CI CK CL CM CN CO CR CU CV CW CX CY CZ DE DJ DK DM DO DZ EC EE EG EH ER ES ET FI FJ FK FM FO FR GA GB GD '
        .'GE GF GG GH GI GL GM GN GP GQ GR GS GT GU GW GY HK HM HN HR HT HU ID IE IL IM IN IO IQ IR IS IT JE JM JO JP KE KG KH KI KM '
        .'KN KP KR KW KY KZ LA LB LC LI LK LR LS LT LU LV LY MA MC MD ME MF MG MH MK ML MM MN MO MP MQ MR MS MT MU MV MW MX MY MZ NA '
        .'NC NE NF NG NI NL NO NP NR NU NZ OM PA PE PF PG PH PK PL PM PN PR PS PT PW PY QA RE RO RS RU RW SA SB SC SD SE SG SH SI SJ '
        .'SK SL SM SN SO SR SS ST SV SX SY SZ TC TD TF TG TH TJ TK TL TM TN TO TR TT TV TW TZ UA UG UM US UY UZ VA VC VE VG VI VN VU '
        .'WF WS YE YT ZA ZM ZW';
}
