<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Services\TenantProvisioningService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// PHASE 7a · Step 1 — the ATOMIC tenant-provisioning service. Creating a new operator org is all-or-nothing
// (org + provisioned roles + founder + membership + Property Owner + first property in one transaction); the
// chicken-and-egg is escaped via the pre-tenant super-admin path; the two RBAC defects are fixed.

/** @return array{0: array, 1: array, 2: array} org/founder/property provisioning input. */
function provisionInput(array $org = [], array $founder = [], array $property = []): array
{
    return [
        array_merge(['name' => 'Seaside Group', 'country' => 'PT', 'currency' => 'EUR'], $org),
        array_merge(['name' => 'Ada Founder', 'email' => 'ada@newco.test', 'password' => 'password123'], $founder),
        array_merge(['name' => 'Seaside Villas', 'type' => 'villa', 'location' => 'Faro'], $property),
    ];
}

/** Read the founder's RBAC under the org's spatie team (as a real request would, via the registrar). */
function underTeam(string $orgId, Closure $fn): mixed
{
    $registrar = app(PermissionRegistrar::class);
    $previous = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($orgId);
    $registrar->forgetCachedPermissions();
    try {
        return $fn();
    } finally {
        $registrar->setPermissionsTeamId($previous);
        $registrar->forgetCachedPermissions();
    }
}

// ── 1. ✅ ATOMIC CREATION + WORKING RBAC (defect #1) + founder holds the top role (defect #2) ───────
it('provisions a complete, isolated, RBAC-ready org with the founder as Property Owner', function () {
    [$org, $founder, $property] = provisionInput();
    $result = app(TenantProvisioningService::class)->provision($org, $founder, $property);

    // The org + founder + membership + property all exist and cohere.
    $newOrg = $result['organization'];
    expect((string) $newOrg->status)->toBe('active')
        ->and($newOrg->slug)->toBe('seaside-group')
        ->and((string) $result['user']->status)->toBe('active')
        ->and($result['user']->email)->toBe('ada@newco.test')
        ->and(Hash::check('password123', $result['user']->password))->toBeTrue()   // hashed, not plaintext
        ->and(Membership::query()->where('organization_id', $newOrg->id)->where('user_id', $result['user']->id)->where('status', 'active')->exists())->toBeTrue();

    // The first property belongs to the new org.
    expect((string) $result['property']->organization_id)->toBe((string) $newOrg->id);

    // 🔒 DEFECT #1 FIXED — the org has its provisioned role catalog (Role rows on the org's team).
    expect(Role::query()->where('organization_id', $newOrg->id)->count())->toBeGreaterThanOrEqual(13);

    // 🔒 DEFECT #2 FIXED — the founder legitimately holds the ASSIGNABLE org-admin role (never Super Admin),
    // giving them a genuinely working RBAC state (properties.manage + users.manage, etc.).
    underTeam($newOrg->id, function () use ($result) {
        $user = $result['user']->fresh();
        expect($user->hasRole('Property Owner'))->toBeTrue()
            ->and($user->hasRole('Super Admin'))->toBeFalse()               // NOT the global platform role
            ->and($user->hasPermissionTo('properties.manage'))->toBeTrue()
            ->and($user->hasPermissionTo('users.manage'))->toBeTrue()
            ->and($user->hasPermissionTo('payments.refund'))->toBeTrue();   // '*' within the org
    });
});

// ── 2. 🔒 ATOMIC ROLLBACK — a mid-provision failure rolls back the ENTIRE org (no orphans) ──────────
it('rolls back everything when a step fails mid-provision', function () {
    $orgsBefore = Organization::query()->count();

    // An invalid property type makes the FINAL step (Property::create) throw — after the org, roles, founder,
    // membership and role assignment have all been written inside the transaction.
    [$org, $founder, $property] = provisionInput(property: ['type' => 'not_a_real_type']);

    expect(fn () => app(TenantProvisioningService::class)->provision($org, $founder, $property))
        ->toThrow(QueryException::class);

    // Nothing survived: no org, no founder user, no membership — the whole tenant rolled back.
    expect(Organization::query()->count())->toBe($orgsBefore)
        ->and(Organization::query()->where('slug', 'seaside-group')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'ada@newco.test')->exists())->toBeFalse()
        ->and(Membership::query()->whereHas('user', fn ($q) => $q->where('email', 'ada@newco.test'))->exists())->toBeFalse();
});

// ── 3. 🔒 TENANT ISOLATION — two provisioned orgs are isolated both ways; a founder belongs to one ──
it('isolates a provisioned org from every other org, both directions', function () {
    $a = app(TenantProvisioningService::class)->provision(...provisionInput(
        ['name' => 'Alpha Group'], ['email' => 'a@alpha.test'], ['name' => 'Alpha Villas']));
    $b = app(TenantProvisioningService::class)->provision(...provisionInput(
        ['name' => 'Bravo Group'], ['email' => 'b@bravo.test'], ['name' => 'Bravo Lofts']));

    $tenant = app(TenantContext::class);

    // Under org A's scope: A's property is visible, B's is NOT.
    $tenant->forOrganization((string) $a['organization']->id, function () use ($a, $b) {
        expect(Property::query()->whereKey($a['property']->id)->exists())->toBeTrue()
            ->and(Property::query()->whereKey($b['property']->id)->exists())->toBeFalse();
    });
    // …and symmetrically under B's scope (a one-way test would pass a scope that returned everything).
    $tenant->forOrganization((string) $b['organization']->id, function () use ($a, $b) {
        expect(Property::query()->whereKey($b['property']->id)->exists())->toBeTrue()
            ->and(Property::query()->whereKey($a['property']->id)->exists())->toBeFalse();
    });

    // The founder belongs ONLY to their own org.
    expect(Membership::query()->where('user_id', $a['user']->id)->pluck('organization_id')->all())
        ->toBe([(string) $a['organization']->id]);
});

// ── 4. ✅ EXISTING TENANTS UNREGRESSED — provisioning a new org never touches an existing one ───────
it('leaves an existing tenant + its data untouched', function () {
    $existing = seedScenario(1);   // a pre-existing org with a property + a booking-ready unit
    $existingPropId = (string) $existing['property']->id;
    $existingCount = Property::query()->withoutGlobalScopes()->count();

    app(TenantProvisioningService::class)->provision(...provisionInput());

    // The existing org's property still exists and still belongs to the existing org — nothing rewritten.
    app(TenantContext::class)->forOrganization((string) $existing['org']->id, function () use ($existingPropId) {
        expect(Property::query()->whereKey($existingPropId)->exists())->toBeTrue();
    });
    // Exactly one property was added (the new org's first property).
    expect(Property::query()->withoutGlobalScopes()->count())->toBe($existingCount + 1);
});
