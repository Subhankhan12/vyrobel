<?php

declare(strict_types=1);

namespace App\Support\Billing;

/**
 * THE ONE reading of a stored VAT number (DO-BILL-1, 2026-08-13).
 *
 * ⚠️ WHY THIS IS ITS OWN CLASS, AND WHY IT RETURNS NULL SO READILY. Stripe does not take a bare VAT string — a
 * tax ID is `{type, value}`, and the TYPE decides how the number is interpreted. Get the type wrong and Stripe
 * either rejects it or, worse, validates it as something it is not. So the derivation lives in one place, keyed
 * off a **vetted country map** rather than guessed from shape, and anything not in that map comes back **null**.
 *
 * ⚠️ NULL IS THE FAIL-SAFE, AND IT IS THE POINT. A VAT ID drives the tax OUTCOME: a valid EU B2B number means
 * reverse charge, i.e. **€0 VAT**. An id we cannot confidently type must therefore NOT be sent — the customer
 * stays taxable and pays, which is recoverable, rather than being wrongly zero-rated, which is a compliance
 * liability the operator only discovers at an audit. **When in doubt, charge the tax.**
 *
 * ⚠️ WHAT THIS DOES *NOT* DO — it is a STRUCTURAL check, not a validity check. It answers "is this plausibly a
 * VAT number of a country we can name?", never "does this number exist and belong to this business?" Only VIES
 * (or Stripe's async validation against it) can answer that. See {@see StripeTaxIdSync} for how the difference
 * is handled, and §G.1 for the founder decision it is waiting on.
 */
class VatId
{
    /**
     * Country prefix → Stripe tax_id type, for the jurisdictions whose VAT numbers carry an ISO prefix.
     *
     * ⚠️ DELIBERATELY A CLOSED LIST. An unlisted prefix returns null rather than defaulting to `eu_vat`, because
     * a default would silently mis-type every non-EU number that happens to start with two letters.
     */
    private const EU_PREFIXES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV',
        'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
        // Northern Ireland trades under the EU VAT regime with an XI prefix.
        'XI',
    ];

    /** Non-EU prefixes Stripe types distinctly. */
    private const OTHER_PREFIXES = [
        'CH' => 'ch_vat',
        'GB' => 'gb_vat',
        'NO' => 'no_vat',
    ];

    /** The minimum plausible body length (digits/letters after the prefix) — below this it is not a VAT number. */
    private const MIN_BODY = 4;

    private const MAX_BODY = 14;

    /** The fewest digits any real VAT body carries — the check that separates a number from a sentence. */
    private const MIN_DIGITS = 4;

    /**
     * The typed Stripe tax ID for a stored VAT string, or NULL when it cannot be confidently typed.
     *
     * @return array{type: string, value: string}|null
     */
    public static function typed(?string $stored): ?array
    {
        $value = self::normalize($stored);

        if ($value === null) {
            return null;
        }

        $prefix = substr($value, 0, 2);
        $body = substr($value, 2);

        // The body must look like a VAT body: alphanumeric, and a plausible length.
        if (! preg_match('/^[A-Z0-9]{'.self::MIN_BODY.','.self::MAX_BODY.'}$/', $body)) {
            return null;
        }

        // ⚠️ AND IT MUST BE MOSTLY A NUMBER. This check was added because the guard caught the validator being
        // too generous: the string "not-a-vat-id" normalises to "NOTAVATID", whose leading "NO" is Norway — so
        // a piece of prose typed into the field was about to be sent to Stripe as a Norwegian VAT number. That
        // is the precise failure this class exists to prevent: a mis-typed id can be ACCEPTED and zero-rate a
        // sale that owes tax. Every real VAT number carries a run of digits; prose does not.
        if (preg_match_all('/[0-9]/', $body) < self::MIN_DIGITS) {
            return null;
        }

        if (in_array($prefix, self::EU_PREFIXES, true)) {
            return ['type' => 'eu_vat', 'value' => $value];
        }

        if (isset(self::OTHER_PREFIXES[$prefix])) {
            return ['type' => self::OTHER_PREFIXES[$prefix], 'value' => $value];
        }

        // ⚠️ An unrecognised prefix is NOT typed as eu_vat "because most customers are European". That guess is
        // exactly how a number gets validated under the wrong regime.
        return null;
    }

    /** Whether a stored VAT string can be sent to Stripe at all. */
    public static function isTypable(?string $stored): bool
    {
        return self::typed($stored) !== null;
    }

    /**
     * Upper-cased, stripped of the spaces, dots and dashes people type — or null if there is nothing usable.
     *
     * ⚠️ Normalisation is presentation-tolerant, NOT validity-tolerant: "pt 123 456 789" is the same number as
     * "PT123456789" and is accepted; "PT" or "not a vat id" is not.
     */
    private static function normalize(?string $stored): ?string
    {
        $value = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $stored) ?? '');

        // Two letters of country prefix plus a body — anything shorter cannot be a prefixed VAT number.
        return strlen($value) >= 2 + self::MIN_BODY ? $value : null;
    }
}
