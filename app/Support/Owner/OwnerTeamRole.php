<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Middleware\EnsureOwnerCapability;
use App\Http\Middleware\SetOwnerScopeForPortal;

/**
 * THE fixed owner-team role model (Phase 7c) — a small, capability-scoped set, NOT the operator RBAC catalog.
 * ONE definition of each role's capabilities, so the route gates ({@see EnsureOwnerCapability})
 * and the UI ({@see owner_can()}) can never disagree.
 *
 * The capability grid is taken verbatim from the "Owner Team" design matrix:
 *
 *   Capability                     co_owner  accountant  view_only
 *   ────────────────────────────── ──────── ─────────── ─────────
 *   Earnings & statements          ✓         ✓           ✓          CAP_FINANCIALS
 *   Approvals (rates, expenses)    ✓         ✗           ✗          CAP_ACTIONS
 *   Documents & tax                ✓         ✓           ✗          CAP_DOCUMENTS
 *   Calendar & bookings            ✓         ✗           ✓          CAP_CALENDAR
 *   Exports (download PDFs)        ✓         ✓           ✗          CAP_EXPORTS
 *   Payout account & settings      ✓         ✗           ✗          CAP_PAYOUT  ⚠️ money-security gate
 *
 * NOTE it is NOT a privilege chain — accountant has documents+exports but NOT calendar; view_only has
 * calendar but NOT documents/exports. They are incomparable; only co_owner is a superset of both.
 *
 * The primary owner (`owners.user_id`) is the implicit CO-OWNER for DATA access. Managing the TEAM itself
 * (invite / change-role / remove — {@see CAP_TEAM}) belongs to the PRIMARY OWNER ALONE — the design's
 * Co-owner reads "Can't manage the team" — so team management is gated on {@see OwnerContext::isPrimaryOwner()},
 * NOT on any role. This makes self-escalation structurally impossible: no sub-user, not even a co-owner,
 * can assign a role ({@see canAssign()} requires the primary owner).
 */
final class OwnerTeamRole
{
    public const CO_OWNER = 'co_owner';

    public const ACCOUNTANT = 'accountant';

    public const VIEW_ONLY = 'view_only';

    /** The assignable roles (in privilege order, highest first — used for {@see SetOwnerScopeForPortal}). */
    public const ALL = [self::CO_OWNER, self::ACCOUNTANT, self::VIEW_ONLY];

    /** Capability keys the owner portal gates on. */
    public const CAP_FINANCIALS = 'financials.view';   // earnings & statements (all roles)

    public const CAP_DOCUMENTS = 'documents.view';     // documents & tax (co-owner + accountant)

    public const CAP_CALENDAR = 'calendar.view';       // dashboards, calendar, bookings, units (co-owner + view-only)

    public const CAP_EXPORTS = 'exports';              // download PDFs / exports (co-owner + accountant)

    public const CAP_ACTIONS = 'actions.write';        // approvals — block calendar, rates, requests (co-owner only)

    public const CAP_PAYOUT = 'payout.manage';         // ⚠️ SENSITIVE — payout account & settings (co-owner only)

    public const CAP_TEAM = 'team.manage';             // manage the team — PRIMARY OWNER only (held by no role)

    /**
     * role → capabilities it grants. CAP_TEAM is deliberately absent from EVERY role: team management is
     * the primary owner's prerogative, gated on {@see OwnerContext::isPrimaryOwner()}, not on a role.
     *
     * @var array<string, list<string>>
     */
    private const CAPABILITIES = [
        self::CO_OWNER => [
            self::CAP_FINANCIALS, self::CAP_DOCUMENTS, self::CAP_CALENDAR,
            self::CAP_EXPORTS, self::CAP_ACTIONS, self::CAP_PAYOUT,
        ],
        self::ACCOUNTANT => [
            self::CAP_FINANCIALS, self::CAP_DOCUMENTS, self::CAP_EXPORTS,
        ],
        self::VIEW_ONLY => [
            self::CAP_FINANCIALS, self::CAP_CALENDAR,
        ],
    ];

    /** Whether a role grants a capability (an unknown/null role grants nothing — fail closed on the gate). */
    public static function allows(?string $role, string $capability): bool
    {
        return in_array($capability, self::CAPABILITIES[$role] ?? [], true);
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::ALL, true);
    }

    /**
     * Role-integrity gate: WHO may assign an owner-team role. Only the PRIMARY OWNER may — so a sub-user
     * (even a co-owner) can never grant a role and thus can never escalate itself or anyone else. The target
     * must be one of the three fixed roles.
     */
    public static function canAssign(bool $actorIsPrimaryOwner, string $targetRole): bool
    {
        return $actorIsPrimaryOwner && self::isValid($targetRole);
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

    /** A one-line capability summary for the UI (verbatim from the design's role descriptions). */
    public static function summary(string $role): string
    {
        return match ($role) {
            self::CO_OWNER => 'Everything you see — approvals, money, documents.',
            self::ACCOUNTANT => 'Statements, payouts and tax documents. Read-only, export allowed.',
            self::VIEW_ONLY => 'Dashboards, calendar and reports. No actions, no exports.',
            default => '',
        };
    }
}
