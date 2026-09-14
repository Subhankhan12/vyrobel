<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\User;
use App\Support\Authorization\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ THE REMOVAL PATH · A PRIVILEGE INVERSION ══════════════════════════════════════════════════════
//
// ⚠️⚠️ THE DEFECT. `OwnerManageController::remove()` deactivated the owner's LINKED USER unconditionally
// (`$model->user?->forceFill(['status' => 'inactive'])`), while deactivating that owner's SUB-USERS through
// the shared {@see \App\Support\Owner\OwnerLoginLifecycle}, which spares anyone holding a back-office role.
// Two answers to one question, ten lines apart in one transaction.
//
// ⚠️ THE CONSEQUENCE: an actor holding `users.manage` but NOT `roles.manage` could permanently strip an
// organisation of role management by removing the owner its sole role-manager is linked to — a LESSER
// permission producing the exact effect the greater one is guarded against.
//
// ⚠️⚠️ AND IT IS UNRECOVERABLE FROM INSIDE THE PRODUCT. Both proven below by RUNNING them, not by citing:
// the password-reset broker's lookup is scoped to `status = active`, and no operator route reactivates a
// back-office login at all.
//
// ⚠️ THE FIX IS NOT A NEW GUARD. The sparing rule already existed one layer over; the removal path simply
// did not use it for the primary owner. Reused, never re-implemented (§N.8f).

/**
 * Dana is the organisation's ONLY role-manager AND the owner's own linked user — the dual shape that
 * brings the sole role-manager within reach of `remove()`.
 */
function ormOrg(): array
{
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    // `Property Owner` is the wildcard role, so Dana holds `roles.manage`.
    $dana = operatorUser($s, ['name' => 'Dana Dual'], 'Property Owner');
    $dana->changePasswordTo('secret-Passw0rd!');

    // ⚠️ Ada holds `users.manage` (enough to reach the route) but NOT `roles.manage`. Nothing about her
    //    props the floor up — that asymmetry IS the finding.
    $ada = operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin');
    $ada->changePasswordTo('secret-Passw0rd!');

    // ⚠️ Dana is the PRIMARY owner's linked user — not a team sub-user. That is the uncovered case:
    //    the cascade path was already pinned; this direct path was not.
    $owner = Owner::factory()->create(['user_id' => $dana->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    return ['s' => $s, 'org' => $org, 'dana' => $dana->fresh(), 'ada' => $ada->fresh(), 'owner' => $owner];
}

/** How many holders of `roles.manage` could actually SIGN IN — the floor's own question. */
function ormSignInAbleManagers(string $org): int
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    return count(RolePermissions::roleManagersAfter(RolePermissions::mapFor($org)));
}

function ormRemove(array $c): TestResponse
{
    return test()->actingAs($c['ada'])->post(route('app.owners.remove', $c['owner']->id), [
        'current_password' => 'secret-Passw0rd!',
        'confirm_name' => (string) $c['owner']->name,
    ]);
}

// ── ⚠️⚠️ THE PIN — removing an owner must never cost the org its last role-manager ────────────────
it('leaves the last role-manager able to sign in when the owner they are linked to is removed', function () {
    $c = ormOrg();

    // ⚠️ THE FIXTURE BITES: exactly one sign-in-able role-manager, and it is Dana. Without this the test
    //    could pass against an org that never had a floor to lose.
    expect(ormSignInAbleManagers($c['org']))->toBe(1);

    ormRemove($c)->assertRedirect();

    // The owner IS removed — this pass does not weaken the removal itself.
    expect(Owner::withTrashed()->findOrFail($c['owner']->id)->trashed())->toBeTrue();
    expect($c['s']['units'][0]->fresh()->owner_id)->toBeNull();

    // ⚠️ …but Dana's LOGIN survives, because she holds a back-office role.
    expect($c['dana']->fresh()->status)->toBe(User::SIGN_IN_STATUS);
    expect(ormSignInAbleManagers($c['org']))->toBe(1);
});

// ── ⚠️⚠️ SPARING THE LOGIN MUST NOT RE-OPEN THE RESIDUAL-ACCESS SHAPE ──────────────────────────────
//
// ⚠️ The pass before this one closed exactly that hole: a removed owner's people could still reach the
// portal and LAND A WRITE. Keeping a login alive is only defensible if OWNER-SCOPED ACTION is still shut.
//
// ⚠️⚠️ WHAT THIS ASSERTS, AND WHAT IT DELIBERATELY DOES NOT. It asserts the WRITE, because that is the half
// that actually landed last time — a read came back an empty shell while a write went through.
// ⚠️ It does NOT assert `GET /owner` is refused. MEASURED while writing this: an operator who was NEVER an
// owner also gets 200 there — a plain `Hotel Manager` does. That is audit **M-10** (`/owner` has no positive
// "is an owner" gate), pre-existing and unrelated to removal. Asserting it here would have been a check
// aimed at the wrong target, and it would fail for a reason this pass did not cause.
it('lets no owner-scoped WRITE through on the spared login', function () {
    $c = ormOrg();

    ormRemove($c)->assertRedirect();

    $dana = $c['dana']->fresh();
    expect($dana->status)->toBe(User::SIGN_IN_STATUS);   // the login lives…

    // …and owner-scoped action does not: the owner is soft-deleted, so no owner context resolves.
    $write = test()->actingAs($dana)->post(route('owner.payouts.preferences'), [
        'payout_frequency' => 'monthly',
        'current_password' => 'secret-Passw0rd!',
    ]);
    expect($write->getStatusCode())->toBe(404);
    expect(DB::table('owner_payout_preferences')->where('owner_id', $c['owner']->id)->count())->toBe(0);
});

// ── ⚠️ THE OTHER HALF — an ordinary removal must STILL end portal access ──────────────────────────
it('still deactivates the login of an owner who holds no back-office role', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);

    $ada = operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin');
    $ada->changePasswordTo('secret-Passw0rd!');

    // A plain owner: a Membership (how their org resolves) and no back-office role.
    $plain = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $org, 'user_id' => $plain->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $plain->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);

    test()->actingAs($ada)->post(route('app.owners.remove', $owner->id), [
        'current_password' => 'secret-Passw0rd!',
        'confirm_name' => (string) $owner->name,
    ])->assertRedirect();

    // ⚠️ Portal access really ends — the removal's promise is kept for the ordinary case.
    expect($plain->fresh()->status)->not->toBe(User::SIGN_IN_STATUS);
});

