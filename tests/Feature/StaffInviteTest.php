<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Mail\OwnerInviteMail;
use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerInvite;
use App\Models\Unit;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// Auth Phase B · Step 2 — STAFF invites (Hotel Manager / Accountant) reuse the owner-invite machinery
// (hashed/single-use/expiring tokens, no enumeration, tenant isolation) but branch: NO owner record,
// NO agreement, NO units — a Membership + a REAL spatie role, landing in /app (not /owner).

/**
 * An active org member with NO owner link, holding a real back-office role — the inviting operator.
 *
 * /app is an allow-list gate (EnsureBackOfficeAccess), so a Membership alone is not operator access.
 * `Property Owner` is the ORG ADMIN (see config/authorization.php) — i.e. exactly who invites staff.
 *
 * Deliberately NOT grantBackOfficeRole(): that helper provisions the org's WHOLE role catalog, which
 * would silently satisfy the fail-safe test below (it asserts the staff roles are NOT provisioned).
 * Creating just this one role opens the gate and leaves `Hotel Manager`/`Accountant` unprovisioned.
 */
function staffOperator(array $scenario, string $email): User
{
    $user = User::factory()->create(['email' => $email, 'status' => 'active']);
    Membership::create(['organization_id' => $scenario['org']->id, 'user_id' => $user->id, 'status' => 'active']);

    $registrar = app(PermissionRegistrar::class);
    $previousTeam = $registrar->getPermissionsTeamId();
    $registrar->setPermissionsTeamId($scenario['org']->id);   // roles are team-scoped (team = org)

    try {
        $role = Role::findOrCreate('Property Owner', (string) config('authorization.guard'));

        // ⚠️ A TARGETED GRANT, NOT A ROLE SWAP — and the distinction is this file's whole point.
        // The invite route is now gated on `can:users.manage` (invariant #30). `Property Owner` holds `['*']`
        // in the CATALOG, but this fixture creates the role bare, via Role::findOrCreate, DELIBERATELY: it
        // must NOT call the provisioner, because the fail-safe test below asserts the staff roles are
        // *unprovisioned*. So the role exists with ZERO permissions attached — name-correct, permission-empty
        // — and `can('users.manage')` would fail on the very role that is supposed to hold everything.
        // Granting exactly the one permission the gate needs opens the door without provisioning the catalog,
        // preserving what this file exists to prove.
        Permission::findOrCreate('users.manage', (string) config('authorization.guard'));
        $role->givePermissionTo('users.manage');

        $user->assignRole('Property Owner');
    } finally {
        $registrar->setPermissionsTeamId($previousTeam);
    }

    return $user->fresh() ?? $user;
}

/** Provision the org's spatie catalog roles (as org-creation / the demo seeder do) so staff roles exist. */
function provisionOrgRoles(array $scenario): void
{
    app(OrganizationRoleProvisioner::class)->provision($scenario['org']);
}

/** Capture the tokened link from the dispatched invite mail. */
function capturedInviteToken(): string
{
    $token = null;
    Mail::assertSent(OwnerInviteMail::class, function (OwnerInviteMail $m) use (&$token): bool {
        $token = $m->token;

        return true;
    });

    return (string) $token;
}

// ── MANAGER: invite → activate as staff with the Hotel Manager role in /app, no owner artifacts ──────
it('invites a manager and activates them as Hotel Manager staff in /app — no owner record/agreement/units', function () {
    Mail::fake();
    $s = seedScenario(1);
    provisionOrgRoles($s);
    $op = staffOperator($s, 'op-mgr@vyrobel.test');

    // Invite via the REAL operator action; deliberately include owner-only fields to prove they're dropped.
    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Morgan Manager',
        'email' => 'morgan@example.test',
        'role' => 'manager',
        'unit_ids' => [$s['units'][0]->id],
        'commission_pct' => '15',
    ])->assertRedirect(route('app.owners'))->assertSessionHas('owners_notice');

    $token = capturedInviteToken();

    $user = User::query()->where('email', 'morgan@example.test')->firstOrFail();
    expect($user->status)->toBe('invited');

    // Invite is a STAFF invite: no owner, owner-only fields dropped, tenant-scoped, token hashed.
    app(TenantContext::class)->set($s['org']->id);
    $invite = OwnerInvite::query()->where('email', 'morgan@example.test')->firstOrFail();
    expect($invite->owner_id)->toBeNull()
        ->and($invite->role)->toBe('manager')
        ->and($invite->assigned_unit_ids)->toBeNull()
        ->and($invite->commission_pct)->toBeNull()
        ->and($invite->organization_id)->toBe($s['org']->id)
        ->and($invite->token_hash)->toBe(hash('sha256', $token))
        ->and(Owner::query()->where('user_id', $user->id)->exists())->toBeFalse();

    // Can't authenticate before activation (active-only login).
    $this->post('/logout');
    $this->post('/login', ['email' => 'morgan@example.test', 'password' => 'staff-strong-pass'])->assertSessionHasErrors();
    $this->assertGuest();

    // Activate via the tokened link → lands in the operator app (NOT /owner).
    $this->get("/invite/{$token}")->assertOk()->assertSee('Set your password')->assertSee('Morgan Manager');
    $this->post("/invite/{$token}", ['password' => 'staff-strong-pass', 'password_confirmation' => 'staff-strong-pass'])
        ->assertRedirect('/app/dashboard');

    $user->refresh();
    $this->assertAuthenticatedAs($user);
    expect($user->status)->toBe('active')
        ->and(Owner::query()->where('user_id', $user->id)->exists())->toBeFalse()      // no owner record
        ->and(ManagementAgreement::query()->count())->toBe(0)                          // no agreement
        ->and(Unit::query()->find($s['units'][0]->id)->owner_id)->toBeNull()           // unit not assigned
        ->and(OwnerInvite::query()->find($invite->id)->status)->toBe('accepted');      // token spent

    // The REAL Hotel Manager spatie role is assigned (team-scoped).
    app(PermissionRegistrar::class)->setPermissionsTeamId($s['org']->id);
    expect($user->fresh()->hasRole('Hotel Manager'))->toBeTrue()
        ->and($user->fresh()->hasRole('Accountant'))->toBeFalse();
});

