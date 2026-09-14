<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ THE GRANT/REVOKE ASYMMETRY + THE MISSING ASSIGNMENT AUDIT — 2026-08-30 ══════════════════════════
//
// ⚠️ REPRODUCED FIRST: a Property Owner stripped Front Desk from 20 permissions to 2 — accepted, revoked=18,
// no refusal. A real Front Desk user kept `reservations.view` and LOST check-in, check-out, taking a payment,
// the folio, the guest record, availability, invoicing and the rota. The front desk stops functioning.
//
// ⚠️⚠️ AND THIS PASS DOES NOT STOP THAT, DELIBERATELY. The actor held `['*']`, so any subset rule passes them.
// Stripping a role to two permissions is indistinguishable from an admin repurposing it — roles here ship
// with 4 permissions (Housekeeping) to 44 (Group Admin), so no floor is derivable. What the pass adds is the
// bound that IS defensible (never destroy access you could not restore) and the visibility that was missing.

/** ⚠️ Ask the REAL gate inside the spatie TEAM context — outside it, can() answers for no team = false. */
function rbCan(User $user, array $s, string $permission): bool
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user->fresh()->can($permission);
}

function rbPerms(array $s, string $role): array
{
    return Role::query()->where('name', $role)->where('organization_id', $s['org']->id)
        ->first()->permissions->pluck('name')->sort()->values()->all();
}

/** The Group-Admin-with-roles.manage actor: the only shape that can reach the matrix WITHOUT holding everything. */
function rbLimitedAdmin(array $s): User
{
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');

    $withRoles = collect((array) config('authorization.roles')['Group Admin'])->push('roles.manage')->unique()->values()->all();
    app(RolePermissionService::class)->save($owner->fresh(), 'Group Admin', $withRoles);

    return $admin->fresh();
}

// ── 1. ⚠️ THE NEW BOUND — an actor may not revoke a permission they do not hold ─────────────────────
it('refuses to revoke a permission the actor does not hold themselves', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = rbLimitedAdmin($s);
    $accountant = operatorUser($s, ['name' => 'Ann Accountant'], 'Accountant');

    // ⚠️ The bound is INERT on the shipped catalogue — no editable role holds anything a Group Admin lacks.
    //    So build the state that makes it reachable, the way the product allows: a wildcard admin hands an
    //    out-of-remit permission to an editable role.
    $accountantPerms = rbPerms($s, 'Accountant');
    app(RolePermissionService::class)->save($owner->fresh(), 'Accountant', [...$accountantPerms, 'billing.manage']);
    expect(rbCan($accountant, $s, 'billing.manage'))->toBeTrue()
        ->and(rbCan($admin, $s, 'billing.manage'))->toBeFalse();   // the actor does NOT hold it

    // The Group Admin tries to take it away — a permission they could never put back.
    expect(fn () => app(RolePermissionService::class)->save($admin->fresh(), 'Accountant', $accountantPerms))
        ->toThrow(RoleAssignmentException::class, 'You can’t revoke a permission you don’t hold yourself.');

    // ⚠️ AND THE CAPABILITY DID NOT MOVE — the refusal is real, not just a message.
    expect(rbCan($accountant, $s, 'billing.manage'))->toBeTrue();
});

// ── 2. ⚠️⚠️ AND IT DOES NOT BLOCK LEGITIMATE NARROWING — the whole risk of a wrong bound ────────────
// A bound that stopped an admin narrowing a role they own would block real work every day, while the attack
// it aims at needs a hijacked, already-capable session. This pins that it does not.
it('still lets an actor narrow a role using permissions they do hold', function () {
    $s = seedScenario(1);
    $admin = rbLimitedAdmin($s);
    $desk = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    expect(rbCan($admin, $s, 'reports.view'))->toBeTrue()          // the actor holds it…
        ->and(rbCan($desk, $s, 'guests.manage'))->toBeTrue();

    $narrowed = array_values(array_diff(rbPerms($s, 'Front Desk'), ['guests.manage']));
    $result = app(RolePermissionService::class)->save($admin->fresh(), 'Front Desk', $narrowed);

    expect($result['revoked'])->toBe(1)
        ->and(rbCan($desk, $s, 'guests.manage'))->toBeFalse();     // genuinely removed — ordinary administration
});

