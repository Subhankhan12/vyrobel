<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerUser;
use App\Models\User;
use App\Services\RolePermissionService;
use App\Support\Authorization\RolePermissions;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ G1 · THE FLOOR COUNTS HOLDERS — AND ONE THING KEEPS THAT SAFE ══════════════════════════════════
//
// ⚠️ `RolePermissions::roleManagersAfter()` counts people who HOLD `roles.manage`, not people who can sign
// in. That is only safe while nothing can disable the last holder's login.
//
// ⚠️⚠️ THE OWNER-REMOVAL CASCADE (shipped 2026-08-31) DISABLES LOGINS. It is held off the last role-manager
// by ONE clause inside `OwnerLoginLifecycle`: `$hasRole` — a login is disabled only when the person holds no
// back-office role. Remove that clause and owner removal becomes a path to an organisation with a role-manager
// who cannot sign in, which is UNRECOVERABLE from inside the product (an inactive user cannot even request a
// password reset — the broker's lookup is scoped to `status = active`).
//
// ⚠️ THIS IS THE REVOKE-BOUND SHAPE: a guard standing up because of another rule's side effect. That pass's
// ruling was to make the dependency EXPLICIT and PIN IT, so relaxing the other rule cannot silently reopen the
// hole. A comment would not have failed. This does.

function lrmOrg(): array
{
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    // ⚠️ Dana is the org's ONLY role-manager — and also sits on an owner's TEAM, which is what brings her
    //    within reach of the removal cascade.
    $dana = operatorUser($s, ['name' => 'Dana Dual'], 'Property Owner');
    $dana->changePasswordTo('secret-Passw0rd!');

    // The acting operator holds users.manage but NOT roles.manage, so nothing about them props the floor up.
    $ada = operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin');
    $ada->changePasswordTo('secret-Passw0rd!');

    $ownerUserAccount = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $org, 'user_id' => $ownerUserAccount->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $ownerUserAccount->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    OwnerUser::create(['owner_id' => $owner->id, 'user_id' => $dana->id,
        'role' => OwnerTeamRole::ACCOUNTANT, 'status' => 'active']);

    return ['s' => $s, 'org' => $org, 'dana' => $dana->fresh(), 'ada' => $ada->fresh(), 'owner' => $owner];
}

function lrmSignInAbleManagers(string $org): int
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    $ids = RolePermissions::roleManagersAfter(RolePermissions::mapFor($org));

    return count(array_filter($ids, fn (string $id): bool => User::query()->find($id)?->status === 'active'));
}

// ── ⚠️⚠️ THE PIN — the cascade must never disable the last role-manager ────────────────────────────
it('leaves the last role-manager able to sign in when their owner is removed', function () {
    $c = lrmOrg();

    expect(lrmSignInAbleManagers($c['org']))->toBe(1);   // the fixture bites: exactly one, and it is Dana

    $this->actingAs($c['ada'])->post(route('app.owners.remove', $c['owner']->id), [
        'current_password' => 'secret-Passw0rd!',
        'confirm_name' => (string) $c['owner']->name,
    ])->assertRedirect();

    // Her team membership goes — that is the cascade working…
    expect(OwnerUser::withTrashed()->where('user_id', $c['dana']->id)->firstOrFail()->trashed())->toBeTrue();

    // …but her LOGIN does not, because she holds a back-office role.
    expect($c['dana']->fresh()->status)->toBe('active')
        ->and(lrmSignInAbleManagers($c['org']))->toBe(1);
});

// ── ⚠️⚠️ AND SHE CAN STILL ACT — a status check alone would have proved half of it ──────────────────
// ⚠️ Last pass, reads looked harmless while a WRITE landed. The inverse applies here: "status = active" does
// not prove she can still manage roles. This drives the real matrix write.
it('leaves the last role-manager able to actually manage roles afterwards', function () {
    $c = lrmOrg();

    $this->actingAs($c['ada'])->post(route('app.owners.remove', $c['owner']->id), [
        'current_password' => 'secret-Passw0rd!',
        'confirm_name' => (string) $c['owner']->name,
    ])->assertRedirect();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($c['org']);

    $before = RolePermissions::mapFor($c['org'])['Front Desk'];
    app(RolePermissionService::class)->save($c['dana']->fresh(), 'Front Desk', [...$before, 'reviews.manage']);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect(RolePermissions::mapFor($c['org'])['Front Desk'])->toContain('reviews.manage');
});

// ── ⚠️ AND THE ORDINARY CASE STILL WORKS — the cascade is not neutered ──────────────────────────────
// ⚠️ A pin that only proved "nobody is ever disabled" would pass against a broken cascade.
it('still disables a team member who holds no back-office role', function () {
    $c = lrmOrg();

    $plain = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $c['org'], 'user_id' => $plain->id, 'status' => 'active']);
    OwnerUser::create(['owner_id' => $c['owner']->id, 'user_id' => $plain->id,
        'role' => OwnerTeamRole::VIEW_ONLY, 'status' => 'active']);

    $this->actingAs($c['ada'])->post(route('app.owners.remove', $c['owner']->id), [
        'current_password' => 'secret-Passw0rd!',
        'confirm_name' => (string) $c['owner']->name,
    ])->assertRedirect();

    expect($plain->fresh()->status)->toBe('disabled')     // the cascade really does disable
        ->and($c['dana']->fresh()->status)->toBe('active');   // …and still spares the role-holder
});
