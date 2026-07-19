<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Organization;
use Database\Seeders\RoleCatalogSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions the 13 tenant-scoped roles for a single organization (team = organization),
 * syncing each role's permissions from config/authorization.php. Idempotent — safe to call
 * again on an existing org. The global `Super Admin` role is NOT provisioned here (it is a
 * platform role installed once by {@see RoleCatalogSeeder}).
 *
 * ⚠️ RBAC DEFECT #1 — fail-closed, DEFERRED to Phase 7 (docs/DECISIONS.md 2026-07-20). Despite the intent, NO
 * app-level org-creation path calls this today: the ONLY callers are the demo seeder + tests, and there is no
 * `Organization::create` anywhere in `app/`. So an org born outside those — i.e. the future self-signup /
 * tenant-provisioning path — would have ZERO roles, and the `/app` back-office gate (invariant #26) would lock
 * out every user in it: dead on arrival. **When Phase 7 builds tenant provisioning, its org-creation transaction
 * MUST call `provision($org)` atomically (org + first user + roles in one commit).** Fail-closed today (an
 * unprovisioned org is unusable, not insecure), and there is no code path to hook onto yet — so this is a
 * deliberate deferral to the gate that builds org creation, not an open hole. A global `Organization::created`
 * hook was considered and rejected: it would add role-provisioning overhead + shared-permission-table contention
 * to 90 org-creating test files (including the true-parallel no-double-booking suites) to guard a path that does
 * not exist. See also RBAC DEFECT #2 (Super Admin unassignable) in docs/PROJECT-STATE.md — same posture.
 */
class OrganizationRoleProvisioner
{
    public function __construct(
        private PermissionRegistrar $registrar,
    ) {}

    public function provision(Organization $organization): void
    {
        $guard = config('authorization.guard');
        $superAdminRole = config('authorization.super_admin_role');
        /** @var list<string> $allPermissions */
        $allPermissions = config('authorization.permissions');

        // Ensure the global permission catalog exists (permissions are not team-scoped).
        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        $previousTeam = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($organization->getKey());

        try {
            foreach (config('authorization.roles') as $roleName => $permissions) {
                if ($roleName === $superAdminRole) {
                    continue; // global platform role, not per-org
                }

                $role = Role::findOrCreate($roleName, $guard); // team id read from the registrar
                $resolved = $permissions === ['*'] ? $allPermissions : $permissions;
                $role->syncPermissions($resolved);
            }
        } finally {
            $this->registrar->setPermissionsTeamId($previousTeam);
        }
    }
}
