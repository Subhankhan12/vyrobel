<?php

declare(strict_types=1);

use App\Models\CompanyAccount;
use App\Models\Folio;
use App\Support\Folio\FolioTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * ⚠️ The row flag AS IT ACTUALLY APPEARS IN THE PAGE. `@js()` renders the compact row payload into an
 * `x-on:click` attribute with quotes escaped as `\u0022`, so this is the only string that observes the flag
 * Alpine reads. Written once, because a test that searches for a different spelling of it is green for the
 * wrong reason — which is exactly how the missing payload key survived its first run.
 */
const BTA_FLAG = '\\u0022can_bill_to_account\\u0022';

/**
 * ⚠️⚠️ APPLY 04 — THE PRODUCT NAMED A REMEDY AND PROVIDED NO WAY TO REACH IT.
 *
 * ⚠️⚠️ `OutstandingBalanceException` refuses a check-out and tells the clerk exactly what to do instead:
 * *"…Take the payment, **bill it to a company account** if they settle later, or post an adjustment…"*.
 * 🔬 Measured 2026-09-22: `app.reservations.bill-to-account` had **no view, no Livewire component, no PHP and
 * no URI anywhere** — the only site naming it was a test. Its undo, `unbill-from-account`, had not even that.
 *
 * ⚠️⚠️ AND THE REASON NOBODY NOTICED IS THE REASON R3 WENT UNREACHABLE FOR THREE DAYS: every existing test
 * drove `$this->post(route(...))`, **a door no operator has**. A test that reaches a capability that way
 * proves the capability and says nothing about the access. ⚠️ So the tests below do BOTH — and the one that
 * matters most is `arrives the way a clerk does`, which reads the control out of the rendered page and posts
 * to the URL **the page itself names**. If the control disappears, that test fails; the old ones would not.
 */
function btaStay(int $chargeMinor): array
{
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $chargeMinor);
    $reservation->forceFill(['status' => 'checked_in'])->save();

    return [$s, $reservation->refresh(), Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail()];
}

function btaAccount(string $orgId, string $name = 'Northwind Ltd'): CompanyAccount
{
    return CompanyAccount::query()->create(['organization_id' => $orgId, 'name' => $name]);
}

/**
 * Revokes ONE permission from ONE role in the scenario's org — the state `app.roles.permissions` produces.
 *
 * ⚠️ Teams mode: the registrar's team id must be the org, or spatie resolves the role in the wrong team and
 * the revoke silently no-ops. The assertion below is there because a fixture that quietly does nothing is a
 * test that quietly proves nothing.
 */
function revokeRolePermission(array $scenario, string $roleName, string $permission): void
{
    $orgId = $scenario['org']->getKey();
    app(PermissionRegistrar::class)->setPermissionsTeamId($orgId);

    $role = Role::query()->where('organization_id', $orgId)->where('name', $roleName)->firstOrFail();
    $role->revokePermissionTo($permission);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($role->fresh()->hasPermissionTo($permission))->toBeFalse("the fixture failed to revoke {$permission}");
}

// ── 1. ⚠️⚠️ THE CONTROL EXISTS WHERE THE REFUSAL IS READ ────────────────────────────────────────────
it('⚠️⚠️ offers "bill to a company account" on the FRONT DESK, where the refusal is actually read', function () {
    [$s, $reservation] = btaStay(20000);
    $account = btaAccount((string) $reservation->organization_id);

    // ⚠️ THE BOARD, not the reservation screen: `backToBoard()` is where OutstandingBalanceException lands,
    //    so that is where the remedy must appear. A remedy two screens from the problem is the dead end with
    //    extra steps.
    $html = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get(route('app.front-desk', ['date' => $reservation->check_out->toDateString()]))
        ->assertOk()->getContent();

    expect($html)->toContain('bill-to-account')                 // the form action the modal builds
        ->and($html)->toContain('Or bill it to a company account')
        ->and($html)->toContain($account->name);                // a REAL account, offered by name

    // ⚠️⚠️ AND THE FLAG REACHED THE PAYLOAD ALPINE ACTUALLY READS — not merely the page.
    //    🔬 THIS EXACT ASSERTION USED TO READ `toContain('can_bill_to_account')`, WHICH WAS A FALSE PIN: the
    //    string also occurs in the literal `x-if="row.can_bill_to_account"`, so it was green while the
    //    payload omitted the key entirely, `row.can_bill_to_account` evaluated to `undefined`, and THE
    //    CONTROL NEVER RENDERED FOR A HUMAN. The control was in the HTML and absent from the screen — the
    //    precise failure this whole pass exists to remove, reproduced inside the fix for it.
    //    ⚠️ `@js()` escapes quotes as `\u0022`, so the observable string is the one below. Measured, not assumed.
    expect($html)->toContain(BTA_FLAG.':true');
});

