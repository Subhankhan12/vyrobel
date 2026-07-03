<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Concerns\BelongsToOwner;
use App\Support\Owner\OwnerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Owner-portal row-level scope (Phase 3 · Module 1 Step 6), applied by {@see BelongsToOwner}.
 *
 * Behaviour — the security boundary:
 *  - OwnerContext INACTIVE (operator/manager, CLI, queue, tests) → NO constraint (see all owners,
 *    still subject to the tenant scope);
 *  - OwnerContext ACTIVE with owner ids → constrain to `owner_id IN (ids)`;
 *  - OwnerContext ACTIVE but EMPTY (a non-linked user on the portal) → **FAIL CLOSED**: `1 = 0`,
 *    return nothing. It NEVER falls open to all owners.
 */
class OwnerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(OwnerContext::class);

        if (! $context->active()) {
            return; // operator side — owner scope does not apply
        }

        $ownerIds = $context->ownerIds();

        if ($ownerIds === []) {
            $builder->whereRaw('1 = 0'); // fail closed — never all owners

            return;
        }

        /** @var string $column */
        $column = method_exists($model, 'getOwnerColumn') ? $model->getOwnerColumn() : 'owner_id';

        $builder->whereIn($model->qualifyColumn($column), $ownerIds);
    }
}
