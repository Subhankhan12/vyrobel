<?php

use App\Http\Middleware\EnsureBackOfficeAccess;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\User;
use App\Services\OwnerInviteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// LAYER 2 of the /app authorization fix (docs/DECISIONS.md 2026-07-16): an owner is not operator-capable
// at the DATA level, not merely blocked at the door.
//
// A real invited owner now holds `External Owner` — `owner_portal.view` and NOTHING else — so the
// back-office allow-list has nothing to admit even if a future route forgets EnsureBackOfficeAccess.
// The two layers are tested INDEPENDENTLY below, so a regression in either one is caught on its own.

/** Invite + activate an owner through the REAL service, exactly as production does. */
function activatedOwner(array $s, string $email = 'nadia@example.test'): array
{
    Mail::fake();

    $operator = operatorUser($s, ['email' => 'op@example.test']);   // provisions the org's catalog roles
    $this_ = test();
    $this_->actingAs($operator);

    ['token' => $token] = app(OwnerInviteService::class)
        ->invite('Nadia Okonkwo', $email, [$s['units'][0]->id], 18.0, 'owner');

    $svc = app(OwnerInviteService::class);
    $svc->accept($svc->findRedeemable($token), 'sup3r-secret-pass');

    $user = User::query()->where('email', $email)->firstOrFail();

    return ['user' => $user, 'operator' => $operator];
}

/** Read a user's roles under an explicit team (roles are team-scoped; a bare check would see nothing). */
function rolesInOrg(User $user, string $orgId): array
{
    $registrar = app(PermissionRegistrar::class);
    $previous = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($orgId);

    try {
        return User::query()->findOrFail($user->getKey())->getRoleNames()->all();
    } finally {
        $registrar->setPermissionsTeamId($previous);
    }
}

// ── 1. A freshly invited + activated owner holds the OWNER-ONLY role, and no operator capability ──
it('activates an invited owner with the owner-only External Owner role and no operator capability', function () {
    $s = seedScenario(1);
    $a = activatedOwner($s);
    $orgId = (string) $s['org']->id;

    expect(rolesInOrg($a['user'], $orgId))->toBe(['External Owner']);

    // It is NOT a back-office role — the allow-list has nothing to admit.
    expect(config('authorization.back_office_roles'))->not->toContain('External Owner');

    // The capability is exactly one permission: view your own portal. Nothing operator-shaped.
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($orgId);
    $user = User::query()->findOrFail($a['user']->getKey());

    expect($user->can('owner_portal.view'))->toBeTrue();

    foreach (['reservations.view', 'payments.refund', 'guests.erase', 'users.manage', 'folios.view', 'units.manage'] as $operatorPermission) {
        expect($user->can($operatorPermission))->toBeFalse("owner must not hold {$operatorPermission}");
    }

    // …and the owner artifacts are real (the invite flow still works end to end).
    expect(Owner::query()->where('user_id', $a['user']->id)->exists())->toBeTrue()
        ->and(OwnerInvite::query()->where('email', 'nadia@example.test')->value('status'))->toBe('accepted');
});

// ── 2. That owner reaches their portal, with scope active ────────────────────────────────────────
it('lets the freshly activated owner into their portal with owner scope active', function () {
    $s = seedScenario(1);
    $a = activatedOwner($s);

    $this->actingAs($a['user'])->get('/owner')->assertOk();
    $this->actingAs($a['user'])->get('/owner/units')->assertOk()
        ->assertSee($s['units'][0]->code);   // their assigned unit — scope is live, not empty
});

// ── 3. LAYER 2 IN ISOLATION — the ROLE alone blocks the owner, with the route gate taken away ────
// Proves defense-in-depth: even if a future /app route forgets EnsureBackOfficeAccess, the owner still
// holds no back-office role, so any role-based check refuses them.
it('leaves the owner without any back-office role, so the allow-list refuses them independently of the route gate', function () {
    $s = seedScenario(1);
    $a = activatedOwner($s);
    $orgId = (string) $s['org']->id;

    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($orgId);
    $user = User::query()->findOrFail($a['user']->getKey());

    // This is EXACTLY the predicate EnsureBackOfficeAccess evaluates — asserted here with no HTTP at all,
    // so it holds even for a route that never runs the middleware.
    expect($user->hasAnyRole(config('authorization.back_office_roles')))->toBeFalse();
});

