<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Models\Membership;
use App\Models\Organization;
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
