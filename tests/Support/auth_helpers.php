<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Owner;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

// Shared auth/authorization test helpers. In a Support file (required from Pest.php) so any test can
// reuse them without depending on cross-file function-load order — same discipline as owner_helpers.php.

if (! function_exists('grantBackOfficeRole')) {
    /**
     * Give a user a REAL back-office role in an org, the way production does.
     *
     * WHY THIS EXISTS: `/app` is gated by {@see EnsureBackOfficeAccess}, an
     * ALLOW-LIST of `config('authorization.back_office_roles')` checked team-scoped. A Membership alone
     * is NOT operator access — an owner has one too (it is the only way their org resolves), which was
     * exactly the hole. So a test operator must hold a real catalog role, as a real invited staff member
     * does via OwnerInviteService::assignStaffRole().
     *
     * Roles are provisioned first (idempotent — as org-creation / the demo seeder do), then assigned
     * under the org's team id, restoring the caller's team afterwards: the same save → set → act →
     * restore idiom as OrganizationRoleProvisioner / HorizonServiceProvider::gate().
     */
    function grantBackOfficeRole(User $user, Organization|string $organization, string $role = 'Hotel Manager'): User
    {
        $org = $organization instanceof Organization
            ? $organization
            : Organization::query()->withoutGlobalScopes()->findOrFail($organization);

        // Provision once per org per process. provision() is idempotent but expensive (13 roles x 44
        // permissions, plus a spatie cache flush each time); a test that builds several operators for one
        // org would otherwise pay it repeatedly. Org ids are fresh ULIDs per test, so this never goes
        // stale across a RefreshDatabase rollback.
        static $provisioned = [];
        $orgId = (string) $org->getKey();

        if (! isset($provisioned[$orgId])) {
            app(OrganizationRoleProvisioner::class)->provision($org);
            $provisioned[$orgId] = true;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($org->getKey());

        try {
            $user->assignRole($role);
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }

        return $user->fresh() ?? $user;
    }
}

if (! function_exists('operatorUser')) {
    /**
     * A realistic OPERATOR: an active member of the org holding a real back-office role — i.e. what
     * OwnerInviteService produces for a staff invite. Use this for any test that drives `/app`.
     *
     * @param  array{org: Organization, ...}  $scenario  a seedScenario() result
     * @param  array<string, mixed>  $attributes  extra User attributes (e.g. name/email)
     */
    function operatorUser(array $scenario, array $attributes = [], string $role = 'Hotel Manager'): User
    {
        $user = User::factory()->create(array_merge(['status' => 'active'], $attributes));

        Membership::create([
            'organization_id' => $scenario['org']->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return grantBackOfficeRole($user, $scenario['org'], $role);
    }
}

if (! function_exists('ownerRoleUser')) {
    /**
     * An OWNER-role user, exactly as production builds one: a Membership (the ONLY way their org resolves)
     * and NO back-office role. This is the fixture that proves invariant #26 — the shape that USED to walk
     * straight into the operator back office, because a Membership was mistaken for operator access.
     *
     * It lives in Support/ rather than in a test file because three suites need it (reservations list,
     * guests list, guest profile) and a `function` declared in one Pest file is only loaded when THAT file
     * loads — running a single sibling file in isolation would fatal on an undefined function. Same
     * discipline as the shared app helpers: one definition, so three tests cannot drift about what an
     * "owner" is.
     *
     * The unit is given to the owner (and an agreement created) so the user is a genuine owner of something
     * in this org — a bare Owner row with no unit would be a weaker test than production reality.
     */
    function ownerRoleUser(array $scenario, int $unitIndex = 0): User
    {
        $user = User::factory()->create(['name' => 'Olive Owner', 'status' => 'active']);

        Membership::create([
            'organization_id' => $scenario['org']->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
        $scenario['units'][$unitIndex]->update(['owner_id' => $owner->id]);

        ManagementAgreement::factory()->create([
            'owner_id' => $owner->id,
            'commission_pct' => 15.00,
            'effective_from' => '2026-01-01',
        ]);

        return $user;
    }
}
