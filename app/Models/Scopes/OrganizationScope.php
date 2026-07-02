<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Exceptions\TenantContextMissingException;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope applied by the {@see BelongsToOrganization} trait.
 *
 * Behaviour:
 *  - super-admin context  → no constraint added (cross-tenant read allowed);
 *  - active org context   → constrain to that organization_id;
 *  - neither               → THROW. We never run an unscoped tenant query by accident.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isSuperAdmin()) {
            return;
        }

        if (! $context->hasContext()) {
            throw TenantContextMissingException::for($model::class);
        }

        $builder->where(
            $model->qualifyColumn('organization_id'),
            $context->id()
        );
    }
}
