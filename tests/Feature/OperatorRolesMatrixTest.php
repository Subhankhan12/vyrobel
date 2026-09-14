<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// #102 GATE 1 — the EDITABLE permission matrix genuinely edits real capabilities (spatie DB), gated
// `roles.manage`, with the editable-role / subset / last-admin guards + audit. Mutation-proven end to end.

/** Pin the spatie team to the org + return whether $user resolves $permission (the REAL gate check). */
function userCan(User $user, array $s, string $permission): bool
{
    $registrar = app(PermissionRegistrar::class);
    $prev = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($s['org']->getKey());
    try {
        return $user->fresh()->can($permission);
    } finally {
        $registrar->setPermissionsTeamId($prev);
    }
}

// ── ⚠️ THE PROOF — toggling a permission GENUINELY grants then revokes the capability ───────────────
it('genuinely grants and revokes a real capability when the matrix is saved', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');   // holds roles.manage (['*'])
    $frontDesk = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    // Front Desk does NOT hold reviews.manage out of the box.
    expect(userCan($frontDesk, $s, 'reviews.manage'))->toBeFalse();

    // Grant it via the real matrix save → the Front Desk user's can() flips TRUE (spatie DB, mutation-proven).
    $current = app(RolePermissionService::class);
    $granted = collect((array) config('authorization.roles')['Front Desk'])->push('reviews.manage')->unique()->values()->all();
    $this->actingAs($admin)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Front Desk', 'permissions' => $granted])
        ->assertRedirect(route('app.roles'))->assertSessionHas('roles_notice');
    expect(userCan($frontDesk, $s, 'reviews.manage'))->toBeTrue();

    // Revoke it (save the original set) → the capability is genuinely removed again.
    $original = array_values((array) config('authorization.roles')['Front Desk']);
    $this->actingAs($admin)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Front Desk', 'permissions' => $original])
        ->assertRedirect();
    expect(userCan($frontDesk, $s, 'reviews.manage'))->toBeFalse();
    unset($current);
});

// ── A LOCKED role (wildcard) can't be edited ────────────────────────────────────────────────────────
it('refuses to edit a locked wildcard role', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');

    $this->actingAs($admin)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Property Owner', 'permissions' => ['reservations.view']])
        ->assertRedirect()->assertSessionHas('roles_notice', fn ($m) => str_contains($m, 'can’t be edited'));
});

// ── ⚠️ THE LAST-ADMIN SAFETY — can't remove the org's only role-management access ───────────────────
it('refuses an edit that would leave no one able to manage roles', function () {
    $s = seedScenario(1);
    // A user holds Hotel Manager, and we grant roles.manage to Hotel Manager, with NO Property Owner member.
    $manager = operatorUser($s, ['name' => 'Mara Manager'], 'Hotel Manager');
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($s['org']->getKey());
    Role::where('name', 'Hotel Manager')->where('organization_id', $s['org']->id)->first()->givePermissionTo('roles.manage');

    // Now Hotel Manager is the ONLY role with roles.manage that has a member. Revoking it → blocked.
    $original = array_values((array) config('authorization.roles')['Hotel Manager']);   // no roles.manage
    expect(fn () => app(RolePermissionService::class)->save($manager, 'Hotel Manager', $original))
        ->toThrow(RoleAssignmentException::class);

    // roles.manage is still there — the guard blocked the write.
    expect(userCan($manager, $s, 'roles.manage'))->toBeTrue();
});

// ── The matrix-edit route is gated `roles.manage` — a users.manage-only admin is refused ────────────
it('gates the matrix edit on roles.manage, not the broader users.manage', function () {
    $s = seedScenario(1);
    // Group Admin holds users.manage but NOT roles.manage (only ['*'] roles do).
    $groupAdmin = operatorUser($s, ['name' => 'Gil Group'], 'Group Admin');

    $this->actingAs($groupAdmin)->post(route('app.roles.permissions'), ['role' => 'Front Desk', 'permissions' => []])
        ->assertForbidden();
});

// ── READ-ONLY guard — a paused workspace refuses a matrix save (was a gap on this controller) ───────
it('refuses a matrix save when the workspace is read-only', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Property Owner');
    $s['org']->update(['paused_until' => now()->addDays(5)]);

    $this->actingAs($admin)->post(route('app.roles.permissions'), ['role' => 'Front Desk', 'permissions' => ['reviews.manage']])
        ->assertRedirect()->assertSessionHas('roles_notice', fn ($m) => str_contains($m, 'read-only'));

    expect(userCan(operatorUser($s, [], 'Front Desk'), $s, 'reviews.manage'))->toBeFalse();   // nothing written
});

// ── ⚠️ THE SUBSET RULE — an actor may not grant a permission they do not hold themselves ───────────
// This is the SELF-ESCALATION guard, and it was the one matrix bound with no test. The assign path has an
// equivalent ("refuses to assign a role with MORE access than the actor's own"); the MATRIX path did not,
// even though it is the more dangerous of the two — a granted permission applies to every member of the
// role at once, not to one user. Added when the design-parity pass restyled the matrix control.
//
// ⚠️ The setup has to build the actor through the REAL chain, because no seeded role holds `roles.manage`
// WITHOUT also being a wildcard role that holds everything (Super Admin / Property Owner are `['*']`).
// So: a Property Owner first grants Group Admin `roles.manage` via a genuine matrix save — which is itself
// legal, because the Property Owner does hold it — and the Group Admin, who still lacks `billing.manage`,
// then tries to hand that out.
it('refuses to grant a permission the actor does not hold themselves — no self-escalation', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');    // wildcard — holds everything
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');

    // Group Admin starts WITHOUT roles.manage (verified, not assumed) — give it to them for real.
    expect(userCan($admin, $s, 'roles.manage'))->toBeFalse();
    $withRoles = collect((array) config('authorization.roles')['Group Admin'])->push('roles.manage')->unique()->values()->all();
    $this->actingAs($owner)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Group Admin', 'permissions' => $withRoles])
        ->assertRedirect();
    expect(userCan($admin, $s, 'roles.manage'))->toBeTrue()
        ->and(userCan($admin, $s, 'billing.manage'))->toBeFalse();   // …but still NOT billing.manage

    $target = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');
    expect(userCan($target, $s, 'billing.manage'))->toBeFalse();

    // Now the Group Admin tries to hand out a permission they do NOT hold → refused, with the real message.
    $granted = collect((array) config('authorization.roles')['Front Desk'])->push('billing.manage')->unique()->values()->all();
    $this->actingAs($admin)->post(route('app.roles.permissions'), ['current_password' => 'password', 'role' => 'Front Desk', 'permissions' => $granted])
        ->assertRedirect(route('app.roles'))
        ->assertSessionHas('roles_notice', fn ($m) => str_contains($m, 'grant a permission you don'));

    // ⚠️ AND THE CAPABILITY DID NOT MOVE — the refusal is real, not just a message.
    expect(userCan($target, $s, 'billing.manage'))->toBeFalse()
        ->and(Role::where('name', 'Front Desk')->where('organization_id', $s['org']->getKey())
            ->first()->permissions->pluck('name')->all())->not->toContain('billing.manage');
});
