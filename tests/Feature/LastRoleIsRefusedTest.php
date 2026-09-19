<?php

use App\Exceptions\Authorization\RoleAssignmentException;
use App\Services\OwnerTeamInviteService;
use App\Services\RoleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ F-56 — A MEMBER ALWAYS HOLDS AT LEAST ONE ROLE (founder ruling, 2026-09-19).
 *
 * ⚠️ THE STATE, AND WHY IT IS WRONG. {@see RoleAssignmentService::assertNotLastRoleManager()} asks whether
 * the ORGANISATION keeps someone able to manage access. Nothing asked whether the TARGET keeps anything —
 * so an admin could strip a colleague's only role and leave them an active member holding nothing: a person
 * who logs in and is refused `/app` (an allow-list, so no role fails closed) and refused `/owner` too.
 * Membership and role answer different questions — "are they in this organisation" and "what may they do
 * here" — and this was an answer to the first with none to the second.
 *
 * ⚠️⚠️ THE RULING IS THAT REMOVING ACCESS MEANS REMOVING THE MEMBERSHIP, so the refusal SAYS SO. A wall with
 * no door teaches an operator to route around it, and the route around this one is worse than the state it
 * forbids.
 *
 * ⚠️⚠️ AND THE REFUSED PERSON IS STILL REACHABLE ANOTHER WAY — checked, because if F-56 had made a role-less
 * member impossible then F-50's refusal copy would address nobody, and that would have been a finding rather
 * than a fix. 🔬 It does not: {@see OwnerTeamInviteService} creates an ACTIVE membership when
 * an invite is SENT and assigns a role only on ACCEPTANCE, and when the role is not provisioned it logs
 * `owner_team_invite.owner_role_not_provisioned` and returns having assigned nothing. Never-assigned, not
 * stripped, is how that person occurs now — which is what `OwnerRefusalSpeaksToItsReaderTest` was rebuilt on.
 */
function lrTeam(array $s): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/** A wildcard admin who may legitimately manage anybody, plus a target holding exactly the roles named. */
function lrCast(array $s, array $targetRoles): array
{
    $actor = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');

    $target = operatorUser($s, ['name' => 'Tara Target'], $targetRoles[0]);
    lrTeam($s);
    foreach (array_slice($targetRoles, 1) as $extra) {
        $target->assignRole($extra);
    }

    $target = $target->fresh();
    $target->unsetRelation('roles');
    lrTeam($s);

    // ⚠️ THE FIXTURE IS THE FINDING. If the target silently held a different number of roles than intended,
    //    the refusal below would fire (or not) for a reason that has nothing to do with the guard.
    expect($target->getRoleNames()->sort()->values()->all())
        ->toBe(collect($targetRoles)->sort()->values()->all(), 'the target must hold exactly the roles named');

    return ['actor' => $actor->fresh(), 'target' => $target];
}

// ── 1. ⚠️⚠️ THE GUARD ───────────────────────────────────────────────────────────────────────────────
it('⚠️⚠️ F-56 — refuses to remove a member’s LAST role', function () {
    $s = seedScenario(1);
    ['actor' => $actor, 'target' => $target] = lrCast($s, ['Front Desk']);

    expect(fn () => app(RoleAssignmentService::class)->revoke($actor, $target, 'Front Desk'))
        ->toThrow(RoleAssignmentException::class);

    // ⚠️ AND NOTHING WAS WRITTEN — the refusal must fire BEFORE the removal, not be repaired after it.
    lrTeam($s);
    expect($target->fresh()->getRoleNames()->all())->toBe(['Front Desk'], 'the role must survive the refusal');
});

// ── 2. ⚠️⚠️ THE REFUSAL NAMES THE WAY OUT ───────────────────────────────────────────────────────────
it('⚠️⚠️ names the way out, because a wall with no door gets routed around', function () {
    $s = seedScenario(1);
    ['actor' => $actor, 'target' => $target] = lrCast($s, ['Front Desk']);

    try {
        app(RoleAssignmentService::class)->revoke($actor, $target, 'Front Desk');
        $message = null;
    } catch (RoleAssignmentException $e) {
        $message = $e->getMessage();
    }

    expect($message)->not->toBeNull('the revocation must be refused at all');

    // ⚠️ THE REMEDY, NAMED. Not "forbidden" — the operator is told what to do instead.
    expect($message)->toContain('remove them from the organisation');
    expect($message)->toContain('only role');
});

