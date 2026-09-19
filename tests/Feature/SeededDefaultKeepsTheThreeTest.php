<?php

use App\Actions\Authorization\OrganizationRoleProvisioner;
use App\Services\RolePermissionService;
use App\Support\Authorization\RolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * ⚠️⚠️ R-F10 (founder ruling, 2026-09-19) — NO ROLE BELOW PROPERTY OWNER SHIPS HOLDING THE THREE.
 *
 * ⚠️⚠️ THIS ANSWER HAD NEVER BEEN RECORDED ANYWHERE. F-10 was raised as "`billing.manage` /
 * `settings.manage` / `roles.manage` are reachable by nobody below Property Owner", and FIX 32 found that
 * premise half-wrong: the three are ALREADY grantable through the role matrix, so "unreachable" was never
 * true. What FIX 32 could not answer was the question underneath — SHOULD THE SEEDED DEFAULT GRANT THEM? —
 * because it was never put. The register has read ⚠️ PREMISE FAILED ever since, and the ruling that settles
 * it arrived after that pass ended. This file is where it now lives in code.
 *
 * ⚠️⚠️ THE RULING HAS TWO HALVES AND BOTH MUST BE ASSERTED, because pinning only the first would be
 * SATISFIED BY DELETING THE THREE FROM THE CATALOGUE — which would destroy exactly the capability the
 * founder chose to keep, and do it while the test went green. That is the wrong-fix mutation for this pin.
 *
 *   (a) THE SEEDED DEFAULT STAYS TIGHT — no editable back-office role ships holding any of the three.
 *   (b) ALL THREE REMAIN GRANTABLE — an operator opts in through the matrix, which already works.
 *
 * ⚠️ READ FROM THE DATABASE, NOT FROM `config/authorization.php`. The config is the SEED; the DB is the live
 * state, and {@see RolePermissions} exists because a guard that reads the shipped file judges a world that
 * stops existing the moment any role is customised (finding A-1). A freshly provisioned org IS the seeded
 * default, so asserting its DB state answers the question the ruling actually asked.
 */

/** The three the ruling is about. ⚠️ NAMED, because "the three" is not a pin. */
const VYF10_THE_THREE = ['billing.manage', 'roles.manage', 'settings.manage'];

function f10Team(array $s): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId((string) $s['org']->id);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/**
 * ⚠️ THE SEEDED DEFAULT, INSTALLED BY THE THING THAT INSTALLS IT. {@see OrganizationRoleProvisioner} is
 * what org-creation and the demo seeder both run, so provisioning a fresh org here reproduces exactly the
 * state the ruling is about — rather than inferring it from `config/authorization.php`, which is the seed
 * and not the live state (A-1).
 */
function f10SeededOrg(array $s): array
{
    app(OrganizationRoleProvisioner::class)->provision($s['org']);
    f10Team($s);

    return RolePermissions::mapFor((string) $s['org']->id);
}

// ── (a) ⚠️⚠️ THE SEEDED DEFAULT STAYS TIGHT ─────────────────────────────────────────────────────────
it('⚠️⚠️ R-F10(a) — no editable role ships holding billing.manage, roles.manage or settings.manage', function () {
    $s = seedScenario(1);
    $map = f10SeededOrg($s);
    $editable = app(RolePermissionService::class)->editableRoleNames();

    // ⚠️ NON-VACUITY FIRST. If the provisioner had installed nothing, every assertion below would pass on
    //    an empty world — the exact shape that has made a guard look perfect while guarding nothing.
    expect(count($editable))->toBeGreaterThan(5, 'no editable roles were provisioned; this world is empty');

    $offenders = [];
    foreach ($editable as $role) {
        foreach (array_intersect($map[$role] ?? [], VYF10_THE_THREE) as $perm) {
            $offenders[] = "{$role} holds {$perm}";
        }

        // ⚠️ AND THE ROLE IS REALLY POPULATED — so "holds none of the three" is a fact, not an absence
        //    caused by the role holding nothing at all.
        expect($map[$role] ?? [])->not->toBeEmpty("{$role} was provisioned with no permissions");
    }

    sort($offenders);

    expect($offenders)->toBe([],
        'R-F10 ruled the seeded default stays tight: no role below Property Owner ships holding these. '
        ."Grant them through the matrix instead — that is the opt-in the ruling preserved.\n"
        .implode("\n", $offenders));
});