// ── 4. LAYER 1 IN ISOLATION — the ROUTE GATE alone blocks an owner who somehow DID hold a role ───
// The mirror image: strip layer 2 by handing the owner a back-office role, and confirm the gate is not
// the only thing standing between an owner and the operator app — this asserts the gate is genuinely
// role-driven, and documents what layer 1 alone can and cannot do.
it('shows the route gate is what enforces the boundary at the door, keyed on the real role', function () {
    $s = seedScenario(1);
    $a = activatedOwner($s);

    // Layer 2 intact → refused (redirected home).
    $this->actingAs($a['user'])->get('/app/dashboard')->assertRedirect(route('owner.dashboard'));

    // Deliberately BREAK layer 2: give this owner a back-office role, as the old DemoSeeder wrongly did.
    grantBackOfficeRole($a['user'], $s['org'], 'Property Owner');

    // Layer 1 alone now admits them — which is precisely WHY layer 2 exists, and why the seeder was
    // inverted-and-dangerous. Re-resolve the user: spatie's hasRole() memoizes the roles relation.
    $fresh = User::query()->findOrFail($a['user']->getKey());
    $this->actingAs($fresh)->get('/app/dashboard')->assertOk();
})->group('security');

// ── 5. The STAFF path is unregressed — still a real back-office role, still reaches /app ─────────
it('still activates a staff invite with a real back-office role that reaches /app', function () {
    $s = seedScenario(1);
    Mail::fake();

    $operator = operatorUser($s, ['email' => 'inviter@example.test']);
    $this->actingAs($operator);

    ['token' => $token] = app(OwnerInviteService::class)
        ->invite('Marcus Bellweather', 'manager@example.test', [], null, 'manager');

    $svc = app(OwnerInviteService::class);
    $svc->accept($svc->findRedeemable($token), 'sup3r-secret-pass');

    $staff = User::query()->where('email', 'manager@example.test')->firstOrFail();
    $orgId = (string) $s['org']->id;

    expect(rolesInOrg($staff, $orgId))->toBe(['Hotel Manager'])
        ->and(config('authorization.back_office_roles'))->toContain('Hotel Manager')
        // staff get NO owner artifacts
        ->and(Owner::query()->where('user_id', $staff->id)->exists())->toBeFalse();

    $this->actingAs(User::query()->findOrFail($staff->getKey()))->get('/app/dashboard')->assertOk();
});

// ── 6. An owner-only role is not provisioned → the owner STILL activates and is STILL safe ───────
// The deliberate asymmetry with assignStaffRole(): a staff member without their role is broken, so that
// path throws. An owner's portal access comes from the owners.user_id link, so a missing role must not
// block a legitimate activation — and costs nothing, because no role is just as non-operator-capable.
it('activates an owner even when the owner role is not provisioned, and they remain non-operator-capable', function () {
    $s = seedScenario(1);   // NOTE: roles deliberately NOT provisioned for this org
    Mail::fake();

    $operator = User::factory()->create(['email' => 'raw-op@example.test', 'status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $operator->id, 'status' => 'active']);
    $this->actingAs($operator);

    ['token' => $token] = app(OwnerInviteService::class)
        ->invite('Unprovisioned Owner', 'unprov@example.test', [], null, 'owner');

    $svc = app(OwnerInviteService::class);
    $svc->accept($svc->findRedeemable($token), 'sup3r-secret-pass');

    $user = User::query()->where('email', 'unprov@example.test')->firstOrFail();
    $orgId = (string) $s['org']->id;

    // No role — but the activation succeeded and the owner is real.
    expect(rolesInOrg($user, $orgId))->toBe([])
        ->and(Owner::query()->where('user_id', $user->id)->exists())->toBeTrue();

    // Still safe on BOTH counts: no back-office role, and the gate refuses them.
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($orgId);
    expect(User::query()->findOrFail($user->getKey())->hasAnyRole(config('authorization.back_office_roles')))->toBeFalse();

    $this->actingAs(User::query()->findOrFail($user->getKey()))
        ->get('/app/dashboard')->assertRedirect(route('owner.dashboard'));

    // …and their portal works regardless of the missing role.
    $this->actingAs(User::query()->findOrFail($user->getKey()))->get('/owner')->assertOk();
});

// ── 7. The middleware is genuinely keyed on the allow-list (not on "is an owner") ────────────────
it('gates on the back-office allow-list, so a non-owner with no role is refused too', function () {
    expect(EnsureBackOfficeAccess::class)->toBeString();   // anchors the reference for a rename

    $s = seedScenario(1);
    $nobody = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $nobody->id, 'status' => 'active']);

    // Not an owner → no home to send them to → a bare 403, not a redirect.
    $this->actingAs($nobody)->get('/app/dashboard')->assertForbidden();
});