// ── 3. ⚠️ THE HONEST LIMIT, PINNED — a wildcard actor can still empty a role ────────────────────────
// ⚠️ Written so nobody reads this pass as having closed the erasure. It did not, and could not.
it('still permits a wildcard admin to strip a role to almost nothing — the bound is not a floor', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $desk = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    expect(rbPerms($s, 'Front Desk'))->toHaveCount(20)
        ->and(rbCan($desk, $s, 'reservations.checkin'))->toBeTrue();

    $result = app(RolePermissionService::class)->save($owner->fresh(), 'Front Desk', ['reservations.view', 'units.view']);

    expect($result['revoked'])->toBe(18)
        ->and(rbPerms($s, 'Front Desk'))->toHaveCount(2)
        ->and(rbCan($desk, $s, 'reservations.checkin'))->toBeFalse();   // the front desk stops functioning
});

// ── 4. ⚠️⚠️ THE AUDIT ROW — assignment recorded WHAT changed, not that something did ────────────────
it('records an assignment with the complete role set before and after', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $target = operatorUser($s, ['name' => 'Hank Housekeeper'], 'Housekeeping');

    app(RoleAssignmentService::class)->assign($admin->fresh(), $target->fresh(), 'Front Desk');

    $row = AuditEvent::query()->where('action', 'user.role_assigned')->sole();

    expect($row->subject_id)->toBe((string) $target->getKey())          // WHICH user
        ->and($row->actor_id)->toBe((string) $admin->getKey())          // WHO did it
        ->and($row->actor_type)->toBe('user')
        ->and($row->before['roles'])->toBe(['Housekeeping'])            // the BEFORE state
        ->and($row->after['roles'])->toBe(['Front Desk', 'Housekeeping'])
        ->and($row->after['role'])->toBe('Front Desk');                 // WHICH role was operated on
});

it('records a revocation with the complete role set before and after', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $target = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    app(RoleAssignmentService::class)->revoke($admin->fresh(), $target->fresh(), 'Front Desk');

    $row = AuditEvent::query()->where('action', 'user.role_revoked')->sole();

    expect($row->subject_id)->toBe((string) $target->getKey())
        ->and($row->actor_id)->toBe((string) $admin->getKey())
        ->and($row->before['roles'])->toBe(['Front Desk'])
        ->and($row->after['roles'])->toBe([])                           // ⚠️ the emptied set, recorded
        ->and($row->after['role'])->toBe('Front Desk');
});

// ── 5. A no-op changes nothing and records nothing — the matrix's own rule ──────────────────────────
it('writes no audit row when the assignment changes nothing', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $target = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    app(RoleAssignmentService::class)->assign($admin->fresh(), $target->fresh(), 'Front Desk');   // already holds it

    expect(AuditEvent::query()->where('action', 'user.role_assigned')->count())->toBe(0);
});

// ── 6. ⚠️ THE ROW REACHES THE LEDGER THROUGH THE REAL ROUTE, not only the service ───────────────────
it('records the assignment when it is driven through the real gated route', function () {
    $s = seedScenario(1);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin->changePasswordTo('secret-Passw0rd!');
    $target = operatorUser($s, ['name' => 'Hank Housekeeper'], 'Housekeeping');

    $this->actingAs($admin->fresh())->from(route('app.roles'))
        ->post(route('app.roles.assign'), [
            'current_password' => 'secret-Passw0rd!', 'user_id' => $target->id, 'role' => 'Front Desk',
        ])->assertRedirect(route('app.roles'));

    $row = AuditEvent::query()->where('action', 'user.role_assigned')->sole();
    expect($row->after['roles'])->toBe(['Front Desk', 'Housekeeping'])
        ->and($row->organization_id)->toBe((string) $s['org']->id);   // tenant-stamped
});
