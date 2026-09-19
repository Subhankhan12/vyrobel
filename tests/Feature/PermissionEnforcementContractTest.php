<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * ⚠️⚠️ A PERMISSION THE MATRIX OFFERS THAT NOTHING READS (docs/PHANTOM-PERMISSIONS.md).
 *
 * The roles screen presents a switch per permission. A switch nothing enforces is a FALSE STATEMENT ABOUT
 * ACCESS, made where access is decided. Measured: 51 permissions, 34 enforced, 17 read by nothing.
 *
 * ⚠️ NOTHING WAS GRANTED, LOOSENED OR REMOVED. Removing a permission moves `role_has_permissions` (260,
 * pinned); wiring one takes access away from a role that has it today. Both are STOP-AND-ASK, so this pass
 * MEASURES and GUARDS, and every unread permission is recorded below with the reason it is unread and what
 * ruling it needs. That is the deliverable; the rulings are the founder's.
 */
uses(DatabaseTransactions::class);

// ⚠️ `VY_UNREAD_PERMISSIONS` MOVED to tests/Support/permission_helpers.php (FIX 42), beside the census
//    that computes against it. It is the ONE register, and a `const` in a test file is only defined
//    when THAT file loads — the cross-file fatal FIX 41 removed twice already.

it('⚠️⚠️ every permission the matrix offers is enforced somewhere, or recorded here with its reason', function () {
    $unread = vyUnreadPermissions();
    $unexplained = array_values(array_diff($unread, array_keys(VY_UNREAD_PERMISSIONS)));

    expect($unexplained)->toBe([],
        'A permission the roles matrix offers is read by nothing — no route gate, no policy or service '
        ."check, no view directive. Enforce it, or record it with a reason:\n".implode("\n", $unexplained));
});

it('⚠️ the allow-list does not outlive what it describes', function () {
    // ⚠️ An entry for a permission that IS now enforced would be a stale excuse quietly vouching for
    //    nothing — the same rot the reconciliation's backing strings exist to prevent.
    $stale = array_values(array_intersect(array_keys(VY_UNREAD_PERMISSIONS), vyEnforcedPermissions()));

    expect($stale)->toBe([], 'these permissions are now enforced — remove their allow-list entries: '.implode(', ', $stale));
});

it('⚠️⚠️ the census actually walks — a non-vacuity floor', function () {
    // ⚠️ A broken walk finds nothing unread and reads exactly like perfect enforcement. These pin that
    //    the catalogue was found, that enforcement sites were found, and that the split is real.
    $catalogue = vyPermissionCatalogue();
    $enforced = vyEnforcedPermissions();

    expect(count($catalogue))->toBeGreaterThan(40)
        ->and(count($enforced))->toBeGreaterThan(25)
        ->and(count($enforced))->toBeLessThan(count($catalogue));

    // And a permission known to be enforced really is seen as such.
    $sites = vyPermissionEnforcement();
    expect($sites['roles.manage']['route'] ?? [])->not->toBeEmpty();
});

it('⚠️ the census reads what RUNS, not what is written about it', function () {
    // ⚠️ This file, the helper and the doc all name these permissions in prose. A scanner matching its own
    //    explanation would report perfect enforcement while enforcing nothing.
    $commented = <<<'BLADE'
    {{-- @can('taxes.manage') described in a comment --}}
    <!-- @can('fees.manage') -->
    <script>can('invoices.view')</script>
    <style>/* can:api.manage */</style>
    BLADE;

    expect(trim(vyPermissionStrip($commented, true)))->toBe('');
});

// ══ Acted, not read — both directions on the gate this pass measured ════════════════════════════════

