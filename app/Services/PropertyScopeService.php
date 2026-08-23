<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Operator\PropertyController;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * P-7 — THE ONE WRITE PATH FOR PER-PROPERTY STAFF SCOPING (`property_user`).
 *
 * ⚠️⚠️ WHY THIS EXISTS. Per-property scoping was UNREACHABLE. The pivot's only writer in `app/` was
 * {@see PropertyController::store()}, and it is guarded by
 * `$user->propertyAssignments()->exists()` — ⚠️ **a user can only GAIN a pivot row if they ALREADY have
 * one**, so from an empty set the scoped population can never grow. ⚠️ THAT GUARD IS NOT A BUG AND IS NOT
 * BEING CHANGED: its job is to keep an EXISTING allow-list consistent when its holder adds a property
 * ("otherwise they'd create a property they immediately couldn't select"), and inverting it would silently
 * NARROW a full-org user to one property. What was missing was the assignment path itself — this service.
 *
 * ⚠️ THE ACCESS RULE IT WRITES INTO IS RATIFIED, NOT A DEFECT (#38, {@see User::accessiblePropertyIds()}):
 * a user with pivot rows is restricted to them; a user with NONE gets the whole org. "Empty pivot ⇒ full
 * org" is deliberate — it keeps single-property behaviour unregressed and locks nobody out. So the fix for
 * the silent widening is NOT to change the fallback; it is to make the restricted state REACHABLE, which is
 * what this does.
 *
 * ⚠️ TENANCY IS EXPLICIT HERE, AND MUST BE. {@see PropertyAssignment} deliberately opts OUT of
 * {@see BelongsToOrganization} (a user reads assignments across orgs), so it has
 * NEITHER the read scope NOR the `organization_id` auto-fill. Nothing catches a cross-org id for us: both
 * the target user and every property id are re-resolved inside the ACTIVE tenant before anything is written.
 */
class PropertyScopeService
{
    public function __construct(
        private TenantContext $tenant,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Restrict a staff member to `$propertyIds`, or pass an empty list to return them to full-org access.
     *
     * @param  list<string>  $propertyIds
     * @return list<string> the ids actually in force afterwards
     */
    public function setScope(User $user, array $propertyIds): array
    {
        $orgId = (string) $this->tenant->id();

        if ($orgId === '') {
            throw new RuntimeException('Property scoping requires an organization context.');
        }

        // ⚠️ THE TARGET MUST BE A MEMBER OF THE ACTIVE ORG. Resolved from the actor's own tenant, never
        // trusted from the request — the operator-realm analogue of #28. A foreign user id finds nothing.
        $isMember = $user->memberships()->where('organization_id', $orgId)->exists();

        if (! $isMember) {
            throw new RuntimeException('That user is not a member of this organisation.');
        }

        // ⚠️ EVERY PROPERTY ID RE-RESOLVED UNDER THE TENANT SCOPE. `Property` IS tenant-scoped, so a
        // sibling organisation's property id simply does not come back — it is dropped, never written.
        $resolved = $propertyIds === []
            ? []
            : Property::query()->whereIn('id', $propertyIds)->orderBy('name')
                ->pluck('id')->map(fn ($id): string => (string) $id)->values()->all();

        $before = $user->propertyAssignments()->where('organization_id', $orgId)
            ->pluck('property_id')->map(fn ($id): string => (string) $id)->sort()->values()->all();

        DB::transaction(function () use ($user, $orgId, $resolved): void {
            // Replace this org's rows only — a user scoped in another organisation keeps that scoping.
            $user->propertyAssignments()->where('organization_id', $orgId)->delete();

            foreach ($resolved as $propertyId) {
                PropertyAssignment::create([
                    'organization_id' => $orgId,      // ⚠️ explicit: this model has no auto-fill
                    'user_id' => (string) $user->getKey(),
                    'property_id' => $propertyId,
                ]);
            }
        });

        // ⚠️ AN ACCESS CHANGE IS A SECURITY EVENT. Property ids are not secrets, so the before/after sets
        // are recorded in full — unlike the arrival secrets, whose VALUES must never enter this table.
        $this->audit->record(
            'user.property_scope_changed',
            $user,
            ['property_ids' => $before, 'scope' => $before === [] ? 'all' : 'selected'],
            ['property_ids' => $resolved, 'scope' => $resolved === [] ? 'all' : 'selected'],
        );

        return $resolved;
    }
}
