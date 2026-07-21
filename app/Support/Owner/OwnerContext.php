<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\Scopes\OwnerScope;
use App\Support\Tenancy\TenantContext;
use Closure;

/**
 * Holds the current OWNER-PORTAL scope for the request lifecycle (Phase 3 · Module 1 Step 6) — a
 * row-level boundary WITHIN a tenant, stricter than {@see TenantContext}. An
 * external owner shares the operator's org with the operator and other owners, so tenant scope alone
 * is not enough: this restricts owner-facing reads to the owner(s) the authenticated portal user is
 * linked to.
 *
 * Bound as a singleton (see AppServiceProvider). It is INACTIVE by default — operator/manager
 * requests, CLI, queue and tests run with no owner scope (they see all owners, subject to tenant
 * scope). It is ACTIVATED only by {@see SetOwnerScopeForPortal} on the owner
 * portal routes, and deactivated when that request terminates.
 *
 * FAIL-CLOSED: when active with an EMPTY owner set (a non-linked user reaching the portal), the
 * {@see OwnerScope} returns NOTHING — it never falls open to all owners.
 */
class OwnerContext
{
    private bool $active = false;

    /** @var list<string> */
    private array $ownerIds = [];

    /**
     * The acting user's OWNER-TEAM role on the scoped owner (Phase 7c): 'co_owner' (incl. the primary owner),
     * 'accountant', 'view_only', or null when the user is not an owner/sub-user. Drives capability gating.
     */
    private ?string $role = null;

    /**
     * Whether the acting user is the PRIMARY owner (the `owners.user_id` account holder) as opposed to an
     * invited sub-user. Team management (invite/change-role/remove) is the primary owner's alone
     * ({@see OwnerTeamRole::canAssign()}), so this — not the role — gates it.
     */
    private bool $primaryOwner = false;

    /**
     * Activate portal scope for the given owner ids (may be empty → fail-closed) + the acting user's role
     * + whether they are the primary owner.
     *
     * @param  list<string>  $ownerIds
     */
    public function activate(array $ownerIds, ?string $role = null, bool $primaryOwner = false): void
    {
        $this->active = true;
        $this->ownerIds = array_values(array_unique($ownerIds));
        $this->role = $role;
        $this->primaryOwner = $primaryOwner;
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->ownerIds = [];
        $this->role = null;
        $this->primaryOwner = false;
    }

    /** The acting user's owner-team role on the scoped owner (co_owner/accountant/view_only), or null. */
    public function role(): ?string
    {
        return $this->role;
    }

    /** Whether the acting user is the primary owner (the account holder), not an invited sub-user. */
    public function isPrimaryOwner(): bool
    {
        return $this->primaryOwner;
    }

    /**
     * Whether the acting user is an invited SUB-USER (owner-team member) rather than the primary owner or a
     * non-linked user — i.e. they have owner access but do not own the account. Drives the honest "managed by
     * the account owner" copy on the self-only tax/payout screens (a truly non-linked user keeps the plain
     * "no owner linked" state).
     */
    public function isSubUser(): bool
    {
        return ! $this->primaryOwner && $this->ownerIds !== [];
    }

    /** Whether the acting user holds a given owner-team capability ({@see OwnerTeamRole}). */
    public function can(string $capability): bool
    {
        return OwnerTeamRole::allows($this->role, $capability);
    }

    public function active(): bool
    {
        return $this->active;
    }

    /**
     * @return list<string>
     */
    public function ownerIds(): array
    {
        return $this->ownerIds;
    }

    /**
     * Run a callback under an explicit owner-portal scope, restoring the previous state after
     * (mainly for tests / programmatic portal reads).
     *
     * @template TReturn
     *
     * @param  list<string>  $ownerIds
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    public function forOwners(array $ownerIds, Closure $callback): mixed
    {
        $previousActive = $this->active;
        $previousIds = $this->ownerIds;
        $previousRole = $this->role;
        $previousPrimary = $this->primaryOwner;

        // A programmatic owner read acts as the primary owner (full data + team).
        $this->activate($ownerIds, OwnerTeamRole::CO_OWNER, true);

        try {
            return $callback();
        } finally {
            $this->active = $previousActive;
            $this->ownerIds = $previousIds;
            $this->role = $previousRole;
            $this->primaryOwner = $previousPrimary;
        }
    }
}
