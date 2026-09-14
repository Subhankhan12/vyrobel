<?php

use App\Models\OwnerInvite;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ AUDIT FOLLOW-UP 2026-08-30 — RE-AUTH BEFORE CHANGING WHAT A BACK-OFFICE PRINCIPAL CAN DO ═════════
//
// ⚠️ The owner-side fixes protected ONE OWNER'S SLICE. A back-office role reaches the WHOLE organisation.
// FIVE routes changed org-wide access with no password — the brief named four; `settings.team-scope`
// (which properties a user may reach) is the fifth.
//
// ⚠️ REPRODUCED, all five with no password: a role granted and revoked; the Front Desk role stripped from
// 20 permissions to 2 (changing NO user and NO assignment, yet altering what EVERYONE holding it can do);
// an owner/manager invite created; and a user's property scope narrowed.
//
// ⚠️ WHAT THIS DOES AND DOES NOT ACHIEVE. It stops a session that does not know the password. It does NOT
// stop a hijacked session whose holder has it, and it is NOT what prevents escalation — RolePermissionService's
// SUBSET RULE ("you can't grant a permission you don't hold yourself") and RoleAssignmentService's
// NO-SELF-MANAGEMENT rule already do that, and both are proven below so this pass cannot be read as
// having introduced them.
//
// ⚠️ Guard VERIFIED: /app runs on plain `auth` and AUTH_GUARD defaults to `web`, so the BARE
// `current_password` is correct — `current_password:admin` would validate nothing here, silently.

function roleAdmin(): array
{
    $s = seedScenario(1);
    $actor = operatorUser($s, [], 'Property Owner');   // holds users.manage + roles.manage
    $actor->changePasswordTo('secret-Passw0rd!');

    $target = operatorUser($s, [], 'Housekeeping');

    return ['actor' => $actor->fresh(), 'target' => $target, 'scenario' => $s];
}

function targetRoleNames(User $u): array
{
    return $u->fresh()->roles->pluck('name')->sort()->values()->all();
}

/** ⚠️ spatie runs in TEAMS mode here — a role lookup needs the team pinned, or findByName cannot see it. */
function frontDeskRole(array $c): Role
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $c['scenario']['org']->id);

    return Role::findByName('Front Desk');
}

function roleNamed(array $c, string $name): Role
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $c['scenario']['org']->id);

    return Role::findByName($name);
}

// ── 1. ⛔ ASSIGN ─────────────────────────────────────────────────────────────────────────────────────
it('refuses a role assignment without the current password', function () {
    $c = roleAdmin();

    $this->actingAs($c['actor'])->from(route('app.roles'))
        ->post(route('app.roles.assign'), ['user_id' => $c['target']->id, 'role' => 'Front Desk'])
        ->assertSessionHasErrors('current_password');

    expect(targetRoleNames($c['target']))->not->toContain('Front Desk');
});

it('assigns a role with the correct password', function () {
    $c = roleAdmin();

    $this->actingAs($c['actor'])->from(route('app.roles'))
        ->post(route('app.roles.assign'), [
            'current_password' => 'secret-Passw0rd!', 'user_id' => $c['target']->id, 'role' => 'Front Desk',
        ])->assertSessionHasNoErrors();

    expect(targetRoleNames($c['target']))->toContain('Front Desk');
});

// ── 2. ⛔ REVOKE ─────────────────────────────────────────────────────────────────────────────────────
it('refuses a role revocation without the current password', function () {
    $c = roleAdmin();
    $this->actingAs($c['actor'])->post(route('app.roles.assign'), [
        'current_password' => 'secret-Passw0rd!', 'user_id' => $c['target']->id, 'role' => 'Front Desk',
    ]);

    $this->actingAs($c['actor'])->from(route('app.roles'))
        ->post(route('app.roles.revoke'), ['user_id' => $c['target']->id, 'role' => 'Front Desk'])
        ->assertSessionHasErrors('current_password');

    expect(targetRoleNames($c['target']))->toContain('Front Desk');
});

// ── 3. ⛔ PERMISSIONS — the quiet one: no user, no assignment, everyone with the role is affected ────
it('refuses a permission-matrix change without the current password', function () {
    $c = roleAdmin();
    $before = frontDeskRole($c)->permissions->count();
    expect($before)->toBeGreaterThan(1);   // not vacuous: there is something to strip

    $this->actingAs($c['actor'])->from(route('app.roles'))
        ->post(route('app.roles.permissions'), ['role' => 'Front Desk', 'permissions' => ['reservations.view']])
        ->assertSessionHasErrors('current_password');

    expect(frontDeskRole($c)->permissions->count())->toBe($before);
});

it('changes the permission matrix with the correct password', function () {
    $c = roleAdmin();

    $this->actingAs($c['actor'])->from(route('app.roles'))
        ->post(route('app.roles.permissions'), [
            'current_password' => 'secret-Passw0rd!', 'role' => 'Front Desk', 'permissions' => ['reservations.view'],
        ])->assertSessionHasNoErrors();

    expect(frontDeskRole($c)->permissions->count())->toBe(1);
});

// ── 4. ⛔ OWNER / STAFF INVITE — mints a new back-office principal ───────────────────────────────────
it('refuses an owner-or-staff invite without the current password', function () {
    Mail::fake();
    $c = roleAdmin();

    $this->actingAs($c['actor'])->from(route('app.owners'))
        ->post(route('app.owners.invite'), ['name' => 'A', 'email' => 'np@evil.test', 'role' => 'manager'])
        ->assertSessionHasErrors('current_password');

    expect(OwnerInvite::withoutGlobalScopes()->where('email', 'np@evil.test')->count())->toBe(0);
});