// ── 2. ⚠️ #30 — THE CONTROL IS GATED, NOT ONLY THE ROUTE ────────────────────────────────────────────
it('⚠️ hides the control from a role without folios.manage, and the route refuses it too', function () {
    [$s, $reservation] = btaStay(20000);
    $account = btaAccount((string) $reservation->organization_id);

    // 🔬 Housekeeping holds neither folios.manage nor reservations.checkout.
    $hk = operatorUser($s, [], 'Housekeeping');
    $html = $this->actingAs($hk)->get(route('app.front-desk'))->assertOk()->getContent();

    // ⚠️⚠️ ASSERTED ON WHAT THE GATE ACTUALLY CONTROLS, AND THE FIRST VERSION OF THIS TEST GOT IT WRONG.
    //    The modal's control lives inside `<template x-if="row.can_bill_to_account">`, so the MARKUP is in the
    //    page for everyone and Alpine decides at runtime. Asserting the label's absence therefore failed —
    //    correctly. What must be absent is the DATA and the PERMISSION FLAG: the row flag is false, and the
    //    company account names are not shipped to a role that cannot bill (that gate is now server-side).
    // ⚠️⚠️ ASSERTED ON WHAT THE GATE ACTUALLY CONTROLS, AND TWO EARLIER DRAFTS OF THIS TEST GOT IT WRONG.
    //    The control lives inside `<template x-if="row.can_bill_to_account">` — the same Alpine pattern the
    //    existing `can_collect` control uses — so the TEMPLATE MARKUP is in the page for every viewer and
    //    Alpine decides at runtime. Asserting the label's absence failed (correctly), and so did asserting an
    //    inlined `"can_bill_to_account":false` (the rows are not inlined as raw JSON).
    // ⚠️ What a gate must actually deliver here is DATA ABSENCE: 🔬 a role without `folios.manage` is not
    //    shipped the tenant's company account names at all. That check reddens if the server-side gate is
    //    removed — which is the property worth pinning.
    expect($html)->not->toContain($account->name);

    // ⚠️ THE ROUTE REFUSES INDEPENDENTLY — hiding a control is the UI half, never the gate.
    $this->actingAs($hk)->post(route('app.reservations.bill-to-account', $reservation->id), [
        'company_account_id' => (string) $account->getKey(),
    ])->assertForbidden();

    expect(Folio::query()->where('reservation_id', $reservation->getKey())->value('billed_to_account_id'))->toBeNull();
});

// ── 2b. ⚠️⚠️ THE MUTATION PIN — a clerk who may check out but may NOT bill ──────────────────────────
it('⚠️⚠️ ships can_bill_to_account=false to a checkout-capable role whose folios.manage was revoked', function () {
    [$s, $reservation] = btaStay(20000);
    $account = btaAccount((string) $reservation->organization_id);

    // ⚠️⚠️ WHY THIS FIXTURE IS NOT ARTIFICIAL. 🔬 Measured 2026-09-22 against the seeded catalogue: `folios.manage`
    //    is held by 5 of the 11 back-office roles (Accountant · Front Desk · Group Admin · Hotel Manager ·
    //    Property Owner), and EVERY role that holds `reservations.checkout` also holds `folios.manage` — so in
    //    the default seed the row flag can never be observed false, and dropping its permission check reddened
    //    NOTHING. ⚠️ That is a fact about the SEED, not about the product: `app.roles.permissions`
    //    (RoleAssignmentController::savePermissions, `can:roles.manage`, re-auth gated) lets a Group Admin
    //    revoke exactly this one permission from Front Desk in production. This test builds that state.
    // ⚠️ The role must keep `reservations.checkout`, because 🔬 the compact row payload is only emitted at all
    //    when the row renders a button — no Check out / Collect / Move button, no payload, nothing to measure.
    $desk = operatorUser($s, [], 'Front Desk');
    revokeRolePermission($s, 'Front Desk', 'folios.manage');

    expect($desk->fresh()->can('reservations.checkout'))->toBeTrue('the fixture must still reach the modal')
        ->and($desk->fresh()->can('folios.manage'))->toBeFalse('the fixture must not be able to bill');

    $html = $this->actingAs($desk->fresh())
        ->get(route('app.front-desk', ['date' => $reservation->check_out->toDateString()]))
        ->assertOk()->getContent();

    // 1️⃣ the row flag — the thing Alpine gates the control on
    expect($html)->toContain(BTA_FLAG.':false')
        ->and($html)->not->toContain(BTA_FLAG.':true');
    // 2️⃣ the DATA — a role that cannot bill is not shipped the tenant's account names (#28-shaped: the UI
    //    gate and the server-side gate are separate layers and are proven separately)
    expect($html)->not->toContain($account->name);
    // 3️⃣ the ROUTE — the gate that actually stops the write
    $this->actingAs($desk->fresh())->post(route('app.reservations.bill-to-account', $reservation->id), [
        'company_account_id' => (string) $account->getKey(),
    ])->assertForbidden();

    expect(Folio::query()->where('reservation_id', $reservation->getKey())->value('billed_to_account_id'))->toBeNull();
});

