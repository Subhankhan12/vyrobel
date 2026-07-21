<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Tenant provisioning (Phase 7a) — THE single, atomic path that creates a brand-new operator organization
 * from public self-signup: the org + its provisioned RBAC catalog + the founding admin + an active
 * membership + the founder's role + the first property, ALL in one transaction (all-or-nothing).
 *
 * ── THE CHICKEN-AND-EGG, solved (why this can't go through the normal flow) ────────────────────────────
 * A normal write is blocked three ways: (1) {@see BelongsToOrganization} THROWS with no
 * tenant context; (2) the permission gate needs a user holding `organizations.manage` — but no user exists
 * yet; (3) {@see OrganizationRoleProvisioner::provision()} had zero app callers. The escape is a controlled
 * PRE-TENANT path built from existing primitives:
 *   ① {@see TenantContext::runAsSuperAdmin()} creates the Organization OUTSIDE any tenant/permission context
 *      (the explicit super-admin bypass the seeder already uses; `Organization` is the tenant boundary, not a
 *      `BelongsToOrganization` model, so no auto-fill/guard applies);
 *   ② {@see OrganizationRoleProvisioner::provision()} — THIS is its first real app caller — installs the
 *      org's 13 team-scoped roles from `config/authorization.php` (**RBAC DEFECT #1 fixed**: org-creation now
 *      provisions roles, so the new org is not "dead on arrival" under the #26 back-office gate);
 *   ③ {@see TenantContext::forOrganization()} then runs the rest INSIDE the new org: the founder + membership
 *      + the property (whose `organization_id` auto-fills from the now-set context), and — with the spatie
 *      team id pointed at the new org — assigns the founder `Property Owner`, the org's ADMIN role
 *      (`['*']` within the org). **RBAC DEFECT #2 resolved for self-provision**: the founder holds a
 *      legitimate, ASSIGNABLE top role (team-scoped) rather than the global `Super Admin` (a cross-tenant
 *      platform role a self-signup founder must never hold; its global assignability is a separate, deferred
 *      platform concern — see docs/PROJECT-STATE.md).
 *
 * ── ATOMICITY ──────────────────────────────────────────────────────────────────────────────────────────
 * Everything runs inside one {@see DB::transaction}. A failure at ANY step (e.g. the property) rolls the
 * WHOLE thing back — no orphan org, no orphan user, no org-without-roles, no dangling membership. A
 * half-provisioned org is a broken tenant, so provisioning is all-or-nothing by construction.
 */
final class TenantProvisioningService
{
    /** The org-admin role every founder holds — full control WITHIN their own org (never the global Super Admin). */
    public const FOUNDER_ROLE = 'Property Owner';

    public function __construct(
        private TenantContext $tenant,
        private OrganizationRoleProvisioner $provisioner,
        private PermissionRegistrar $registrar,
        private BillingService $billing,
    ) {}

    /**
     * Atomically provision a new tenant. Input is assumed already validated (the signup controller validates
     * + rate-limits + guards enumeration); this service owns only the atomic write.
     *
     * @param  array{name: string, country?: ?string, currency?: string}  $organization
     * @param  array{name: string, email: string, password: string}  $founder
     * @param  array{name: string, type: string, location?: ?string, timezone?: ?string}  $property
     * @return array{organization: Organization, user: User, property: Property}
     */
    public function provision(array $organization, array $founder, array $property): array
    {
        return DB::transaction(function () use ($organization, $founder, $property): array {
            $currency = strtoupper((string) ($organization['currency'] ?? 'EUR'));

            // ① PRE-TENANT — create the org with no tenant/permission context (chicken-and-egg escape).
            $org = $this->tenant->runAsSuperAdmin(fn (): Organization => Organization::create([
                'name' => $organization['name'],
                'slug' => $this->uniqueSlug(Organization::query(), Str::slug($organization['name']) ?: 'org'),
                'mode' => 'single_property',
                'default_currency' => $currency,
                'default_locale' => 'en',
                'country' => $organization['country'] ?? null,
                'status' => 'active',
                'billing_email' => $founder['email'],
            ]));

            // ② RBAC DEFECT #1 FIX — provision the org's role catalog (first real app caller of the provisioner).
            $this->provisioner->provision($org);

            // ③ Inside the new org's context: founder + membership + role + first property.
            return $this->tenant->forOrganization($org->id, function () use ($org, $founder, $property, $currency): array {
                $user = User::create([
                    'name' => $founder['name'],
                    'email' => $founder['email'],
                    'password' => $founder['password'],   // hashed by the 'hashed' cast
                    'status' => 'active',
                    'locale' => 'en',
                ]);

                Membership::create([
                    'organization_id' => $org->id,
                    'user_id' => $user->id,
                    'status' => 'active',
                    'invited_at' => now(),
                ]);

                // RBAC DEFECT #2 (self-provision) FIX — assign the org's ADMIN role on THIS org's spatie team.
                // The founder holds Property Owner legitimately (assignable, team-scoped), never Super Admin.
                $this->withTeam($org->id, fn () => $user->assignRole(self::FOUNDER_ROLE));

                $newProperty = Property::create([
                    'name' => $property['name'],
                    // Property slug is the PUBLIC storefront identifier (/book/{slug}) — unique GLOBALLY, so
                    // the check drops the tenant scope (a fresh org has none of its own yet anyway).
                    'slug' => $this->uniqueSlug(Property::query()->withoutGlobalScopes(), Str::slug($property['name']) ?: 'property'),
                    'type' => $property['type'],
                    'currency' => $currency,
                    'timezone' => $property['timezone'] ?? 'UTC',
                    'address' => isset($property['location']) && $property['location'] !== ''
                        ? ['city' => $property['location']]
                        : null,
                    'status' => 'active',
                ]);

                // Phase 7b — start the org's free trial ATOMICALLY (part of the same transaction, so a trial
                // failure rolls the whole org back). The trial ends `trial_days` after this org's creation.
                $this->billing->startTrialFor($org);

                return ['organization' => $org, 'user' => $user, 'property' => $newProperty];
            });
        });
    }

    /**
     * Run a callback with the spatie permission team pointed at $organizationId, restoring the previous team
     * after — so assignRole targets the new org's team without leaking that team into the rest of the request.
     *
     * @template TReturn
     *
     * @param  \Closure():TReturn  $callback
     * @return TReturn
     */
    private function withTeam(string $organizationId, \Closure $callback): mixed
    {
        $previous = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($organizationId);

        try {
            return $callback();
        } finally {
            $this->registrar->setPermissionsTeamId($previous);
        }
    }

    /**
     * A globally-unique slug on the given (unscoped) query — org + property slugs are storefront/tenant
     * identifiers, so uniqueness is global, disambiguated with a numeric suffix.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private function uniqueSlug(Builder $query, string $base): string
    {
        $slug = $base;
        $n = 1;

        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$n);
        }

        return $slug;
    }
}
