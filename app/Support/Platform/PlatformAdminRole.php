<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Middleware\EnsurePlatformCapability;
use App\Models\PlatformAdmin;

/**
 * ADMIN 01 · PHASE 1 — the platform-console ROLES and the ONE capability matrix.
 *
 * ⚠️ ONE DEFINITION, TWO READERS: the route gate ({@see EnsurePlatformCapability}) and the UI
 * (`PlatformAdmin::may()`) both ask THIS matrix, so a control can never be shown to a role the route refuses,
 * or hidden from one it admits (§N.8f — the same discipline as OwnerTeamRole).
 *
 * The four roles, as ruled:
 *   · OWNER    — everything.
 *   · SUPPORT  — tenants and support tools; no billing, no switches.
 *   · BILLING  — revenue and subscriptions; no support tools.
 *   · ANALYST  — read-only, everywhere it is allowed to look.
 *
 * ⚠️⚠️ A NULL OR UNKNOWN ROLE FAILS CLOSED — {@see fromAdmin()} returns null and {@see allows()} is then false
 * for every capability, including merely viewing the console.
 */
enum PlatformAdminRole: string
{
    case Owner = 'owner';
    case Support = 'support';
    case Billing = 'billing';
    case Analyst = 'analyst';

    /** See the console at all: health board, overview, customers (metadata), plans, activity, audit, switches. */
    public const CONSOLE_VIEW = 'console.view';

    /** Tenant lifecycle: close/reopen storefront, mark internal, (Phase 2) suspend/archive. */
    public const TENANTS_MANAGE = 'tenants.manage';

    /** See revenue and subscription money (Phase 4). */
    public const BILLING_VIEW = 'billing.view';

    /** Change what a customer pays for: assign a plan, edit the plan catalogue, comp / extend (Phase 4). */
    public const BILLING_MANAGE = 'billing.manage';

    /** Support tools (Phase 3): user lookup, unlink, manual verify, resend, break-glass. */
    public const SUPPORT_USE = 'support.use';

    /** Flip a platform kill switch. */
    public const SWITCHES_MANAGE = 'switches.manage';

    /** See / change the platform's master channel credentials. */
    public const INTEGRATIONS_VIEW = 'integrations.view';

    public const INTEGRATIONS_MANAGE = 'integrations.manage';

    /** See / manage the platform admins themselves: invite, role, revoke, sessions. */
    public const ADMINS_VIEW = 'admins.view';

    public const ADMINS_MANAGE = 'admins.manage';

    /**
     * THE MATRIX. ⚠️ Adding a capability means adding it here AND to the route that needs it — the route-gate
     * test fails for any console route that carries no capability at all.
     *
     * @return list<string>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::Owner => [
                self::CONSOLE_VIEW, self::TENANTS_MANAGE, self::BILLING_VIEW, self::BILLING_MANAGE,
                self::SUPPORT_USE, self::SWITCHES_MANAGE, self::INTEGRATIONS_VIEW, self::INTEGRATIONS_MANAGE,
                self::ADMINS_VIEW, self::ADMINS_MANAGE,
            ],
            self::Support => [self::CONSOLE_VIEW, self::TENANTS_MANAGE, self::SUPPORT_USE],
            self::Billing => [self::CONSOLE_VIEW, self::BILLING_VIEW, self::BILLING_MANAGE],
            self::Analyst => [self::CONSOLE_VIEW, self::BILLING_VIEW, self::INTEGRATIONS_VIEW, self::ADMINS_VIEW],
        };
    }

    /** @return list<string> every capability that exists — for validating a route gate's argument. */
    public static function allCapabilities(): array
    {
        return self::Owner->capabilities();
    }

    public function allows(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Support => 'Support',
            self::Billing => 'Billing',
            self::Analyst => 'Analyst',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Everything, including switches, integrations and other admins.',
            self::Support => 'Customers and support tools. No billing, no switches.',
            self::Billing => 'Revenue, plans and subscriptions. No support tools.',
            self::Analyst => 'Read-only everywhere it may look. Changes nothing.',
        };
    }

    /** ⚠️ Null for a null/unknown stored role — the caller must treat that as "allowed nothing". */
    public static function fromAdmin(?PlatformAdmin $admin): ?self
    {
        return $admin === null ? null : self::tryFrom((string) $admin->role);
    }
}
