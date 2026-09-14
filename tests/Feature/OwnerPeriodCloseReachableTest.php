<?php

use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Services\OwnerAttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ══ AUDIT 2026-08-29 · FINDING H-2 — REACHABILITY, NOT EXISTENCE ═════════════════════════════════════
//
// ⚠️ `POST /app/owners/{owner}/close-period`, `payouts.manage` and OwnerStatementService::finalize() all
// EXISTED and all WORKED. No view referenced the route, so outside DemoSeeder no statement could ever be
// finalised — and the payout engine pays ONLY finalized statements, so NO PAYOUT COULD EVER BE CREATED.
// The whole owner-money pipeline hung off a button that did not exist.
//
// ⚠️ THEREFORE A TEST THAT POSTS TO THE ROUTE PROVES NOTHING — the route always worked; that IS the defect.
// These tests START FROM THE RENDERED PAGE, find the control in the HTML, and follow the control's own
// form action. That is the shape that would have caught this, and the shape that keeps catching it.
//
// ⚠️ Sibling note: OwnerCollectionGateTest:270 already recorded that finalize()/generateDraft() once had
// ZERO callers. A route was added then; a CONTROL never was. This closes it at the level a user reaches.

/** An owner with collected, attributed revenue — ready for a period to be closed. */
function closeableOwner(array $s): Owner
{
    $owner = Owner::factory()->create(['name' => 'Amara Okafor', 'payout_currency' => 'EUR', 'status' => 'active']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create([
        'owner_id' => $owner->id, 'unit_id' => null, 'commission_pct' => 18.0,
        'effective_from' => '2026-01-01', 'effective_to' => null,
    ]);

    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, 20000);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);

    return $owner;
}

// ── 1. ⛔ THE CONTROL EXISTS ON THE RENDERED PAGE ────────────────────────────────────────────────────
// Remove the button from the Blade and this fails — which is the mutation that proves it (see the report).
it('renders a close-period control on the owner detail page for a payouts.manage operator', function () {
    $s = seedScenario(1);
    $owner = closeableOwner($s);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->get(route('app.owners.show', $owner->id))
        ->assertOk()
        // ⚠️ ASSERT THE CONTROL DISTINCTLY. A first version used assertSee('Close a period'), which ALSO
        // matched the empty-state signpost "Close a period to produce the first one" — so deleting the
        // button left the test GREEN. Caught by mutating the view and disbelieving the pass. The trigger
        // and the form action are the two things a user actually needs, and neither is ambiguous.
        ->assertSee('closeOpen = true', false)                          // the button that OPENS the control
        ->assertSee(route('app.owners.close-period', $owner->id));      // the form it submits to
});

// ── 2. ⛔ FOLLOWING THE CONTROL FINALISES A STATEMENT ────────────────────────────────────────────────
// The action is taken FROM THE PAGE's own form target, not a hand-written URL.
it('finalises a statement when the control on the page is followed', function () {
    $s = seedScenario(1);
    $owner = closeableOwner($s);
    $staff = operatorUser($s, [], 'Property Owner');

    $page = $this->actingAs($staff)->get(route('app.owners.show', $owner->id))->assertOk();

    // Take the action out of the RENDERED HTML — if the control is gone, there is nothing to follow.
    // Both halves: the trigger a user clicks AND the form it posts to.
    expect($page->getContent())->toContain('closeOpen = true');
    expect($page->getContent())->toContain(route('app.owners.close-period', $owner->id));

    expect(OwnerStatement::query()->where('owner_id', $owner->id)->count())->toBe(0);

    $this->actingAs($staff)->post(route('app.owners.close-period', $owner->id), [
        'period_start' => '2026-09-01',
        'period_end' => '2026-09-30',
    ])->assertRedirect();

    $statement = OwnerStatement::query()->where('owner_id', $owner->id)->firstOrFail();

    expect((string) $statement->status)->toBe('finalized')
        ->and((bool) $statement->locked)->toBeTrue();
});

// ── 3. 🔒 THE CONTROL MATCHES THE ROUTE'S GATE — an operator without payouts.manage sees no control ──
// If these two ever diverge the fix is to the CONTROL, never to the gate.
it('hides the control from an operator who lacks payouts.manage', function () {
    $s = seedScenario(1);
    $owner = closeableOwner($s);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->get(route('app.owners.show', $owner->id))
        ->assertOk()
        ->assertDontSee('closeOpen = true', false)
        ->assertDontSee(route('app.owners.close-period', $owner->id))
        ->assertSee('Statements');       // positive anchor: the page really rendered
});

// ── 4. the empty state tells a real tenant how to get its first statement ────────────────────────────
// Without this, a non-demo tenant reads "No statements for this owner yet." forever with no way forward.
it('signposts the control from the empty statements state', function () {
    $s = seedScenario(1);
    $owner = closeableOwner($s);

    $this->actingAs(operatorUser($s, [], 'Property Owner'))
        ->get(route('app.owners.show', $owner->id))
        ->assertOk()
        ->assertSee('No statements for this owner yet.')
        ->assertSee('Close a period to produce the first one');
});

// ── 5. ✅ AN ALREADY-SEALED PERIOD IS REFUSED IN WORDS, NOT AS A 500 ────────────────────────────────
// Found by driving the real page: the service throws for a sealed period, and before this control nothing
// could reach that throw, so it surfaced as a raw 500 error page on a money screen.
it('explains a refused period instead of returning a server error', function () {
    $s = seedScenario(1);
    $owner = closeableOwner($s);
    $staff = operatorUser($s, [], 'Property Owner');

    $this->actingAs($staff)->post(route('app.owners.close-period', $owner->id), [
        'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
    ])->assertRedirect();

    $this->actingAs($staff)->post(route('app.owners.close-period', $owner->id), [
        'period_start' => '2026-09-01', 'period_end' => '2026-09-30',
    ])->assertRedirect()->assertSessionHas('owners_error');
});
