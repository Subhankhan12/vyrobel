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
     * Activate portal scope for the given owner ids (may be empty → fail-closed).
     *
     * @param  list<string>  $ownerIds
     */
    public function activate(array $ownerIds): void
    {
        $this->active = true;
        $this->ownerIds = array_values(array_unique($ownerIds));
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->ownerIds = [];
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

        $this->activate($ownerIds);

        try {
            return $callback();
        } finally {
            $this->active = $previousActive;
            $this->ownerIds = $previousIds;
        }
    }
}
