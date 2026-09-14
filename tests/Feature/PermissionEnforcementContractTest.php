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

/**
 * ⚠️ EVERY ENTRY CARRIES A REASON AND A RULING, never a widened pattern. `remove` / `wire` / `reclassify`
 * are recorded as the OPTION each needs, not as something this pass performed.
 */
const VY_UNREAD_PERMISSIONS = [
    // ── The capability IS enforced, under a DIFFERENT permission ────────────────────────────────────
    'taxes.manage' => 'Tax editing exists at POST /app/settings/tax-fees but is gated by `properties.manage`. ⚠️ MEASURED: a Hotel Manager holding `taxes.manage` is refused 403. Needs a ruling — wire the route to this permission, or drop it.',
    'fees.manage' => 'Same as taxes.manage — /app/settings/fees/add is gated by `properties.manage`.',
    'restrictions.manage' => 'Stay restrictions are edited through the rates surface, gated by `rates.manage`.',
    'availability.manage' => 'Availability is written only through AssignmentService, reached via `rates.manage`/`reservations.manage`.',
    'availability.view' => 'Availability is read through the calendar and rack, gated by `rates.view`/`reservations.view`.',
    'housekeeping.view' => 'The housekeeping board (GET /app/housekeeping) carries no gate at all; every WRITE is gated by `housekeeping.manage`. See the ungated-screens section of the doc.',

    // ── The capability does not exist as an operator surface ────────────────────────────────────────
    'invoices.view' => 'No invoice surface exists — invoice numbering is an explicit "Soon" in Settings.',
    'invoices.issue' => 'Same — nothing issues an invoice.',
    'amenities.manage' => 'No amenity management surface exists.',
    'corporate_accounts.manage' => 'No corporate-account surface exists; `reservations.source` carries `corporate` but nothing manages accounts.',
    'guest_documents.manage' => 'Guest ID documents are handled by the guest realm and the retention purge, never by an operator permission.',
    'api.manage' => 'The OAuth/API surface is platform-admin only (`oauth:register-client`), deliberately not self-serve.',
    'organizations.manage' => 'Organisations are created only by TenantProvisioningService; the platform admin realm has its own guard.',
    'addons.manage' => 'Add-ons are a GUEST request flow (POST /guest/reservation/{r}/manage/add-ons); no operator surface consumes this.',

    // ── Enforced by a ROLE allow-list rather than a permission ──────────────────────────────────────
    'owner_portal.view' => 'The owner realm is gated by role (`EnsureBackOfficeAccess` + the External Owner role), not by this permission. Held as the marker of the owner role.',
    'guest_portal.view' => 'The guest realm has its own guard and its own auth guard entirely.',
    'payments.view' => 'Payment reads ride on the folio surfaces, gated by `folios.view`; the only /payments routes are guest-realm.',
];

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
