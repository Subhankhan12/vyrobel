<?php

declare(strict_types=1);

namespace App\Support\Owner;

/**
 * THE fixed owner-team role model (Phase 7c) — a small, capability-scoped set, NOT the operator RBAC catalog.
 * ONE definition of each role's capabilities, so the gates and the UI can never disagree.
 *
 * Roles (per the "Owner Team" design):
 *   • co_owner  — "Full access to everything, including this page" (the owner-admin: team + payout settings).
 *   • accountant — "Earnings & statements · Documents & tax" (financial read + exports; NO payout, NO team).
 *   • view_only  — "Dashboards, calendar and reports. No actions, no exports."
 *
 * The primary owner (`owners.user_id`) is the implicit CO-OWNER. The **payout account & settings** capability
 * (the encrypted IBAN / where the money goes) is CO-OWNER ONLY — the money-security gate.
 */
final class OwnerTeamRole
{
    public const CO_OWNER = 'co_owner';

    public const ACCOUNTANT = 'accountant';

    public const VIEW_ONLY = 'view_only';

    /** The assignable roles (in privilege order, highest first). */
    public const ALL = [self::CO_OWNER, self::ACCOUNTANT, self::VIEW_ONLY];

    /** Capability keys the owner portal gates on. */
    public const CAP_FINANCIALS = 'financials.view';   // earnings & statements

    public const CAP_DOCUMENTS = 'documents.view';     // documents & tax

    public const CAP_CALENDAR = 'calendar.view';       // dashboards, calendar, bookings, reports

    public const CAP_EXPORTS = 'exports';              // download PDFs / exports

    public const CAP_ACTIONS = 'actions.write';        // owner write actions (block calendar, rates, requests…)

    public const CAP_PAYOUT = 'payout.manage';         // ⚠️ SENSITIVE — payout account & settings (co-owner only)

    public const CAP_TEAM = 'team.manage';             // manage the team + assign roles (co-owner only)

    /** @var array<string, list<string>> role → capabilities it grants */
    private const CAPABILITIES = [
        self::CO_OWNER => [
            self::CAP_FINANCIALS, self::CAP_DOCUMENTS, self::CAP_CALENDAR,
            self::CAP_EXPORTS, self::CAP_ACTIONS, self::CAP_PAYOUT, self::CAP_TEAM,
        ],
        self::ACCOUNTANT => [
            self::CAP_FINANCIALS, self::CAP_DOCUMENTS, self::CAP_CALENDAR, self::CAP_EXPORTS,
        ],
        self::VIEW_ONLY => [
            self::CAP_FINANCIALS, self::CAP_DOCUMENTS, self::CAP_CALENDAR,
        ],
    ];

    /** Whether a role grants a capability (an unknown role grants nothing — fail closed on the gate). */
    public static function allows(?string $role, string $capability): bool
    {
        return in_array($capability, self::CAPABILITIES[$role] ?? [], true);
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::ALL, true);
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::CO_OWNER => 'Co-owner',
            self::ACCOUNTANT => 'Accountant',
            self::VIEW_ONLY => 'View only',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    /** A one-line capability summary for the UI (matches the design's descriptions). */
    public static function summary(string $role): string
    {
        return match ($role) {
            self::CO_OWNER => 'Full access to everything, including this page.',
            self::ACCOUNTANT => 'Earnings & statements · Documents & tax.',
            self::VIEW_ONLY => 'Dashboards, calendar and reports. No actions, no exports.',
            default => '',
        };
    }
}