// ── 5. ⛔ TEAM SCOPE — which properties a user may reach ─────────────────────────────────────────────
it('refuses a team-scope change without the current password', function () {
    $c = roleAdmin();
    $property = (string) Property::withoutGlobalScopes()->value('id');

    $this->actingAs($c['actor'])->from(route('app.settings'))
        ->post(route('app.settings.team-scope'), [
            'user_id' => $c['target']->id, 'scope' => 'selected', 'properties' => [$property],
        ])->assertSessionHasErrors('current_password');

    expect(DB::table('property_user')->where('user_id', $c['target']->id)->count())->toBe(0);
});

// ── 6. 🔒 THE PRE-EXISTING BOUNDS STILL HOLD — this pass did not introduce them ─────────────────────
// ⚠️ The password is a SECOND FACTOR, not the escalation boundary. These two rules are what actually stop
// an actor widening their own access, and they existed before this pass. Pinned so a later change to the
// re-auth cannot be mistaken for having weakened them.
it('still refuses to grant a permission the actor does not hold, even with the password', function () {
    $c = roleAdmin();
    $actor = $c['actor'];
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $c['scenario']['org']->id);
    $actor->roles()->detach();
    $actor->assignRole('Front Desk');            // a narrow role — cannot grant beyond itself
    $actor->givePermissionTo('roles.manage');    // may reach the screen…

    $this->actingAs($actor->fresh())->from(route('app.roles'))
        ->post(route('app.roles.permissions'), [
            'current_password' => 'secret-Passw0rd!', 'role' => 'Reservation Agent', 'permissions' => ['users.manage'],
        ])->assertSessionHasNoErrors();

    expect(roleNamed($c, 'Reservation Agent')->permissions->pluck('name')->all())->not->toContain('users.manage');
});

it('still refuses an actor changing their own roles, even with the password', function () {
    $c = roleAdmin();

    $this->actingAs($c['actor'])->from(route('app.roles'))
        ->post(route('app.roles.assign'), [
            'current_password' => 'secret-Passw0rd!', 'user_id' => $c['actor']->id, 'role' => 'Front Desk',
        ]);

    expect(targetRoleNames($c['actor']))->not->toContain('Front Desk');
});

// ── 7. 🖥️ THE CONTROL, NOT JUST THE CHECK ───────────────────────────────────────────────────────────
// ⚠️ "Static checks cannot see a rendered page." A `current_password` rule with no field on the screen is
// a control that 100%-reliably bounces the operator — the UI inviting its own refusal. Each of the four
// screens is FETCHED and the input is located INSIDE the form that posts to the gated route, not merely
// somewhere on the page.
//
// ⚠️ The team-scope field is the one that repeats: it renders inside `@foreach ($teamScopes)`, so a fixed
// DOM id would recur down the page and every row's <label for> would focus the FIRST row's box. Proven
// per-row here, because that is exactly the kind of defect a passing controller test cannot see.

/**
 * Every <form> on the page that posts to $action, each cut at its own </form>.
 *
 * ⚠️ A page can carry SEVERAL forms for one route: the team-scope editor renders one per member, and the
 * actor's OWN row deliberately has no password box (self-scope is refused outright). Taking "the first
 * form" would have asserted against that row and read as a missing control.
 *
 * @return list<string>
 */
function formsPosting(string $html, string $action): array
{
    $out = [];
    foreach (array_slice(explode('<form', $html), 1) as $chunk) {
        $body = explode('</form>', $chunk)[0];
        if (str_contains($body, $action)) {
            $out[] = $body;
        }
    }
    expect($out)->not->toBeEmpty("no form posts to {$action}");

    return $out;
}

it('renders the password field inside every gated form', function () {
    $c = roleAdmin();

    $roles = $this->actingAs($c['actor'])->get('/app/roles')->assertOk()->getContent();
    expect(formsPosting($roles, route('app.roles.assign'))[0])->toContain('name="current_password"')
        ->and(formsPosting($roles, route('app.roles.permissions'))[0])->toContain('name="current_password"');

    $owners = $this->actingAs($c['actor'])->get('/app/owners')->assertOk()->getContent();
    expect(formsPosting($owners, route('app.owners.invite'))[0])->toContain('name="current_password"');

    // ⚠️ The scope editor is deliberately hidden with one property (scoping would be meaningless), so the
    //    fixture must have two — otherwise this assertion would pass vacuously against a missing section.
    Property::create(['name' => 'Second House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $settings = $this->actingAs($c['actor'])->get('/app/settings')->assertOk()->getContent();

    $row = array_values(array_filter(
        formsPosting($settings, route('app.settings.team-scope')),
        fn (string $f): bool => str_contains($f, 'value="'.$c['target']->id.'"'),
    ));
    expect($row)->not->toBeEmpty('the target has no scope row to gate')
        ->and($row[0])->toContain('name="current_password"');
});

it('gives every team-scope row its own password field id', function () {
    $c = roleAdmin();
    operatorUser($c['scenario'], ['name' => 'Second Member'], 'Front Desk');   // a second non-self row
    Property::create(['name' => 'Second House', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);

    $html = $this->actingAs($c['actor'])->get('/app/settings')->assertOk()->getContent();

    preg_match_all('/id="(scope_pw_[^"]+)"/', $html, $m);
    expect(count($m[1]))->toBeGreaterThanOrEqual(2)                 // two rows really rendered
        ->and(array_unique($m[1]))->toHaveCount(count($m[1]));      // …and no id repeats
});
