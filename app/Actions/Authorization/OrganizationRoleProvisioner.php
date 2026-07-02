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
 * Called at organization-creation time and by the demo seeder (Step 8).
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
