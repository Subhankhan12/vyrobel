<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Models\Organization;
use App\Models\User;
use App\Services\RoleAssignmentService;
use App\Services\RolePermissionService;
use App\Support\Authorization\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

// ══ THE ROLE-RECOVERY PROCEDURE — executable proof of a written runbook step (2026-08-31) ═══════════
//
// ⚠️ Founder ruling: recovery is a SUPPORT JOB, not a feature. So there is no route, command or screen —
// there is a written procedure in docs/GO-LIVE-RUNBOOK.md ("Incident · a role has lost permissions").
//
// ⚠️ THIS TEST IS WHY THE PROCEDURE CAN BE TRUSTED. A runbook nobody executes rots silently: the audit
// payload's shape, the reconstruction arithmetic and the restore call are all things a later change could
// break without any other test noticing. This runs the documented steps verbatim.
//
// ⚠️ IT DELIBERATELY USES THE SAME EXPRESSIONS THE RUNBOOK PRINTS. If you change one, change both.

function rrpStrippedOrg(): array
{
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);
    $owner = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');

    $original = RolePermissions::mapFor($org)['Front Desk'];
    sort($original);

    // THE INCIDENT — the role is stripped to two permissions through the real path.
    app(RolePermissionService::class)->save($owner->fresh(), 'Front Desk', ['reservations.view', 'units.view']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return ['s' => $s, 'org' => $org, 'owner' => $owner->fresh(), 'original' => $original];
}

// ── 1. ⚠️⚠️ THE PROCEDURE, STEP FOR STEP, AS THE RUNBOOK PRINTS IT ─────────────────────────────────
it('recovers a stripped role from the audit ledger, byte for byte', function () {
    $c = rrpStrippedOrg();

    expect(RolePermissions::mapFor($c['org'])['Front Desk'])->toHaveCount(2);   // the incident really happened

    // STEP 1 — find the change.
    $row = DB::table('audit_events')
        ->where('organization_id', $c['org'])
        ->where('action', 'role.permissions_updated')
        ->orderByDesc('created_at')
        ->first(['id', 'created_at', 'actor_id', 'after']);

    expect($row)->not->toBeNull('no audit row — the change predates the ledger and is NOT recoverable this way');

    $payload = json_decode((string) $row->after, true);
    expect($payload['role'])->toBe('Front Desk')
        ->and($payload['revoked'])->toHaveCount(18)
        ->and($payload['granted'])->toBe([]);

    // STEP 2 — reconstruct the previous state:  current ∪ revoked − granted.
    $current = RolePermissions::mapFor($c['org'])[$payload['role']];
    $restore = array_values(array_unique(array_merge(
        array_diff($current, $payload['granted']),
        $payload['revoked'],
    )));

    // STEP 3 — restore THROUGH the certified service, as an actor who holds everything being restored.
    // ⚠️ THE RUNBOOK PRINTS THIS EXACT EXPRESSION for finding the actor — executed here so a command a
    //    tired person copies at 3am is one that has actually run.
    $actor = User::role('Property Owner')->firstOrFail();
    expect((string) $actor->getKey())->toBe((string) $c['owner']->getKey());

    app(RolePermissionService::class)->save($actor, $payload['role'], $restore);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $after = RolePermissions::mapFor($c['org'])['Front Desk'];
    sort($after);
    expect($after)->toBe($c['original']);   // ⚠️ identical to what the role held before the incident
});

// ── 2. ⚠️ THE OTHER SOURCE — shipped defaults, and what it costs ───────────────────────────────────
// ⚠️ The provisioner restores every role to `config/authorization.php`. That recovers a stripped role, and it
// ALSO discards deliberate customisation on every OTHER role — which is why the ledger route comes first.
it('restores shipped defaults through the provisioner, and that is not the same thing', function () {
    $c = rrpStrippedOrg();

    // A deliberate, legitimate customisation on a DIFFERENT role.
    $customised = [...RolePermissions::mapFor($c['org'])['Accountant'], 'reviews.manage'];
    app(RolePermissionService::class)->save($c['owner'], 'Accountant', $customised);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect(RolePermissions::mapFor($c['org'])['Accountant'])->toContain('reviews.manage');

    // ⚠️ Again the runbook's exact expression, not a convenient local variable.
    app(OrganizationRoleProvisioner::class)->provision(
        Organization::withoutGlobalScopes()->findOrFail($c['org']),
    );
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $map = RolePermissions::mapFor($c['org']);
    $frontDesk = $map['Front Desk'];
    sort($frontDesk);

    expect($frontDesk)->toBe($c['original'])                       // the stripped role IS restored…
        ->and($map['Accountant'])->not->toContain('reviews.manage');   // …and the customisation is GONE
});

// ── 3. ⚠️ A ROLE *ASSIGNMENT* IS RECOVERABLE TOO — the rows added 2026-08-30 carry before/after ─────
it('recovers a revoked role assignment from the audit ledger', function () {
    $s = seedScenario(1);
    $org = (string) $s['org']->id;
    app(PermissionRegistrar::class)->setPermissionsTeamId($org);
    $admin = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    $victim = operatorUser($s, ['name' => 'Finn Desk'], 'Front Desk');

    // ⚠️ F-56 (FIX 41): the victim carries a second role, because stripping a member's LAST role is now
    //    refused. What this test is about — that a revoked ASSIGNMENT is recoverable from the ledger's
    //    before/after — is unchanged, and recovering one role out of two exercises the same ledger read.
    $victim->assignRole('Housekeeping');

    app(RoleAssignmentService::class)->revoke($admin->fresh(), $victim->fresh(), 'Front Desk');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    expect($victim->fresh()->getRoleNames()->all())->toBe(['Housekeeping']);

    $row = DB::table('audit_events')->where('organization_id', $org)
        ->where('action', 'user.role_revoked')->orderByDesc('created_at')->first(['subject_id', 'before']);

    $wasHolding = json_decode((string) $row->before, true)['roles'];
    expect($wasHolding)->toBe(['Front Desk', 'Housekeeping']);   // ⚠️ the COMPLETE set, which is the point

    foreach ($wasHolding as $roleName) {
        app(RoleAssignmentService::class)->assign($admin->fresh(), $victim->fresh(), $roleName);
    }
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // ⚠️ Sorted on both sides: recovery restores the SET, and the pivot does not promise an order.
    $restored = $victim->fresh()->getRoleNames()->sort()->values()->all();
    expect($restored)->toBe($wasHolding);
});

// ── 4. ⚠️⚠️ WHAT IT CANNOT RECOVER — stated as a test, not a caveat nobody reads ────────────────────
it('cannot recover a change that was never written to the ledger', function () {
    $c = rrpStrippedOrg();

    DB::table('audit_events')->where('organization_id', $c['org'])
        ->where('action', 'role.permissions_updated')->delete();   // a change from before the ledger existed

    $row = DB::table('audit_events')->where('organization_id', $c['org'])
        ->where('action', 'role.permissions_updated')->first();

    expect($row)->toBeNull();
    // ⚠️ The role stays stripped. Only the provisioner (shipped defaults) can help, and only if defaults
    //    are what is wanted — there is no third source.
    expect(RolePermissions::mapFor($c['org'])['Front Desk'])->toHaveCount(2);
});