// ── (a) control ⚠️ SOMEBODY DOES HOLD THEM, OR "below Property Owner" would be meaningless ──────────
it('⚠️ and Property Owner DOES hold all three — otherwise the ruling would be about nothing', function () {
    $s = seedScenario(1);
    $map = f10SeededOrg($s);

    foreach (VYF10_THE_THREE as $perm) {
        // ⚠️ NOT `->toContain($perm, $message)`: Pest's toContain is VARIADIC, so the message becomes a
        //    SECOND NEEDLE and the assertion stops meaning what it reads as. This project bans the negated
        //    form outright ({@see NegatedVariadicMatcherGuardTest}); the positive form is just as wrong.
        expect(in_array($perm, $map['Property Owner'] ?? [], true))->toBeTrue(
            "Property Owner no longer holds {$perm} — the seeded default is now tighter than the ruling, "
            .'and the organisation admin cannot administer their own organisation.');
    }
});

// ── (b) ⚠️⚠️ ALL THREE REMAIN GRANTABLE — THE HALF THAT STOPS THE WRONG FIX ─────────────────────────
/**
 * ⚠️⚠️ WITHOUT THIS, DELETING THE THREE FROM THE CATALOGUE WOULD SATISFY THE PIN ABOVE PERFECTLY. The
 * founder kept them precisely so an operator CAN opt in; a tight default and an impossible grant are not
 * the same ruling.
 */
it('⚠️⚠️ R-F10(b) — all three are still OFFERED by the matrix', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    f10Team($s);

    $matrix = app(RolePermissionService::class)->matrix($actor->fresh());
    $offered = array_column($matrix['permissions'], 'key');

    foreach (VYF10_THE_THREE as $perm) {
        expect(in_array($perm, $offered, true))->toBeTrue(
            "{$perm} is no longer offered by the roles matrix. R-F10 kept these grantable — if this was a "
            .'catalogue deletion it moves role_has_permissions and needs its own ruling.');
    }

    // ⚠️ AND A WILDCARD ACTOR MAY ACTUALLY GRANT THEM — being listed is not the same as being grantable,
    //    because the subset rule disables anything the actor does not hold.
    foreach (VYF10_THE_THREE as $perm) {
        expect(in_array($perm, $matrix['lockable'], true))->toBeTrue("a wildcard admin cannot grant {$perm}");
    }
});

// ── (b) ⚠️⚠️ AND GRANTING ONE REALLY TAKES — the strongest form of "grantable" ──────────────────────
/**
 * ⚠️⚠️ OFFERED AND PERMITTED ARE STILL ONLY CLAIMS UNTIL ONE IS ACTUALLY GRANTED AND READ BACK. This drives
 * the real service and re-reads live state, so a matrix that listed the permission and silently dropped it
 * on save would be caught here rather than believed.
 */
it('⚠️⚠️ R-F10(b) — an operator can really opt a role in, through the matrix', function () {
    $s = seedScenario(1);
    $actor = operatorUser($s, ['name' => 'Wanda Wildcard'], 'Property Owner');
    f10Team($s);

    $before = RolePermissions::mapFor((string) $s['org']->id)['Group Admin'] ?? [];
    expect(in_array('billing.manage', $before, true))->toBeFalse('the fixture must start from the tight default');

    app(RolePermissionService::class)->save($actor->fresh(), 'Group Admin', [...$before, 'billing.manage']);

    f10Team($s);
    $after = RolePermissions::mapFor((string) $s['org']->id)['Group Admin'] ?? [];

    expect(in_array('billing.manage', $after, true))->toBeTrue(
        'the grant did not take — the opt-in R-F10 preserved is broken');
});
