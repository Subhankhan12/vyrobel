<?php

declare(strict_types=1);

namespace App\Support\OAuth;

use App\Support\Owner\OwnerTeamRole;

/**
 * THE ONE OAuth scope catalog (Wave D · Passport). Least-privilege, READ-FIRST: each scope maps to a slice of
 * the owner's OWN data AND to an {@see OwnerTeamRole} capability — so a token can never grant more than the
 * owner's role already permits (the "no over-grant" guarantee, enforced at the API by EnsureApiScopeCapability).
 *
 * Single source of truth for:
 *   • the human descriptions the CONSENT screen shows (so the owner sees exactly what each scope means),
 *   • `Passport::tokensCan()` (the catalog Passport validates requested scopes against),
 *   • the scope→capability map the API gate checks against the resolved owner role.
 *
 * WRITE scopes + client-credentials are deliberately NOT here (no real third-party write/machine use case yet);
 * a future `write:*` scope slots in the same way, mapping to CAP_ACTIONS (co-owner only).
 */
final class OAuthScopes
{
    public const READ_BOOKINGS = 'read:bookings';

    public const READ_STATEMENTS = 'read:statements';

    public const READ_UNITS = 'read:units';

    /**
     * scope → human description (what the owner sees on the consent screen).
     *
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        return [
            self::READ_BOOKINGS => 'Read your bookings — reference, guest name, dates, unit and status (never guest contact details).',
            self::READ_STATEMENTS => 'Read your statements — periods, amounts and status.',
            self::READ_UNITS => 'Read your units — code, name, type and occupancy (never door codes or Wi-Fi).',
        ];
    }

    /**
     * scope → the owner-team capability it requires. A token may carry a scope ONLY if the bound owner's role
     * holds this capability (else the API 403s — no over-grant). Reads the certified {@see OwnerTeamRole} caps.
     *
     * @return array<string, string>
     */
    public static function capabilityMap(): array
    {
        return [
            self::READ_BOOKINGS => OwnerTeamRole::CAP_CALENDAR,     // bookings live behind calendar.view
            self::READ_STATEMENTS => OwnerTeamRole::CAP_FINANCIALS, // statements behind financials.view (all roles)
            self::READ_UNITS => OwnerTeamRole::CAP_CALENDAR,        // units behind calendar.view
        ];
    }

    /** The capability a scope requires, or null for an unknown scope (fail closed at the caller). */
    public static function capabilityFor(string $scope): ?string
    {
        return self::capabilityMap()[$scope] ?? null;
    }

    /** @return list<string> every defined scope id */
    public static function all(): array
    {
        return array_keys(self::catalog());
    }
}
