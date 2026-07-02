<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     * Platform Super Admin only (config('authorization.super_admin_role')) — Horizon exposes
     * queue payloads across all tenants, so it is a platform-operator surface, not tenant-facing.
     *
     * The Super Admin role is global (team_id = null, per RoleCatalogSeeder). hasRole() filters
     * by the currently active permissions team id, so it would miss a null-team role while a
     * request-scoped org context is active (SetTenantForWebRequest sets one on every web
     * request). Clear the team scope for this check only, same pattern as
     * OrganizationRoleProvisioner.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if ($user === null) {
                return false;
            }

            $registrar = app(PermissionRegistrar::class);
            $previousTeam = $registrar->getPermissionsTeamId();
            $registrar->setPermissionsTeamId(null);

            try {
                return $user->hasRole(config('authorization.super_admin_role'));
            } finally {
                $registrar->setPermissionsTeamId($previousTeam);
            }
        });
    }
}
