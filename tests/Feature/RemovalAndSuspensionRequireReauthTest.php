<?php

use App\Models\Organization;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ G2 · OWNER REMOVAL · G3 · TENANT SUSPENSION — 2026-08-31 ════════════════════════════════════════
//
// ⚠️ BOTH WERE DELIBERATELY EXCLUDED when the re-auth family closed, and re-examining the exclusion is what
// justified adding them — not the fact that they were asked for:
//
//   · G2's type-the-name confirm is SERVER-SIDE (`OwnerManageController::remove` compares it to the owner's
//     name and refuses), so the brief's "a direct POST walks past it" is FALSE. It genuinely stops a blind
//     POST. ⚠️ But the name is printed on the very page a hijacked session is looking at, so it does NOT stop
//     the threat the password is for. That is the gap the password closes — and the only one.
//   · G3's realm controls are strong and real: `auth:admin`, MANDATORY 2FA, and a platform audit row that
//     DOES record the acting admin, their IP and user-agent. The password is belt-and-braces there, and
//     ⚠️ it protects VYROBEL'S OWN staff session, not a customer's.
//
// ⚠️⚠️ TWO REALMS, TWO DIFFERENT RULES — and getting this wrong is silent. `/app` runs on plain `auth`
// (guard `web`) so the BARE rule is right; `/admin` runs on `auth:admin` so it must be `current_password:admin`.
// A guard-qualified rule pointed at the wrong guard validates NOTHING and looks exactly like one that works —
// which is why every test below proves a WRONG password is REFUSED, not merely that a right one passes.

function g2Actor(): array
{
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Property Owner');
    $admin->changePasswordTo('secret-Passw0rd!');
    $ownerUser = ownerRoleUser($s, 0);

    return ['admin' => $admin->fresh(), 'ownerId' => (string) $s['units'][0]->fresh()->owner_id, 's' => $s, 'ownerUser' => $ownerUser];
}

/**
 * ⚠️ SOFT DELETES: `withoutGlobalScopes()` drops the SoftDeletingScope TOO, so `find()` returns a REMOVED
 * owner exactly as it returns a live one. The first draft of these tests asserted `not->toBeNull()` and would
 * therefore have passed against a removal that actually went through — vacuous in the refusal direction and
 * wrong in the success direction. The state is `trashed()`, so that is what is asserted.
 */
function g2Removed(array $c): bool
{
    return (bool) Owner::withoutGlobalScopes()->findOrFail($c['ownerId'])->trashed();
}

function g2Payload(array $c, array $extra = []): array
{
    return array_merge([
        'confirm_name' => (string) Owner::withoutGlobalScopes()->findOrFail($c['ownerId'])->name,
    ], $extra);
}

// ── G2 · 1. ⛔ NO PASSWORD → REFUSED, and the owner survives ────────────────────────────────────────
it('refuses an owner removal without the current password', function () {
    $c = g2Actor();

    $this->actingAs($c['admin'])->from(route('app.owners.show', $c['ownerId']))
        ->post(route('app.owners.remove', $c['ownerId']), g2Payload($c))
        ->assertSessionHasErrors('current_password');

    expect(g2Removed($c))->toBeFalse()
        ->and($c['ownerUser']->fresh()->status)->toBe('active');
});

// ── G2 · 2. ⚠️⚠️ A WRONG PASSWORD MUST BE REFUSED — the wrong-guard trap ───────────────────────────
// ⚠️ A rule that validates nothing accepts everything, and looks identical to one that works. Only a WRONG
// password proves the rule is bound to a guard that can actually see this user.
it('refuses an owner removal when the password is wrong', function () {
    $c = g2Actor();

    $this->actingAs($c['admin'])->from(route('app.owners.show', $c['ownerId']))
        ->post(route('app.owners.remove', $c['ownerId']), g2Payload($c, ['current_password' => 'not-my-password']))
        ->assertSessionHasErrors('current_password');

    expect(g2Removed($c))->toBeFalse();
});

it('removes the owner with the correct password', function () {
    $c = g2Actor();

    $this->actingAs($c['admin'])->from(route('app.owners.show', $c['ownerId']))
        ->post(route('app.owners.remove', $c['ownerId']), g2Payload($c, ['current_password' => 'secret-Passw0rd!']))
        ->assertRedirect(route('app.owners'));

    expect(g2Removed($c))->toBeTrue()                                          // archived, not destroyed
        // ⚠️ RETARGETED (founder ruling A). This pinned the STRING 'inactive'; owner removal now defers to
        //    the shared {@see \App\Support\Owner\OwnerLoginLifecycle}, which writes 'disabled' — the value
        //    the users migration documents and the owner self-deactivate path already used. The BEHAVIOUR
        //    this line names, "portal access ended", is unchanged: both values are ≠ SIGN_IN_STATUS.
        //    ⚠️ Asserting the RULE rather than one of its spellings is also stronger — it cannot be
        //    falsified by a future vocabulary change that still ends access.
        ->and($c['ownerUser']->fresh()->status)->not->toBe(User::SIGN_IN_STATUS);   // portal access ended
});

// ── G2 · 3. ⚠️ THE NAME CONFIRM IS SERVER-SIDE AND STILL BITES ─────────────────────────────────────
// ⚠️ Pinned because the brief called it "UI-only". It is not, and the password did not replace it.
it('still refuses a removal whose typed name does not match, even with the right password', function () {
    $c = g2Actor();

    $this->actingAs($c['admin'])->from(route('app.owners.show', $c['ownerId']))
        ->post(route('app.owners.remove', $c['ownerId']), [
            'current_password' => 'secret-Passw0rd!', 'confirm_name' => 'Somebody Else',
        ])->assertSessionHas('owners_error');

    expect(g2Removed($c))->toBeFalse();
});

