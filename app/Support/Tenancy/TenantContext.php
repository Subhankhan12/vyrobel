<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use Closure;

/**
 * Holds the current tenant (organization) context for the request/job lifecycle.
 *
 * Bound as a singleton (see AppServiceProvider). Everything tenant-aware reads from here:
 *  - the `BelongsToOrganization` global scope filters queries by {@see id()};
 *  - the auto-fill hook stamps `organization_id` on create;
 *  - the guard throws (via OrganizationScope / the trait) when neither a tenant context
 *    nor an explicit super-admin context is active.
 *
 * Super-admin context is an EXPLICIT, scoped escape hatch (platform administration,
 * cross-tenant maintenance). It is never the default.
 */
class TenantContext
{
    protected ?string $organizationId = null;

    protected bool $superAdmin = false;

    public function set(?string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function id(): ?string
    {
        return $this->organizationId;
    }

    public function hasContext(): bool
    {
        return $this->organizationId !== null;
    }

    public function forget(): void
    {
        $this->organizationId = null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    /**
     * Run a callback for a specific organization, restoring the previous context after.
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    public function forOrganization(string $organizationId, Closure $callback): mixed
    {
        $previousOrg = $this->organizationId;
        $previousSuper = $this->superAdmin;

        $this->organizationId = $organizationId;
        $this->superAdmin = false;

        try {
            return $callback();
        } finally {
            $this->organizationId = $previousOrg;
            $this->superAdmin = $previousSuper;
        }
    }

    /**
     * Run a callback in an explicit super-admin (cross-tenant) context. Inside the callback
     * the tenant global scope is bypassed and the no-context guard does not throw.
     *
     * @template TReturn
     *
     * @param  Closure():TReturn  $callback
     * @return TReturn
     */
    public function runAsSuperAdmin(Closure $callback): mixed
    {
        $previousOrg = $this->organizationId;
        $previousSuper = $this->superAdmin;

        $this->superAdmin = true;
        $this->organizationId = null;

        try {
            return $callback();
        } finally {
            $this->organizationId = $previousOrg;
            $this->superAdmin = $previousSuper;
        }
    }
}