// ── ACCOUNTANT: same machinery, the Accountant role ──────────────────────────────────────────────
it('invites an accountant and activates them as the Accountant role in /app', function () {
    Mail::fake();
    $s = seedScenario(1);
    provisionOrgRoles($s);
    $op = staffOperator($s, 'op-acc@vyrobel.test');

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Avery Accountant',
        'email' => 'avery@example.test',
        'role' => 'accountant',
    ])->assertRedirect(route('app.owners'))->assertSessionHas('owners_notice');

    $token = capturedInviteToken();
    $this->post('/logout');

    $this->post("/invite/{$token}", ['password' => 'books-strong-pass', 'password_confirmation' => 'books-strong-pass'])
        ->assertRedirect('/app/dashboard');

    $user = User::query()->where('email', 'avery@example.test')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->status)->toBe('active');

    app(TenantContext::class)->set($s['org']->id);
    expect(Owner::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(ManagementAgreement::query()->count())->toBe(0);

    app(PermissionRegistrar::class)->setPermissionsTeamId($s['org']->id);
    expect($user->fresh()->hasRole('Accountant'))->toBeTrue()
        ->and($user->fresh()->hasRole('Hotel Manager'))->toBeFalse();
});

// ── OWNER path UNCHANGED — owner record + units + agreement, /owner, and NO staff spatie role ─────
it('leaves the owner path unchanged: owner record + agreement + units, /owner, no staff role', function () {
    Mail::fake();
    $s = seedScenario(1);
    provisionOrgRoles($s);
    $op = staffOperator($s, 'op-own@vyrobel.test');

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Olivia Owner',
        'email' => 'olivia@example.test',
        'role' => 'owner',
        'unit_ids' => [$s['units'][0]->id],
        'commission_pct' => '18',
    ])->assertRedirect(route('app.owners'));

    $token = capturedInviteToken();
    $this->post('/logout');

    $this->post("/invite/{$token}", ['password' => 'owner-strong-pass', 'password_confirmation' => 'owner-strong-pass'])
        ->assertRedirect('/owner');   // owner → the portal

    $user = User::query()->where('email', 'olivia@example.test')->firstOrFail();
    app(TenantContext::class)->set($s['org']->id);
    $owner = Owner::query()->where('user_id', $user->id)->firstOrFail();
    expect($owner->status)->toBe('active')
        ->and((string) Unit::query()->find($s['units'][0]->id)->owner_id)->toBe((string) $owner->id)
        ->and(ManagementAgreement::query()->where('owner_id', $owner->id)->where('status', 'active')->exists())->toBeTrue();

    // An owner gets NO operator staff role (portal access comes from the owner link, not spatie).
    app(PermissionRegistrar::class)->setPermissionsTeamId($s['org']->id);
    expect($user->fresh()->hasRole('Hotel Manager'))->toBeFalse()
        ->and($user->fresh()->hasRole('Accountant'))->toBeFalse();
});

// ── Role-key CORRECTNESS: the slugs map to the EXACT catalog roles, not invented strings ─────────
it('maps staff slugs to the exact RBAC catalog role names', function () {
    $roles = (array) config('authorization.roles');
    // The real catalog defines "Hotel Manager" + "Accountant" — there is NO bare "Manager" role.
    expect($roles)->toHaveKeys(['Hotel Manager', 'Accountant'])
        ->and(array_key_exists('Manager', $roles))->toBeFalse();
});

// ── FAIL-SAFE: a staff role not provisioned for the org errors cleanly (no invite, no partial writes)
it('fails safe when the staff role is not provisioned for the org — clean error, nothing written', function () {
    $s = seedScenario(1);   // roles deliberately NOT provisioned
    $op = staffOperator($s, 'op-fs@vyrobel.test');

    $this->actingAs($op)->post('/app/owners/invite', [
        'name' => 'Unprovisioned',
        'email' => 'unprov@example.test',
        'role' => 'manager',
    ])->assertStatus(302)->assertSessionHas('owners_error');

    app(TenantContext::class)->set($s['org']->id);
    // resolveStaffRole throws BEFORE any writes → no invite and no orphaned login.
    expect(OwnerInvite::query()->where('email', 'unprov@example.test')->count())->toBe(0)
        ->and(User::query()->where('email', 'unprov@example.test')->exists())->toBeFalse();
});