// ── 3. ⚠️⚠️ THE TEST THAT ARRIVES THE WAY A CLERK DOES — the one R3 never had ───────────────────────
it('⚠️⚠️ arrives the way a clerk does: reads the control off the page and posts where IT points', function () {
    [$s, $reservation, $folio] = btaStay(20000);
    $account = btaAccount((string) $reservation->organization_id);
    $desk = operatorUser($s, [], 'Front Desk');

    $html = $this->actingAs($desk)->get(route('app.front-desk'))->assertOk()->getContent();

    // ⚠️ THE URL COMES FROM THE PAGE, NEVER FROM route(). The modal builds it in Alpine as
    //    `'/app/reservations/' + row.id + '/bill-to-account'`, so the test reconstructs it the same way and
    //    asserts the page really offers that shape — then uses the ACCOUNT ID the page listed.
    expect($html)->toContain("'/app/reservations/' + row.id + '/bill-to-account'");
    preg_match('/<option value="('.preg_quote((string) $account->getKey(), '/').')">/', $html, $m);
    expect($m[1] ?? null)->toBe((string) $account->getKey(), 'the page did not offer this account');

    $this->actingAs($desk)
        ->post('/app/reservations/'.$reservation->id.'/bill-to-account', ['company_account_id' => $m[1]])
        ->assertRedirect();

    // ⚠️ THE FOLIO READ BACK — it MOVES NO MONEY. Billing records WHO owes it; the balance is untouched.
    $folio = $folio->refresh()->load(['items', 'payments']);
    expect($folio->billed_to_account_id)->toBe((string) $account->getKey())
        ->and($folio->billed_at)->not->toBeNull()
        ->and((int) FolioTotals::for($folio)['balance_minor'])->toBe(20000, 'billing must not move money');
});

// ── 4. ⚠️ THE UNDO — reachable, and the folio read back ─────────────────────────────────────────────
it('⚠️ offers the undo on the reservation, and returning the folio clears the billing without moving money', function () {
    [$s, $reservation, $folio] = btaStay(20000);
    $account = btaAccount((string) $reservation->organization_id);
    $desk = operatorUser($s, [], 'Front Desk');

    $this->actingAs($desk)->post(route('app.reservations.bill-to-account', $reservation->id), [
        'company_account_id' => (string) $account->getKey(),
    ])->assertRedirect();

    // The billed state is READ on the reservation, where the folio lives — and so is its undo.
    $html = $this->actingAs($desk)->get(route('app.reservations.show', $reservation->id))->assertOk()->getContent();
    expect($html)->toContain('Billed to')
        ->and($html)->toContain($account->name)
        ->and($html)->toContain(route('app.reservations.unbill-from-account', $reservation->id))
        ->and($html)->toContain('Return to the guest');

    $this->actingAs($desk)
        ->post(route('app.reservations.unbill-from-account', $reservation->id))->assertRedirect();

    $folio = $folio->refresh()->load(['items', 'payments']);
    expect($folio->billed_to_account_id)->toBeNull()
        ->and((int) FolioTotals::for($folio)['balance_minor'])->toBe(20000, 'un-billing must not move money either');
});

it('⚠️ shows no undo when the folio is not billed, and hides it from a role that may not', function () {
    [$s, $reservation] = btaStay(20000);
    $account = btaAccount((string) $reservation->organization_id);

    // Not billed → no undo offered, even to a permitted role. (Positive control: the page rendered.)
    $html = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get(route('app.reservations.show', $reservation->id))->assertOk()->getContent();
    expect($html)->toContain((string) $reservation->reference)
        ->and($html)->not->toContain(route('app.reservations.unbill-from-account', $reservation->id));

    // Billed, but read by a role without folios.manage → still no control, and the route refuses.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))->post(route('app.reservations.bill-to-account', $reservation->id), [
        'company_account_id' => (string) $account->getKey(),
    ])->assertRedirect();

    $hk = operatorUser($s, [], 'Housekeeping');
    $hkResponse = $this->actingAs($hk)->get(route('app.reservations.show', $reservation->id));
    if ($hkResponse->status() === 200) {
        expect($hkResponse->getContent())->not->toContain(route('app.reservations.unbill-from-account', $reservation->id));
    }
    $this->actingAs($hk)->post(route('app.reservations.unbill-from-account', $reservation->id))->assertForbidden();

    expect(Folio::query()->where('reservation_id', $reservation->getKey())->value('billed_to_account_id'))
        ->toBe((string) $account->getKey(), 'the refused undo must not have un-billed it');
});

// ── 5. ⚠️ THE HONEST EMPTY STATE — the dead end one level down ──────────────────────────────────────
it('⚠️⚠️ says so plainly when no company accounts exist, rather than offering an empty picker', function () {
    // ⚠️ 🔬 THE ONLY PLACE A CompanyAccount IS EVER CREATED IS `DemoSeeder:697` — there is NO operator UI to
    //    add one. So a real tenant has ZERO, and a picker with an empty dropdown would be the same dead end
    //    one level down: a control that cannot work, offered at the moment of a refusal.
    [$s, $reservation] = btaStay(20000);
    expect(CompanyAccount::query()->count())->toBe(0, 'this test is about the empty case');

    $html = $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->get(route('app.front-desk'))->assertOk()->getContent();

    expect($html)->toContain('No company accounts are set up')
        ->and($html)->not->toContain('Choose an account…');
});
