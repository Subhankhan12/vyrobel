<?php

declare(strict_types=1);

namespace App\Support\Owner;

use App\Http\Middleware\EnsureOwnerApiScope;
use App\Http\Middleware\ResolveApiOwnerContext;
use App\Http\Middleware\SetOwnerScopeForPortal;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\Scopes\OwnerScope;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Support\Facades\Auth;

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
     * Memoised answer to "is the acting user linked to any owner?" — resolved at most ONCE per actor.
     * ⚠️ THIS IS READ FROM INSIDE A GLOBAL SCOPE (audit E-1 fix), so it runs on every query of all 14
     * owner-scoped models. It MUST NOT issue a query per call.
     *
     * ⚠️ KEYED TO THE ACTOR, NOT A BARE BOOL. A singleton outlives a request in a long-running worker
     * (Horizon/queue:work), where nothing calls deactivate() between jobs — a bare bool could survive into
     * the next job and answer for the wrong actor. Keying it to the user id makes a different (or absent)
     * actor invalidate it automatically. `null` = not yet resolved for the current actor.
     */
    private ?bool $actorOwnerLinked = null;

    /** The actor id the memo above was resolved FOR ('' = no authenticated actor). */
    private ?string $actorOwnerLinkedFor = null;

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
        $this->actorOwnerLinked = null;
        $this->actorOwnerLinkedFor = null;
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
     * THE ONE OWNER-LINK RESOLUTION (audit E-1). Resolves the owner ids + per-owner roles for a user and
     * ACTIVATES this context for them. {@see SetOwnerScopeForPortal} calls it on the portal routes, and
     * {@see OwnerScope} calls it lazily for an owner-linked actor whose request never
     * ran that middleware (`/livewire/update`).
     *
     * ⚠️ ONE DEFINITION, NOT TWO. A second copy of "which owners is this user linked to?" would drift from
     * the middleware's copy, and the two would disagree about a security boundary (§N.8f).
     *
     * Both queries are TENANT-scoped, so a user only ever resolves owners inside the active org: the nesting
     * sub-user ⊆ owner ⊆ org holds. Primary ownership wins over a sub-user membership on the same owner, and
     * a role is recorded PER OWNER — never the union/highest across owners (the P1-1 over-grant).
     */
    public function resolveForActor(User $user): void
    {
        $roles = [];

        $primaryOwnerIds = Owner::query()->where('user_id', $user->getKey())->pluck('id')
            ->map(fn ($id): string => (string) $id)->all();
        foreach ($primaryOwnerIds as $ownerId) {
            $roles[$ownerId] = OwnerTeamRole::CO_OWNER;
        }

        $subMemberships = OwnerUser::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->get(['owner_id', 'role']);
        foreach ($subMemberships as $membership) {
            $ownerId = (string) $membership->owner_id;
            if (! isset($roles[$ownerId])) {
                $roles[$ownerId] = (string) $membership->role;
            }
        }

        $this->activatePerOwner($roles, $primaryOwnerIds);
    }

    /**
     * Whether the ACTING USER is linked to at least one owner (as primary account holder or as an active
     * owner-team sub-user) in the CURRENT tenant — regardless of whether the owner context was established.
     *
     * ⚠️ AUDIT E-1. {@see OwnerScope} asks this when the context is INACTIVE, to tell a
     * legitimate operator/CLI read (no owner links → no constraint) apart from an owner reading through a path
     * that never established the scope (owner links → THROW rather than return every owner's rows).
     *
     * ⚠️ Resolved AT MOST ONCE per request and memoised — it is reached from a global scope, so a query per
     * call would be a per-model-query N+1. Returns FALSE (cheaply, no query) when there is no authenticated
     * user or no tenant context, which is the CLI/queue resting state.
     *
     * ⚠️ It deliberately reuses the SAME two link sources as {@see SetOwnerScopeForPortal} (owners.user_id and
     * an ACTIVE owner_users row). Both are tenant-scoped, so this can never see another org's links.
     */
    public function actorIsOwnerLinked(): bool
    {
        // No resolved user (CLI, queue, unauthenticated) → nothing to scope to. Never triggers the user provider.
        if (! Auth::hasUser()) {
            return $this->memo('', false);
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->memo('', false);            // guest realm / platform admin are not owner actors
        }

        $actorId = (string) $user->getKey();

        if ($this->actorOwnerLinked !== null && $this->actorOwnerLinkedFor === $actorId) {
            return $this->actorOwnerLinked;
        }

        // Owner + OwnerUser are TENANT-scoped; without a tenant context those queries would themselves throw.
        if (! app(TenantContext::class)->hasContext()) {
            return $this->memo($actorId, false);
        }

        $linked = Owner::query()->where('user_id', $user->getKey())->exists()
            || OwnerUser::query()->where('user_id', $user->getKey())->where('status', 'active')->exists();

        return $this->memo($actorId, $linked);
    }

    /** Record the owner-link answer against the actor it was resolved for. */
    private function memo(string $actorId, bool $linked): bool
    {
        $this->actorOwnerLinkedFor = $actorId;

        return $this->actorOwnerLinked = $linked;
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
