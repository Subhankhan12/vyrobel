<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\User;
use App\Services\RoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// OPERATOR ROLES (Phase 1 · Sub-gate 3) — the SECURITY-FIRST redesign. READ + ASSIGN over the fixed, code-defined
// catalog; the catalog is NEVER edited from the UI (that was the design's escalation weapon). Every mutation is a
// user↔role assignment, gated on users.manage (route) AND bounded by the privilege-subset rule (service): you can
// only assign a role whose access you already hold — never escalate a user beyond your own privilege.

/** A user's role names in the given scenario's org (roles are team-scoped). */
function roleNames(User $user, array $scenario): array
{
    $registrar = app(PermissionRegistrar::class);
    $previous = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($scenario['org']->id);

    try {
        return $user->fresh()->getRoleNames()->all();
    } finally {
        $registrar->setPermissionsTeamId($previous);
    }
}

/** A ROLE's granted permission names in the org (the catalog projection into spatie). */
function rolePerms(string $roleName, array $scenario): array
{
    $registrar = app(PermissionRegistrar::class);
    $previous = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($scenario['org']->id);

    try {
        return Role::findByName($roleName, 'web')->permissions->pluck('name')->sort()->values()->all();
    } finally {
        $registrar->setPermissionsTeamId($previous);
    }
}

// ── 1. VIEW — real roles, read-only permission grants, real members, the refusal note ────────────
it('shows the fixed catalog roles with read-only permissions and members', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Ada Admin'], 'Group Admin');
    operatorUser($s, ['name' => 'Fred Front-Desk'], 'Front Desk');

    $this->actingAs($admin)->get('/app/roles')
        ->assertOk()
        ->assertSee('Front Desk')             // a real catalog role
        ->assertSee('Hotel Manager')
        ->assertSee('Reservations')           // a grouped read-only permission
        ->assertSee('Fred Front-Desk')        // a real member
        ->assertSee('defined in Vyrobel');    // the catalog-is-code, not-editable-here note
});

// ── 2. ASSIGN — an admin may assign a role within their own privilege ────────────────────────────
it('lets an admin assign a role within their own privilege', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Group Admin');
    $member = operatorUser($s, ['name' => 'Mo Member'], 'Front Desk');

    $this->actingAs($admin)->post('/app/roles/assign', ['user_id' => $member->id, 'role' => 'Hotel Manager'])
        ->assertRedirect(route('app.roles'))->assertSessionHas('roles_notice');

    expect(roleNames($member, $s))->toContain('Hotel Manager');
});

// ── 3. ⚠️ users.manage GATE (mutation) — no gate, no screen, no write ────────────────────────────
it('refuses the roles screen and the assign write to a user without users.manage', function () {
    $s = seedScenario(1);
    $frontDesk = operatorUser($s, [], 'Front Desk');   // has NO users.manage
    $victim = operatorUser($s, ['name' => 'Vic Victim'], 'Housekeeping');

    $this->actingAs($frontDesk)->get('/app/roles')->assertForbidden();
    $this->actingAs($frontDesk)->post('/app/roles/assign', ['user_id' => $victim->id, 'role' => 'Accountant'])->assertForbidden();

    expect(roleNames($victim, $s))->not->toContain('Accountant');   // nothing was written
});

// ── 4. ⚠️ NO ESCALATION — the privilege-subset rule (mutation, THE critical guard) ───────────────
it('refuses to assign a role with MORE access than the actor’s own — no escalation', function () {
    $s = seedScenario(1);
    $groupAdmin = operatorUser($s, [], 'Group Admin');            // users.manage, but NOT ['*']
    $member = operatorUser($s, ['name' => 'Mo Member'], 'Front Desk');

    // Property Owner is ['*'] — strictly MORE than Group Admin. The ROUTE admits the admin (users.manage), but
    // the SERVICE refuses the escalation and writes nothing. (Strip the subset check and this would escalate.)
    $this->actingAs($groupAdmin)->post('/app/roles/assign', ['user_id' => $member->id, 'role' => 'Property Owner'])
        ->assertRedirect(route('app.roles'))->assertSessionHas('roles_notice');

    expect(roleNames($member, $s))->not->toContain('Property Owner');

    // The guard itself throws rather than escalate.
    expect(fn () => app(RoleAssignmentService::class)->assign($groupAdmin->fresh(), $member->fresh(), 'Property Owner'))
        ->toThrow(RoleAssignmentException::class);

    // And a Group Admin cannot even SEE Property Owner among its assignable options.
    expect(app(RoleAssignmentService::class)->assignableRolesFor($groupAdmin->fresh()))->not->toContain('Property Owner');
});

// ── 5. NO SELF-MANAGEMENT — an admin can't change their own role (anti-lockout + anti-self-escalation) ──
it('refuses an admin changing their own role', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Group Admin');

    $this->actingAs($admin)->post('/app/roles/assign', ['user_id' => $admin->id, 'role' => 'Hotel Manager'])
        ->assertSessionHas('roles_notice');

    expect(roleNames($admin, $s))->not->toContain('Hotel Manager');
});

// ── 6. TENANT — a role can't be assigned to a user in another organization ────────────────────────
it('cannot assign a role to a user in another organization', function () {
    $a = seedScenario(1);
    $admin = operatorUser($a, [], 'Group Admin');                       // org A
    $b = seedScenario(1);
    $outsider = operatorUser($b, ['name' => 'Ozan Outsider'], 'Front Desk');   // org B

    $this->actingAs($admin)->post('/app/roles/assign', ['user_id' => $outsider->id, 'role' => 'Hotel Manager'])
        ->assertSessionHas('roles_notice');

    expect(roleNames($outsider, $b))->not->toContain('Hotel Manager');   // untouched
});

// ── 7. THE CATALOG IS NOT UI-EDITABLE — assignment never mutates role→permission; no edit route exists ──
it('never mutates the role permission catalog and exposes no catalog-edit route', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, [], 'Group Admin');
    $member = operatorUser($s, ['name' => 'Mo Member'], 'Front Desk');

    $before = rolePerms('Front Desk', $s);
    $this->actingAs($admin)->post('/app/roles/assign', ['user_id' => $member->id, 'role' => 'Hotel Manager']);

    // Front Desk's GRANTS are unchanged — the assign moved a user, it did not touch the catalog.
    expect(rolePerms('Front Desk', $s))->toBe($before);

    // Structurally: the ONLY roles routes are view + assign + revoke — there is NO permission-editing endpoint.
    $roleRoutes = collect(app('router')->getRoutes())
        ->map->getName()
        ->filter(fn ($n): bool => is_string($n) && str_starts_with($n, 'app.roles'))
        ->values()->all();
    expect($roleRoutes)->toEqualCanonicalizing(['app.roles', 'app.roles.assign', 'app.roles.revoke']);
});
