<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ A-1 · THE ESCALATION GUARD MUST READ LIVE STATE — 2026-08-30 ════════════════════════════════════
//
// ⚠️ REPRODUCED BEFORE THE FIX, end to end, INCLUDING THE RETURN LEG:
//   1. a Property Owner grants Hotel Manager `roles.manage` + `billing.manage` (legitimate matrix use);
//   2. a Group Admin holding NEITHER assigns Hotel Manager to a confederate — ACCEPTED, because the subset
//      check compared against config/authorization.php, the file the org was SEEDED from;
//   3. the confederate grants both straight back to Group Admin through the matrix, which reads live state
//      and therefore believes them entitled — ACCEPTED. The actor ends up holding what they never had.
//
// ⚠️ These tests drive the FULL SEQUENCE rather than asserting a return value, because the defect was never
// visible in one call: each step was individually legitimate.

function lsTeam(array $s): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

function lsPerms(array $s, string $role): array
{
    lsTeam($s);

    return Role::query()->where('name', $role)->where('organization_id', $s['org']->id)
        ->first()->permissions->pluck('name')->values()->all();
}

function lsCan(array $s, User $u, string $permission): bool
{
    lsTeam($s);

    return $u->fresh()->can($permission);
}

/** STEP 1 — the legitimate elevation every A-1 scenario starts from. */
function lsElevateHotelManager(array $s, User $owner): void
{
    app(RolePermissionService::class)->save(
        $owner->fresh(), 'Hotel Manager', [...lsPerms($s, 'Hotel Manager'), 'roles.manage', 'billing.manage'],
    );
    lsTeam($s);
}

// ── 1. ⚠️⚠️ THE ROUND TRIP IS CLOSED AT STEP 2 ──────────────────────────────────────────────────────
it('refuses the A-1 assignment once the role has been elevated beyond the actor', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');
    $conf = operatorUser($s, ['name' => 'Carl Confederate'], 'Housekeeping');

    lsElevateHotelManager($s, $owner);

    // The actor holds neither of the two permissions the role now carries.
    expect(lsCan($s, $admin, 'roles.manage'))->toBeFalse()
        ->and(lsCan($s, $admin, 'billing.manage'))->toBeFalse();

    expect(fn () => app(RoleAssignmentService::class)->assign($admin->fresh(), $conf->fresh(), 'Hotel Manager'))
        ->toThrow(RoleAssignmentException::class, 'You can’t assign a role with more access than your own.');

    // ⚠️ AND THE CONFEDERATE GAINED NOTHING — so the return leg has no source to come from.
    expect(lsCan($s, $conf, 'roles.manage'))->toBeFalse()
        ->and(lsCan($s, $conf, 'billing.manage'))->toBeFalse()
        ->and($conf->fresh()->getRoleNames()->all())->toBe(['Housekeeping']);
});

// ── 2. …AND THE SCREEN NO LONGER OFFERS IT ──────────────────────────────────────────────────────────
// ⚠️ The UI feed reads the same definition, so it cannot invite a refusal (nor advertise the escalation).
it('drops an elevated role from the actor’s assignable options', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');

    expect(app(RoleAssignmentService::class)->assignableRolesFor($admin->fresh()))->toContain('Hotel Manager');

    lsElevateHotelManager($s, $owner);

    expect(app(RoleAssignmentService::class)->assignableRolesFor($admin->fresh()))->not->toContain('Hotel Manager');
});

// ── 3. ⚠️ THE GUARD READS REALITY IN BOTH DIRECTIONS, not merely "stricter" ─────────────────────────
// An actor whose OWN role was widened in the DB was previously judged against the shipped file and refused
// a role they genuinely out-ranked. The fix answers about what they actually hold.
it('lets an actor whose own role was widened assign what they now genuinely hold', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $desk = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');
    $target = operatorUser($s, ['name' => 'Hank Housekeeper'], 'Housekeeping');

    expect(app(RoleAssignmentService::class)->assignableRolesFor($desk->fresh()))->not->toContain('Accountant');

    // The wildcard admin widens Front Desk to cover everything Accountant holds, plus users.manage.
    app(RolePermissionService::class)->save($owner->fresh(), 'Front Desk',
        [...lsPerms($s, 'Front Desk'), ...lsPerms($s, 'Accountant'), 'users.manage']);
    lsTeam($s);

    expect(app(RoleAssignmentService::class)->assignableRolesFor($desk->fresh()))->toContain('Accountant');

    app(RoleAssignmentService::class)->assign($desk->fresh(), $target->fresh(), 'Accountant');
    expect($target->fresh()->getRoleNames()->all())->toContain('Accountant');
});

