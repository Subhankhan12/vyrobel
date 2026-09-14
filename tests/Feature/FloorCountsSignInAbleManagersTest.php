<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use App\Support\Authorization\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ G1 · THE FLOOR MUST COUNT PEOPLE WHO CAN SIGN IN ═══════════════════════════════════════════════
//
// ⚠️ An organisation whose only role-manager cannot sign in is administratively dead, and it CANNOT be
// revived from inside the product: the password-reset broker's lookup is scoped to the same
// `status = active`, so a deactivated holder cannot even request a reset.
//
// ⚠️ A HOLDER IS NOT AN ADMINISTRATOR. Deactivation is reachable from ordinary operator work — removing an
// owner deactivates that owner's linked user whatever back-office roles it also holds — so a locked-out
// holder can be sitting in an org, counting toward the floor while granting nobody any ability to act.
//
// ⚠️ WHERE THE FLOOR ACTUALLY BITES: on the MATRIX, not on revoke. A revoking actor must themselves hold
// `roles.manage`, and self-management is separately refused, so a revoke always leaves the actor behind and
// the survivor count cannot reach zero. Stripping the permission from a ROLE can empty it. Both paths are
// exercised below so a future reader does not have to re-derive that.

function floorOrg(): array
{
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    $actor = operatorUser($s, ['name' => 'Acting Admin'], 'Group Admin');
    $ghost = operatorUser($s, ['name' => 'Ghost Holder'], 'Front Desk');

    // ⚠️ TWO EDITABLE roles that both grant role-management. `Property Owner` is wildcard-by-design and is
    //    refused by the matrix outright, so it cannot carry this reproduction.
    foreach (['Group Admin', 'Front Desk'] as $r) {
        Role::query()->where('organization_id', $org)->where('name', $r)->firstOrFail()
            ->givePermissionTo(RolePermissions::ROLE_MANAGER_PERMISSION);
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    return ['s' => $s, 'org' => $org, 'actor' => $actor->fresh(), 'ghost' => $ghost->fresh()];
}

it('refuses to strip the last role-manager who can still sign in', function () {
    $c = floorOrg();

    // The fixture bites: before deactivation the ghost really would keep the org administrable.
    $keep = RolePermissions::mapFor($c['org']);
    expect(RolePermissions::roleManagersAfter($keep, 'Group Admin'))->toContain((string) $c['ghost']->id);

    // ⚠️ The ghost still HOLDS the permission — they simply cannot sign in to use it.
    $c['ghost']->update(['status' => 'inactive']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($c['org']);

    expect(RolePermissions::roleManagersAfter(RolePermissions::mapFor($c['org']), 'Group Admin'))
        ->not->toContain((string) $c['ghost']->id);

    $without = array_values(array_filter(
        RolePermissions::mapFor($c['org'])['Group Admin'],
        fn (string $p): bool => $p !== RolePermissions::ROLE_MANAGER_PERMISSION,
    ));

    expect(fn () => app(RolePermissionService::class)->save($c['actor'], 'Group Admin', $without))
        ->toThrow(RoleAssignmentException::class);

    // …and nothing was written on the way out.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect(RolePermissions::mapFor($c['org'])['Group Admin'])
        ->toContain(RolePermissions::ROLE_MANAGER_PERMISSION);
});

// ── ⚠️ THE STRICTER FLOOR MUST NOT BLOCK ORDINARY ADMINISTRATION ────────────────────────────────────
it('still allows stripping a role while a sign-in-able manager remains', function () {
    $c = floorOrg();   // the ghost is active here — an ordinary second administrator

    $without = array_values(array_filter(
        RolePermissions::mapFor($c['org'])['Group Admin'],
        fn (string $p): bool => $p !== RolePermissions::ROLE_MANAGER_PERMISSION,
    ));
    app(RolePermissionService::class)->save($c['actor'], 'Group Admin', $without);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect(RolePermissions::mapFor($c['org'])['Group Admin'])
        ->not->toContain(RolePermissions::ROLE_MANAGER_PERMISSION);
});

it('still allows an ordinary revoke', function () {
    $c = floorOrg();
    $drop = operatorUser($c['s'], ['name' => 'Dropped Admin'], 'Group Admin');

    app(RoleAssignmentService::class)->revoke($c['actor'], $drop, 'Group Admin');

    expect($drop->fresh()->hasRole('Group Admin'))->toBeFalse()
        ->and(User::query()->find($c['actor']->id)->hasRole('Group Admin'))->toBeTrue();
});