it('⚠️⚠️ ACTED: a Hotel Manager HOLDS taxes.manage and is still REFUSED by the tax route', function () {
    // ⚠️ This is the false statement, reproduced. The matrix grants it; the route does not honour it.
    //    ⚠️ NOTHING WAS GRANTED TO MAKE THIS PASS — the 403 is the finding, not a failure.
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');

    // ⚠️ TEAMS MODE: without the team id, `can()` fails SILENTLY TO FALSE and every assertion below would
    //    "pass" against a permission the user actually holds. That shape has bitten this project twice.
    setPermissionsTeamId($s['org']->id);
    $manager->unsetRelation('roles')->unsetRelation('permissions');

    expect($manager->getRoleNames()->all())->toBe(['Hotel Manager'])
        ->and($manager->can('taxes.manage'))->toBeTrue()
        ->and($manager->can('properties.manage'))->toBeFalse();

    // ⚠️ A WRITE, not a page load — access is never assessed by reading alone.
    $this->actingAs($manager)->post('/app/settings/tax-fees', ['vat_pct' => 10])->assertForbidden();
});

it('⚠️⚠️ ACTED: the role that DOES hold the real gate succeeds — so it is not denying everyone', function () {
    // ⚠️ THE OTHER DIRECTION, AND IT IS NOT OPTIONAL. A gate proven only in the deny direction may be
    //    denying every role, which looks identical to a working restriction and is worse.
    $s = seedScenario(1);
    $owner = operatorUser($s, [], 'Property Owner');

    setPermissionsTeamId($s['org']->id);
    $owner->unsetRelation('roles')->unsetRelation('permissions');

    expect($owner->can('properties.manage'))->toBeTrue();

    $response = $this->actingAs($owner)->post('/app/settings/tax-fees', ['vat_pct' => 10]);

    expect($response->status())->not->toBe(403, 'the permitted role is refused too — the gate denies everyone');
});

it('⚠️ a permission check with NO team set fails silently to false — the shape that has bitten twice', function () {
    $s = seedScenario(1);
    $manager = operatorUser($s, [], 'Hotel Manager');

    setPermissionsTeamId($s['org']->id);
    $manager->unsetRelation('roles')->unsetRelation('permissions');
    expect($manager->can('taxes.manage'))->toBeTrue();

    // ⚠️ Now drop the team. The user is unchanged; the answer flips — and nothing warns.
    setPermissionsTeamId(null);
    $manager->unsetRelation('roles')->unsetRelation('permissions');

    expect($manager->can('taxes.manage'))->toBeFalse(
        'if this ever returns true, the silent-false shape has changed and the warning above is stale');

    setPermissionsTeamId($s['org']->id);
});

// ══ THE PARTITION — FIX 42 ═════════════════════════════════════════════════════════════════════════

/**
 * ⚠️⚠️ ONE STRUCTURE, NOT THREE LISTS (founder ruling, 2026-09-19 — consolidate the census to one source).
 *
 * ⚠️ THERE WERE THREE NUMBERS FOR ONE CONCEPT. This register held seventeen; `KnownLimitsAreStillTrueTest`
 * L6 asserted TWELVE under a comment saying "all eleven"; and `docs/KNOWN-LIMITS.md` said "12 of 46" in its
 * headline and "The 11" in its prose over a table listing twelve. 🔬 Each disagreement is now accounted for
 * and the second census is gone — L6 delegates here.
 *
 * ⚠️⚠️ WHAT THIS ADDS, STATED HONESTLY. `vyEnforcedPermissions()` and `vyUnreadPermissions()` filter ONE map
 * with complementary predicates, so "none in both, none in neither" between THOSE TWO is true by
 * construction and asserting it would prove nothing. The load-bearing half is that the partition is taken
 * against the HAND-WRITTEN register and the CONFIG catalogue, neither of which the census can influence:
 *
 *   · every catalogue permission is enforced, or recorded in `VY_UNREAD_PERMISSIONS` — none in neither;
 *   · no recorded permission is also enforced — none in both;
 *   · and the two sides together are exactly the catalogue, nothing outside it.
 *
 * ⚠️ "ENFORCED" IS DEFINED, NOT ASSUMED — {@see vyPermissionEnforcement()}. A permission counts as enforced
 * when its name appears, IN QUOTES INSIDE A CALL, as one of: a `can:` route-middleware string; a Blade
 * `@can`/`@canany` directive; or a `->can` / `->cannot` / `Gate::allows|denies|authorize|check` /
 * `authorize` / `hasPermissionTo` argument — in `app/`, `routes/` or `resources/views/`, after comments,
 * `<style>`, `<script>` and heredocs are stripped. ⚠️ A NEW ENFORCEMENT STYLE IS NOT COVERED UNTIL IT IS
 * ADDED THERE, and a reader adding one should expect this test to redden until they do.
 */