// ── 4. ⚠️ FAIL CLOSED on a role the organisation never had provisioned ──────────────────────────────
// ⚠️ An unprovisioned role holds nothing, and nothing is a subset of everything — without an explicit
// guard, pointing the check at the database would have made such a role assignable by ANYBODY. This is
// the way a source-of-truth fix makes things worse, so it is pinned.
it('refuses a catalog role that is not provisioned for the organisation', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $desk = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');
    $target = operatorUser($s, ['name' => 'Hank Housekeeper'], 'Housekeeping');

    lsTeam($s);
    Role::query()->where('name', 'Accountant')->where('organization_id', $s['org']->id)->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(fn () => app(RoleAssignmentService::class)->assign($owner->fresh(), $target->fresh(), 'Accountant'))
        ->toThrow(RoleAssignmentException::class, 'That role isn’t set up for your organization.');

    expect(app(RoleAssignmentService::class)->assignableRolesFor($desk->fresh()))->not->toContain('Accountant');
});

// ── 5. 🔒 THE THREE PRE-EXISTING REFUSALS STILL BITE — now against LIVE state ────────────────────────
it('still refuses an actor changing their own role, against live state', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');
    lsElevateHotelManager($s, $owner);

    expect(fn () => app(RoleAssignmentService::class)->assign($admin->fresh(), $admin->fresh(), 'Group Admin'))
        ->toThrow(RoleAssignmentException::class, 'You can’t change your own role — ask another administrator.');
});

it('still refuses granting a permission the actor does not hold, against live state', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');

    // Give the Group Admin roles.manage so they can reach the matrix at all — they still lack billing.manage.
    app(RolePermissionService::class)->save($owner->fresh(), 'Group Admin', [...lsPerms($s, 'Group Admin'), 'roles.manage']);
    lsTeam($s);

    expect(fn () => app(RolePermissionService::class)->save($admin->fresh(), 'Front Desk', [...lsPerms($s, 'Front Desk'), 'billing.manage']))
        ->toThrow(RoleAssignmentException::class, 'You can’t grant a permission you don’t hold yourself.');
});

it('still refuses revoking a permission the actor does not hold, against live state', function () {
    $s = seedScenario(1);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $admin = operatorUser($s, ['name' => 'Gia GroupAdmin'], 'Group Admin');

    app(RolePermissionService::class)->save($owner->fresh(), 'Group Admin', [...lsPerms($s, 'Group Admin'), 'roles.manage']);
    app(RolePermissionService::class)->save($owner->fresh(), 'Accountant', [...lsPerms($s, 'Accountant'), 'billing.manage']);
    lsTeam($s);

    $without = array_values(array_diff(lsPerms($s, 'Accountant'), ['billing.manage']));
    expect(fn () => app(RolePermissionService::class)->save($admin->fresh(), 'Accountant', $without))
        ->toThrow(RoleAssignmentException::class, 'You can’t revoke a permission you don’t hold yourself.');
});

// ── 6. ⚠️⚠️ THE SOURCE CANNOT BE RE-INTRODUCED — a STRUCTURAL guard ─────────────────────────────────
//
// ⚠️ WHY THIS IS STRUCTURAL AND NOT BEHAVIOURAL. Every other mutation in this pass is provable by running
// the code. "The shared definition was split back into two readers" is NOT: a second reader that queries
// the same table behaves identically, so no assertion about behaviour can see it. What CAN be seen is the
// re-appearance of the stale SOURCE — which is the defect itself, not the duplication.
//
// ⚠️ IT READS WHAT RUNS, NOT WHAT IS WRITTEN ABOUT IT. Both services now DESCRIBE the config read in their
// docblocks, so a naive grep would match its own needle and fail forever (a shape this project has hit
// three times). The source is tokenised and comments are dropped before scanning.
it('keeps config/authorization.php out of the executable path of both role services', function () {
    $files = [
        'RoleAssignmentService' => app_path('Services/RoleAssignmentService.php'),
        'RolePermissionService' => app_path('Services/RolePermissionService.php'),
    ];

    foreach ($files as $label => $path) {
        $code = '';
        foreach (token_get_all((string) file_get_contents($path)) as $token) {
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;   // ⚠️ a guard must read what RUNS
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        // ⚠️ NOT `->not->toContain($needle, $message)`: toContain is VARIADIC, so the message becomes a
        //    SECOND NEEDLE and the negated assertion is satisfied by its own absence (H-4). This pass wrote
        //    that exact bug here and NegatedVariadicMatcherGuardTest caught it by file and line.
        expect(str_contains($code, 'authorization.roles'))
            ->toBeFalse("{$label} reads the SHIPPED role catalogue in executable code — that is finding A-1");
    }

    // ⚠️ AND THE POSITIVE HALF, so the guard cannot pass because the config key was renamed away. Two
    //    readers must KEEP reading it: provisioning (seeding a new org) and the design-tier question
    //    ("is this role top-tier BY DESIGN?"), which must NOT read live state — reading the database there
    //    would mean narrowing a wildcard role once unlocks it permanently.
    expect(str_contains((string) file_get_contents(app_path('Actions/Authorization/OrganizationRoleProvisioner.php')), 'authorization.roles'))
        ->toBeTrue('provisioning must keep seeding from the shipped catalogue');
    expect(str_contains((string) file_get_contents(app_path('Support/Authorization/RolePermissions.php')), 'authorization.roles'))
        ->toBeTrue('the design-tier wildcard question must keep reading the shipped catalogue');
});
