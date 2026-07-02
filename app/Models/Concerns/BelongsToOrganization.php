<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Exceptions\TenantContextMissingException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\PropertyAssignment;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Convention #1 — tenancy. Every tenant-scoped business model uses this trait. It:
 *   1. applies the {@see OrganizationScope} global scope (filter every read by the active org);
 *   2. auto-fills `organization_id` on create from the active {@see TenantContext};
 *   3. throws {@see TenantContextMissingException} if a create runs with neither an org
 *      context nor an explicit super-admin context.
 *
 * The read-side guard lives in {@see OrganizationScope}; the write-side guard lives here.
 *
 * Deliberate opt-outs: the tenancy-linking pivots {@see Membership} (organization_user)
 * and {@see PropertyAssignment} (property_user) do NOT use this trait — a user must be
 * able to read their memberships/assignments across every org. The append-only logs and
 * polymorphic-owner tables (audit_events, domain_events, settings) also opt out (org stamped
 * explicitly, not globally scoped). Do not add the scope to those.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('organization_id') !== null) {
                return; // explicitly set (e.g. super-admin provisioning)
            }

            $context = app(TenantContext::class);

            if ($context->hasContext()) {
                $model->setAttribute('organization_id', $context->id());

                return;
            }

            if ($context->isSuperAdmin()) {
                return; // super-admin must set organization_id explicitly
            }

            throw TenantContextMissingException::for(static::class);
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Escape hatch for building a query without the tenant global scope. Use only in
     * explicit super-admin / platform-maintenance code paths.
     */
    public static function withoutOrganizationScope(): Builder
    {
        return static::query()->withoutGlobalScope(OrganizationScope::class);
    }
}
