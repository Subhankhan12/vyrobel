<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Concerns\BelongsToOwner;
use App\Models\User;
use App\Support\Owner\OwnerContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Owner-portal row-level scope (Phase 3 · Module 1 Step 6), applied by {@see BelongsToOwner}.
 *
 * Behaviour — the security boundary:
 *  - OwnerContext INACTIVE **and the actor is not owner-linked** (operator/manager, CLI, queue, tests)
 *    → NO constraint (see all owners, still subject to the tenant scope);
 *  - OwnerContext INACTIVE **but the actor IS linked to an owner** → **THROW**
 *    ({@see OwnerContextMissingException}). This is the audit E-1 fix: the boundary now depends on WHO is
 *    asking, not on which route group the request arrived through, so a surface that forgets the middleware
 *    fails LOUDLY instead of returning every owner's rows;
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
            // ⚠️ AUDIT E-1 (2026-08-29) — THIS BRANCH USED TO `return` UNCONDITIONALLY, AND THAT WAS THE HOLE.
            // "Inactive" is the legitimate resting state for operator requests, CLI and queue work, so it
            // cannot simply throw the way {@see OrganizationScope} does — every web request has an org, but
            // most requests legitimately have no owner. The distinguishing question is not "was the middleware
            // run?" (a property of the ROUTE) but "is the actor an owner?" (a property of the ACTOR) — and it
            // was answering the first. One `$refresh` to /livewire/update, where route-group middleware does
            // not re-run, returned all 8 units in the organisation to an owner entitled to 2.
            // ⚠️ SELF-HEAL, DO NOT FALL OPEN. If the actor IS linked to an owner, resolve the boundary NOW
            // from the SAME definition the middleware uses. There is then NO state in which an owner-linked
            // actor reads owner data unscoped, on ANY path — reached through a route group or not.
            //
            // ⚠️ NOTE there is deliberately NO throw here. actorIsOwnerLinked() is true only when a User is
            // authenticated, so a "linked but unresolvable" branch would be UNREACHABLE — dead code that
            // reads as a guard. And resolution cannot fall open: if it yields an empty owner set, the
            // active-but-empty branch below applies `1 = 0`.
            $user = Auth::hasUser() ? Auth::user() : null;

            if ($user instanceof User && $context->actorIsOwnerLinked()) {
                $context->resolveForActor($user);
                $this->apply($builder, $model);   // re-enter, now ACTIVE → constrained below

                return;
            }

            return; // genuine operator / CLI / queue read — no owner boundary applies
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
