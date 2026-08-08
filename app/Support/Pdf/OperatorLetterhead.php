<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;

/**
 * The ONE definition of the operator's REGISTERED IDENTITY on a document (audit 2026-08-07 · parity).
 *
 * Every PDF that states who issued it needs the same three real, operator-editable org values —
 * `billing_company` (the registered legal name), `billing_vat_id` (the VAT registration number) and
 * `country`/`billing_city`. Four owner documents already built that letterhead, each with its own copy
 * of the same three lines:
 *
 *     OwnerStatementDocument · OwnerRemittanceDocument · OwnerAgreementDocument
 *     OwnerBookingConfirmationDocument
 *
 * ⚠️ AND THE GUEST VAT INVOICE — the one document with a STATUTORY need for them — had none of it. It
 * printed only the org's trading name, so a tax document went out without its supplier VAT number or
 * registered legal name (audit finding, §5 parity matrix). Adding a fifth private copy of the rule was
 * the obvious fix and the wrong one: the project's own convention is that two `match`es for one concept
 * WILL drift, and a letterhead that disagrees between an owner's remittance and a guest's invoice is
 * exactly the drift that matters. So the rule now lives HERE and all five read it.
 *
 * ⚠️ HONEST-EMPTY, NEVER INVENTED (#39). These fields are frequently unset — the demo org sets none of
 * them. A missing VAT number is OMITTED, never faked, never defaulted to a placeholder, and never
 * inferred from the country. {@see registeredName()} falls back to the trading name because an
 * organization always HAS a name; {@see isRegistered()} reports whether that name is the registered one,
 * so a caller can label it honestly instead of implying a registration that does not exist.
 */
final class OperatorLetterhead
{
    /** The role word for owner-facing documents — the operator manages the owner's units. */
    public const ROLE_MANAGING_AGENT = 'Managing agent';

    /** The role word for guest-facing documents — the operator is the supplier of the stay. */
    public const ROLE_ACCOMMODATION = 'Accommodation provider';

    /**
     * The full identity block, for a document that lays the seller out over several lines
     * (the guest VAT invoice). Every value is real-or-null; nothing is substituted.
     *
     * @return array{
     *     name: string, is_registered: bool, trading_name: string,
     *     vat_id: string|null, city: string|null, country: string|null,
     *     line: string,
     * }
     */
    public static function for(?Organization $org, string $role = self::ROLE_MANAGING_AGENT): array
    {
        $org ??= new Organization;

        return [
            'name' => self::registeredName($org),
            'is_registered' => self::isRegistered($org),
            'trading_name' => (string) ($org->name ?? 'Vyrobel'),
            'vat_id' => self::vatId($org),
            'city' => self::clean($org->billing_city),
            'country' => self::clean($org->country),
            'line' => self::line($org, $role),
        ];
    }

    /** The same block, resolved from an organization id (the shape most documents already hold). */
    public static function forId(?string $organizationId, string $role = self::ROLE_MANAGING_AGENT): array
    {
        $org = $organizationId === null || $organizationId === ''
            ? null
            : Organization::query()->whereKey($organizationId)->first();

        return self::for($org, $role);
    }

    /**
     * The one-line letterhead the owner documents print under the operator name:
     * "Managing agent · PT · VAT PT123456789" — each segment present ONLY when genuinely set.
     *
     * `$countryDisplay` overrides how the country is written. The booking-summary PDF resolves the ISO
     * code to a full country name before printing it, while the others print the code as stored — a real
     * drift that already existed between five hand-rolled copies of this rule. Rather than silently
     * change what an existing document says, the caller passes its resolved form and still shares the
     * rule. Pass null to use the stored value.
     */
    public static function line(?Organization $org, string $role = self::ROLE_MANAGING_AGENT, ?string $countryDisplay = null): string
    {
        $org ??= new Organization;
        $out = $role;

        if (($country = $countryDisplay ?? self::clean($org->country)) !== null && trim($country) !== '') {
            $out .= ' · '.$country;
        }

        if (($vat = self::vatId($org)) !== null) {
            $out .= ' · VAT '.$vat;
        }

        return $out;
    }

    /**
     * The name to print. The REGISTERED legal name when the operator has set one, otherwise the trading
     * name — never a blank document header. Use {@see isRegistered()} to tell the two apart.
     */
    public static function registeredName(?Organization $org): string
    {
        $org ??= new Organization;

        return self::clean($org->billing_company)
            ?? self::clean($org->name)
            ?? 'Vyrobel';
    }

    /** Whether {@see registeredName()} is the operator's REGISTERED legal name (not just the trading name). */
    public static function isRegistered(?Organization $org): bool
    {
        return self::clean($org?->billing_company) !== null;
    }

    /** The supplier's VAT registration number, or NULL when the operator has not set one. Never invented. */
    public static function vatId(?Organization $org): ?string
    {
        return self::clean($org?->billing_vat_id);
    }

    /** Trim to a real value, or null — an empty string is "unset", not a value worth printing. */
    private static function clean(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