// ── G2 · 4. ⚠️ NO EXISTENCE ORACLE — a foreign owner answers the same either way ────────────────────
it('answers a foreign owner id identically with and without a password', function () {
    $c = g2Actor();
    $other = seedScenario(1);
    $foreign = (string) ownerRoleUser($other, 0)->fresh()->id;
    $foreignOwner = (string) Owner::withoutGlobalScopes()->where('user_id', $foreign)->firstOrFail()->id;

    $without = $this->actingAs($c['admin'])->post(route('app.owners.remove', $foreignOwner), ['confirm_name' => 'x']);
    $with = $this->actingAs($c['admin'])->post(route('app.owners.remove', $foreignOwner),
        ['confirm_name' => 'x', 'current_password' => 'secret-Passw0rd!']);

    expect($without->getStatusCode())->toBe($with->getStatusCode())
        ->and($without->getStatusCode())->toBe(404);   // the tenant scope answers first, either way
});

// ══ G3 · TENANT SUSPENSION — the PLATFORM realm, and a different guard ══════════════════════════════

function g3Setup(): array
{
    $s = seedScenario(1);
    $admin = platformAdmin(['password' => 'admin-Passw0rd!']);

    return ['admin' => $admin, 'orgId' => (string) $s['org']->id];
}

it('refuses a tenant suspension without the current password', function () {
    $c = g3Setup();

    $this->actingAs($c['admin'], 'admin')->from('/admin/customers/'.$c['orgId'])
        ->post(route('admin.customers.suspend', $c['orgId']))
        ->assertSessionHasErrors('current_password');

    expect(Organization::withoutGlobalScopes()->find($c['orgId'])->status)->not->toBe('suspended');
});

// ── ⚠️⚠️ THE GUARD ITSELF, PROVEN — a wrong password on the ADMIN guard is refused ─────────────────
// ⚠️ `current_password:admin` against the wrong guard would find no user and validate NOTHING, accepting this.
it('refuses a tenant suspension when the admin password is wrong', function () {
    $c = g3Setup();

    $this->actingAs($c['admin'], 'admin')->from('/admin/customers/'.$c['orgId'])
        ->post(route('admin.customers.suspend', $c['orgId']), ['current_password' => 'not-the-admin-password'])
        ->assertSessionHasErrors('current_password');

    expect(Organization::withoutGlobalScopes()->find($c['orgId'])->status)->not->toBe('suspended');
});

it('suspends with the correct admin password', function () {
    $c = g3Setup();

    $this->actingAs($c['admin'], 'admin')->from('/admin/customers/'.$c['orgId'])
        ->post(route('admin.customers.suspend', $c['orgId']), ['current_password' => 'admin-Passw0rd!'])
        ->assertRedirect(route('admin.customers.show', $c['orgId']));

    expect(Organization::withoutGlobalScopes()->find($c['orgId'])->status)->toBe('suspended');
});

// ── ⚠️⚠️ THE GUARD QUALIFIER ITSELF — and why the obvious test could not see it ─────────────────────
//
// ⚠️ `actingAs($admin, 'admin')` calls `shouldUse('admin')` (InteractsWithAuthentication::be():51), making
// `admin` the DEFAULT guard for the rest of the test. So a BARE `current_password` resolves to the admin
// guard too, and a mutation dropping `:admin` leaves every other test in this file green. The qualifier is
// invisible to any test that authenticates that way.
//
// ⚠️ This sets the admin on its own guard WITHOUT switching the default, which is what a real /admin request
// looks like: `web` stays a guest, `admin` holds the user.
//
// ⚠️⚠️ AND IT CORRECTS A STANDING CLAIM, TWICE OVER — measured, not reasoned.
//
//   1. "A guard-qualified rule against the wrong guard validates NOTHING, silently" is FALSE. Pointed at a
//      guard holding no user the rule REFUSES EVERYTHING (`validateCurrentPassword()` returns false for a
//      guest) — a broken feature, not a bypass. Pointed at an UNDEFINED guard it THROWS
//      `InvalidArgumentException: Auth guard [owner] is not defined`. Neither failure is silent.
//
//   2. ⚠️ ON THIS ROUTE THE QUALIFIER IS NOT LOAD-BEARING AT ALL, and a mutation dropping it CANNOT be caught
//      — established rather than shrugged at. `Authenticate::authenticate()` calls `$this->auth->shouldUse($guard)`
//      (Authenticate.php:83) the moment `auth:admin` succeeds, so by the time the controller runs the DEFAULT
//      guard already IS `admin`. Bare and qualified are behaviourally identical here. `:admin` is kept because
//      it states the intent and survives a change to the route's middleware stack — not because it changes
//      what happens today.
//
// ⚠️ Where the qualifier WOULD matter is a route with no `auth:` middleware, or one behind a different guard
// than the rule names. That is worth knowing before the next pass repeats the old framing.
it('binds the suspension rule to the ADMIN guard, not merely to whatever is default', function () {
    $c = g3Setup();

    auth()->guard('admin')->setUser($c['admin']);   // ⚠️ NOT actingAs — `web` stays a guest, as in production

    expect(auth()->guard('web')->guest())->toBeTrue()
        ->and(auth()->guard('admin')->guest())->toBeFalse();

    $this->from('/admin/customers/'.$c['orgId'])
        ->post(route('admin.customers.suspend', $c['orgId']), ['current_password' => 'admin-Passw0rd!'])
        ->assertRedirect(route('admin.customers.show', $c['orgId']));

    expect(Organization::withoutGlobalScopes()->find($c['orgId'])->status)->toBe('suspended');
});