// ── ⚠️⚠️ THE RECOVERY FAILURES — RUN, not quoted ──────────────────────────────────────────────────
it('confirms a deactivated role-manager cannot recover through the product', function () {
    $c = ormOrg();

    // Force the pre-fix state directly, so this documents the CONSEQUENCE independently of the fix.
    $c['dana']->forceFill(['status' => 'inactive'])->save();

    // ① The floor is empty: nobody left who can both hold `roles.manage` and sign in.
    expect(ormSignInAbleManagers($c['org']))->toBe(0);

    // ② The password-reset broker cannot see her — the lookup is scoped to `status = active`.
    $status = Password::broker()->sendResetLink([
        'email' => $c['dana']->email,
        'status' => User::SIGN_IN_STATUS,
    ]);
    expect($status)->toBe(Password::INVALID_USER);

    // ③ And she cannot sign in — the state the runbook procedure below exists to repair.
    test()->post(route('login'), ['email' => $c['dana']->email, 'password' => 'secret-Passw0rd!'])
        ->assertSessionHasErrors();
});

// ── ⚠️⚠️ THE RUNBOOK RECOVERY, EXECUTED — not quoted ───────────────────────────────────────────────
//
// ⚠️ An organisation ALREADY in this state (or driven there by one of the two paths still open — the
// portal toggle and the owner's own Deactivate) cannot repair itself. Recovery is the console procedure in
// `docs/GO-LIVE-RUNBOOK.md`, "Incident · nobody can manage roles, and no role or assignment changed".
// ⚠️ This runs its three steps verbatim, so a command copied at 3am is one that has actually run.
it('executes the runbook recovery for a locked-out organisation', function () {
    $c = ormOrg();
    $org = $c['org'];

    // Drive the org into the incident state.
    $c['dana']->forceFill(['status' => 'inactive'])->save();

    // ── STEP 1 · confirm this is the failure: nobody able, but a holder exists ──
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);
    $map = RolePermissions::mapFor($org);

    $able = RolePermissions::roleManagersAfter($map);

    $holders = [];
    foreach ($map as $role => $perms) {
        if (in_array(RolePermissions::ROLE_MANAGER_PERMISSION, $perms, true)) {
            foreach (User::role($role)->get() as $u) {
                $holders[(string) $u->id] = (string) $u->status;
            }
        }
    }

    expect($able)->toBe([]);                                   // ⚠️ the incident signature…
    expect($holders)->toHaveKey((string) $c['dana']->id);      // …and a real repair candidate
    expect($holders[(string) $c['dana']->id])->not->toBe(User::SIGN_IN_STATUS);

    // ── STEP 2 · reactivate one holder, through Eloquent, never raw SQL ──
    $u = User::withoutGlobalScopes()->findOrFail((string) $c['dana']->id);
    $u->forceFill(['status' => User::SIGN_IN_STATUS])->save();

    // ── STEP 3 · verify, and the broker can see them again ──
    expect(ormSignInAbleManagers($org))->toBe(1);

    expect(Password::broker()->sendResetLink([
        'email' => $c['dana']->email,
        'status' => User::SIGN_IN_STATUS,
    ]))->toBe(Password::RESET_LINK_SENT);

    // And the repaired account can actually SIGN IN again — which is the whole point of the repair.
    // ⚠️ The landing route is deliberately NOT asserted: this fixture never ran the removal, so Dana still
    //    has a live `Owner` record and is routed to `/owner`. Pinning a destination here would be a check
    //    aimed at the wrong thing — authentication is what Step 2 restores.
    test()->post(route('login'), ['email' => $c['dana']->email, 'password' => 'secret-Passw0rd!'])
        ->assertRedirect();
    expect(auth()->check())->toBeTrue();
});