it('⚠️⚠️ FIX 42 — the catalogue partitions exactly into enforced and registered-as-unread', function () {
    $catalogue = vyPermissionCatalogue();
    $enforced = vyEnforcedPermissions();
    $registered = array_keys(VY_UNREAD_PERMISSIONS);

    sort($catalogue);
    sort($enforced);
    sort($registered);

    // ⚠️ NON-VACUITY FIRST — every assertion below passes trivially on an empty world.
    expect(count($catalogue))->toBeGreaterThan(40, 'the catalogue is empty or unreadable; this proves nothing');
    expect($enforced)->not->toBeEmpty('nothing was found enforced — the census is not walking');

    // ── none in BOTH ────────────────────────────────────────────────────────────────────────────────
    expect(array_values(array_intersect($enforced, $registered)))->toBe([],
        'a permission is recorded as read-by-nothing AND is enforced. One of the two is wrong: if it gained '
        .'a real gate, remove its entry here and from docs/PHANTOM-PERMISSIONS.md.');

    // ── none in NEITHER ─────────────────────────────────────────────────────────────────────────────
    expect(array_values(array_diff($catalogue, $enforced, $registered)))->toBe([],
        'a permission the roles matrix offers is neither enforced anywhere nor recorded as unread. Enforce '
        .'it, or record it with its reason — an unrecorded phantom is a false statement about access.');

    // ── and NOTHING OUTSIDE the catalogue ───────────────────────────────────────────────────────────
    expect(array_values(array_diff(array_merge($enforced, $registered), $catalogue)))->toBe([],
        'the register or the census names a permission the catalogue does not contain');

    // ⚠️ THE ARITHMETIC, STATED — three numbers collapsed into one identity.
    expect(count($enforced) + count($registered))->toBe(count($catalogue),
        sprintf('enforced %d + registered %d != catalogue %d', count($enforced), count($registered), count($catalogue)));
});

/**
 * ⚠️⚠️ THE UNIVERSE ITSELF — the check the deleted census made possible by getting it wrong.
 *
 * 🔬 L6 counted over `config('authorization.roles')` — the permissions GRANTED TO A ROLE — rather than over
 * the catalogue, and skipped `'*'`. So its universe was 46, not 51, and FIVE catalogue permissions were
 * structurally invisible to it: `api.manage`, `billing.manage`, `organizations.manage`, `roles.manage` and
 * `settings.manage`, each held ONLY through a wildcard role. Two of them (`api.manage`,
 * `organizations.manage`) are phantoms, so L6 could never have seen them however good its matcher was.
 *
 * ⚠️ THE REMAINING THREE ARE R-F10's THREE, and their absence from every non-wildcard role is the ruling —
 * pinned separately by `SeededDefaultKeepsTheThreeTest`. Nothing here should be read as questioning it.
 *
 * ⚠️ WHAT THIS GUARDS is the other direction, which nothing asserted: a role granting a permission that is
 * not in the catalogue at all. That would be a grant no matrix could display and no census could classify.
 */
it('⚠️ every permission granted to a role exists in the catalogue', function () {
    $catalogue = vyPermissionCatalogue();

    $granted = [];
    foreach ((array) config('authorization.roles', []) as $role => $perms) {
        if (! is_array($perms)) {
            continue;
        }
        foreach ($perms as $perm) {
            if ($perm !== '*') {
                $granted[(string) $perm][] = (string) $role;
            }
        }
    }

    expect(count($granted))->toBeGreaterThan(40, 'no role grants anything; this world is empty');

    $orphans = [];
    foreach (array_diff(array_keys($granted), $catalogue) as $perm) {
        $orphans[] = $perm.' (granted to '.implode(', ', $granted[$perm]).')';
    }
    sort($orphans);

    expect($orphans)->toBe([],
        'a role grants a permission that is not in the catalogue, so no matrix can show it and no census '
        ."can classify it:\n".implode("\n", $orphans));
});
