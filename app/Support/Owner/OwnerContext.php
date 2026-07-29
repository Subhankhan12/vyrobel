<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Middleware\EnsureOwnerApiScope;
use App\Http\Middleware\ResolveApiOwnerContext;
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

    /**
     * THE PER-OWNER role map (P1-1 fix) — ownerId => the acting user's owner-team role ON THAT owner
     * ('co_owner'/'accountant'/'view_only', or null). A user who is co-owner of Owner A and view-only of Owner B
     * holds DIFFERENT roles here, so a capability is granted per-owner, NEVER as the union/highest across all.
     * `ownerIds()` is the keys (the read scope); {@see canOn()} / {@see isPrimaryOwnerOf()} are the authoritative
     * per-owner gates.
     *
     * @var array<string, string|null>
     */
    private array $roles = [];

    /**
     * The owners for which the acting user is the PRIMARY owner (the `owners.user_id` account holder) — PER
     * OWNER, not a global flag. Team management (invite/change-role/remove) on an owner is the primary owner of
     * THAT owner's alone ({@see OwnerTeamRole::canAssign()}), so {@see isPrimaryOwnerOf()} — not a global bool —
     * gates it.
     *
     * @var list<string>
     */
    private array $primaryOwnerIds = [];

    /**
     * Activate portal scope for the given owner ids (may be empty → fail-closed) with a UNIFORM role applied to
     * every owner. Kept for programmatic reads ({@see forOwners()}) and read-only scope tests; the request
     * middleware use {@see activatePerOwner()} so a multi-owner user's role is resolved PER OWNER (the P1-1 fix).
     *
     * @param  list<string>  $ownerIds
     */
    public function activate(array $ownerIds, ?string $role = null, bool $primaryOwner = false): void
    {
        $ownerIds = array_values(array_unique($ownerIds));
        $roles = [];
        foreach ($ownerIds as $id) {
            $roles[(string) $id] = $role;
        }

        $this->activatePerOwner($roles, $primaryOwner ? $ownerIds : []);
    }

    /**
     * THE per-owner activation (P1-1) — the acting user's real role on EACH owner + the owners they are the
     * primary of. Used by {@see SetOwnerScopeForPortal} and {@see ResolveApiOwnerContext}.
     *
     * @param  array<string, string|null>  $roles  ownerId => role
     * @param  list<string>  $primaryOwnerIds
     */
    public function activatePerOwner(array $roles, array $primaryOwnerIds = []): void
    {
        $this->active = true;
        $this->roles = [];
        foreach ($roles as $id => $role) {
            $this->roles[(string) $id] = $role;
        }
        $this->primaryOwnerIds = array_values(array_unique(array_map('strval', $primaryOwnerIds)));
    }

    /**
     * Narrow the active owner set to a subset (keeping each kept owner's role) — used by the API scope gate
     * ({@see EnsureOwnerApiScope}) to restrict a token's reads to the owners whose role
     * actually backs the scope's capability, so a token never inherits the union role either.
     *
     * @param  list<string>  $ownerIds
     */
    public function intersectOwners(array $ownerIds): void
    {
        $keep = array_flip(array_map('strval', $ownerIds));
        $this->roles = array_intersect_key($this->roles, $keep);
        $this->primaryOwnerIds = array_values(array_filter($this->primaryOwnerIds, fn (string $id): bool => isset($keep[$id])));
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->roles = [];
        $this->primaryOwnerIds = [];
    }

    /** The acting user's role on a SPECIFIC owner (co_owner/accountant/view_only), or null if not a member. */
    public function roleFor(?string $ownerId): ?string
    {
        return $ownerId !== null ? ($this->roles[$ownerId] ?? null) : null;
    }

    /** Whether the acting user's role ON $ownerId grants $capability (the authoritative per-owner gate). */
    public function canOn(?string $ownerId, string $capability): bool
    {
        return OwnerTeamRole::allows($this->roleFor($ownerId), $capability);
    }

    /** Whether the acting user is the PRIMARY owner of a SPECIFIC owner (per-owner, not global). */
    public function isPrimaryOwnerOf(?string $ownerId): bool
    {
        return $ownerId !== null && in_array($ownerId, $this->primaryOwnerIds, true);
    }

    /**
     * The subset of the scoped owner ids where the acting user's role grants $capability. The read-narrowing
     * primitive for capability-sensitive surfaces (the API, the masked IBAN, the editable room types).
     *
     * @return list<string>
     */
    public function ownerIdsWithCapability(string $capability): array
    {
        return array_values(array_filter(
            $this->ownerIds(),
            fn (string $ownerId): bool => $this->canOn($ownerId, $capability),
        ));
    }

    /**
     * The owners the acting user is the primary of (the team-management set).
     *
     * @return list<string>
     */
    public function primaryOwnerIds(): array
    {
        return $this->primaryOwnerIds;
    }

    /**
     * The acting user's role for DISPLAY only — the highest role held across their owners. NEVER use this to
     * authorize a per-owner action (use {@see canOn()}); it exists for UI labels where a single label is shown.
     */
    public function role(): ?string
    {
        foreach (OwnerTeamRole::ALL as $candidate) {   // highest-first
            if (in_array($candidate, $this->roles, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * COARSE — whether the acting user is the primary owner of ANY owner. For a single-owner user this is exact;
     * it is the first-filter route gate ({@see EnsurePrimaryOwner}), backed by the per-owner
     * {@see isPrimaryOwnerOf()} at the point the target owner is known.
     */
    public function isPrimaryOwner(): bool
    {
        return $this->primaryOwnerIds !== [];
    }

    /**
     * Whether the acting user is an invited SUB-USER (owner-team member) rather than the primary owner or a
     * non-linked user — i.e. they have owner access but do not own the account. Drives the honest "managed by
     * the account owner" copy on the self-only tax/payout screens (a truly non-linked user keeps the plain
     * "no owner linked" state).
     */
    public function isSubUser(): bool
    {
        return $this->primaryOwnerIds === [] && $this->roles !== [];
    }

    /**
     * COARSE — whether the acting user holds $capability on ANY of their owners. For a single-owner user this is
     * exact; it is the first-filter route gate ({@see EnsureOwnerCapability}), backed by the per-owner
     * {@see canOn()} at the point the target owner is known.
     */
    public function can(string $capability): bool
    {
        return $this->ownerIdsWithCapability($capability) !== [];
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
        return array_keys($this->roles);
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
        $previousRoles = $this->roles;
        $previousPrimary = $this->primaryOwnerIds;

        // A programmatic owner read acts as the primary owner (full data + team) on every named owner.
        $this->activate($ownerIds, OwnerTeamRole::CO_OWNER, true);

        try {
            return $callback();
        } finally {
            $this->active = $previousActive;
            $this->roles = $previousRoles;
            $this->primaryOwnerIds = $previousPrimary;
        }
    }
}