// ── 3. ⚠️⚠️ THE POSITIVE CONTROL — AND IT CAN GENUINELY FAIL ────────────────────────────────────────
/**
 * ⚠️⚠️ A GUARD THAT REFUSED EVERY REVOCATION WOULD PASS CASE 1 PERFECTLY AND BREAK THE FEATURE. This is the
 * discriminator: with a second role in hand the revocation must SUCCEED and actually remove the role.
 */
it('⚠️⚠️ still removes a role when the member keeps another — the control', function () {
    $s = seedScenario(1);
    ['actor' => $actor, 'target' => $target] = lrCast($s, ['Front Desk', 'Accountant']);

    app(RoleAssignmentService::class)->revoke($actor, $target, 'Front Desk');

    lrTeam($s);
    expect($target->fresh()->getRoleNames()->all())->toBe(['Accountant'], 'the surviving role must remain, alone');
});

// ── 4. ⚠️ AND THE ORDER IS LOAD-BEARING ─────────────────────────────────────────────────────────────
/**
 * ⚠️ THE OLDER FLOOR STILL SPEAKS FIRST. A revocation that would lock the workspace out must keep reporting
 * THAT, because it names a different remedy — give someone else access management — and it is the more
 * serious of the two. Pinned so a later reordering has to be deliberate.
 */
it('⚠️ the last-role rule answers where the lock-out floor does not apply', function () {
    $s = seedScenario(1);

    // The ONLY role-manager in the org, holding exactly one role: both guards are true of this revocation.
    $actor = operatorUser($s, ['name' => 'Ada Actor'], 'Property Owner');
    $target = operatorUser($s, ['name' => 'Otto Only'], 'Property Owner');
    lrTeam($s);

    // ⚠️ TWO wildcard holders exist, so the LOCK-OUT floor does not fire — revoking one still leaves a
    //    manager. That isolates the new rule: the target's sole role is the only thing at stake here.
    expect($target->fresh()->getRoleNames()->all())->toBe(['Property Owner']);

    try {
        app(RoleAssignmentService::class)->revoke($actor, $target, 'Property Owner');
        $message = null;
    } catch (RoleAssignmentException $e) {
        $message = $e->getMessage();
    }

    // ⚠️ AND IT RECORDS WHICH GUARD ANSWERED, rather than merely that something refused.
    expect($message)->not->toBeNull();
    expect($message)->toContain('only role');
    expect($message)->not->toContain('last person who can manage access');
});

// ── 5. ⚠️⚠️ ONE REMOVER, AND THE GUARD IS ON IT ─────────────────────────────────────────────────────
/**
 * ⚠️⚠️ "A GUARD ON ONE ENTRANCE AND NOT ANOTHER IS A GUARD THAT READS AS WORKING." This pins the structural
 * fact the guard's completeness rests on, so a SECOND remover cannot appear unnoticed.
 *
 * 🔬 MEASURED FROM VENDOR SOURCE, not from method names: spatie's `assignRole()` is `attach()`-only, while
 * `removeRole()` is `$this->roles()->detach($roles)` and `syncRoles()` is `detach()` then attach. So the
 * removers are `removeRole`, `syncRoles`, a raw relation detach, or a raw delete on `model_has_roles`.
 *
 * ⚠️ The seeder's `syncRoles()` calls are EXEMPT and named as such: they run at build time against freshly
 * created users, assigning a first role rather than removing a last one.
 */
it('⚠️⚠️ has exactly one application-level role remover, and revoke() is it', function () {
    $offenders = [];

    foreach ([base_path('app'), base_path('routes')] as $root) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $path = str_replace(base_path().'/', '', (string) $file->getRealPath());

            // ⚠️ READ WHAT RUNS, NOT WHAT IS WRITTEN ABOUT IT — this file's own siblings discuss these calls
            //    in prose, and a scan that matched its own explanation would report one remover forever.
            $source = vyPermissionStrip((string) file_get_contents((string) $file->getRealPath()), false);

            if (preg_match('/->(removeRole|syncRoles)\s*\(|->roles\(\)\s*->detach\s*\(/', $source)) {
                $offenders[] = $path;
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBe(['app/Services/RoleAssignmentService.php'],
        'a NEW role-removal path appeared in app/ or routes/. The last-role guard lives in '
        .'RoleAssignmentService::revoke() and covers only that one. Guard the new path, or funnel it '
        ."through the service:\n".implode("\n", $offenders));
});
