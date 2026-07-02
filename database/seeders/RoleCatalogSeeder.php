<?php

namespace Database\Seeders;

use App\Actions\Authorization\OrganizationRoleProvisioner;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Installs the global RBAC catalog (convention: team = organization):
 *   - the full global permission set (no team), and
 *   - the platform-level `Super Admin` role (team_id = null) with every permission.
 *
 * The 13 tenant roles are provisioned PER organization by
 * {@see OrganizationRoleProvisioner} (at org creation / demo seed),
 * because team-scoped roles need a concrete organization to belong to.
 */
class RoleCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $guard = config('authorization.guard');
        /** @var list<string> $allPermissions */
        $allPermissions = config('authorization.permissions');
        $superAdminRole = config('authorization.super_admin_role');

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        // Global Super Admin role spans all tenants (team_id = null).
        $previousTeam = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(null);

        try {
            $role = Role::findOrCreate($superAdminRole, $guard);
            $role->syncPermissions($allPermissions);
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
        }

        $registrar->forgetCachedPermissions();
    }
}
