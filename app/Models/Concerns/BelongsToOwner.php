<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\OwnerScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the owner-portal {@see OwnerScope} to owner-facing models (Phase 3 · Module 1 Step 6):
 * units, owner_statements, owner_statement_lines, payouts, owner_ledger, owner_expenses. The scope
 * is INACTIVE outside an owner-portal request, so operator/manager/service/test code is unaffected;
 * inside the portal it restricts reads to the authenticated owner and FAILS CLOSED on an empty scope.
 *
 * A model whose owner foreign key is not literally `owner_id` may override {@see getOwnerColumn()}.
 */
trait BelongsToOwner
{
    public static function bootBelongsToOwner(): void
    {
        static::addGlobalScope(new OwnerScope);
    }

    public function getOwnerColumn(): string
    {
        return 'owner_id';
    }

    /**
     * Escape hatch for a query without the owner scope (operator/platform paths).
     */
    public static function withoutOwnerScope(): Builder
    {
        return static::query()->withoutGlobalScope(OwnerScope::class);
    }
}
