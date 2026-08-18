<?php

use App\Models\EmailTemplate;
use App\Models\MaintenanceTicket;
use App\Models\Owner;
use App\Models\UnitRequest;
use App\Services\MaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR FIX 1 — the control-gate sweep for the operator DEEP audit's Findings 2 · 4 · 5 · 6 · 7
// (docs/audits/operator-portal-DEEP-parity-2026-08-18.md).
//
// ⚠️⚠️ EVERY TEST HERE CARRIES A POSITIVE CONTROL, AND THAT IS THE WHOLE POINT. The audit's own worst
// instrument failures were absences that proved nothing — a hidden control read as "gated", an empty table
// read as "gated", a filter that removed the very string being looked for. So each test first asserts that an
// ENTITLED role DOES see the control, and only then that the unentitled role does not. A vacuous
// "sees nothing" cannot pass here.
//
// ⚠️ AND THE ROLES ARE THE POINT TOO. The demo seeds 4 accounts; the back-office allow-list holds 11. Finding
// 6 is invisible to all four seeded roles — only Revenue Manager (rates.manage WITHOUT units.manage) reveals
// it. `operatorUser($s, [], '<Role>')` builds any of the eleven, so these land MEASURED, not derived.

/** A ticket through the certified service, so the detail page has a real work order to render. */
function ocgTicket(array $s): MaintenanceTicket
{
    return app(MaintenanceService::class)->open([
        'unit_id' => $s['units'][0]->id,
        'title' => 'AC not cooling',
        'category' => 'appliance',
        'priority' => 'urgent',
        'description' => 'Blowing warm air.',
    ]);
}

// ── FINDING 2 · /app/calendar — `New reservation` now carries `reservations.create` ────────────────────
it('offers New reservation on the calendar only to a role that may create one', function () {
    $s = seedScenario(1);

    // ⚠️ POSITIVE CONTROL FIRST — an entitled role really does render the control, so the absence below is
    // a fact about the gate and not about a broken selector or an empty page.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $managerHtml = $this->actingAs($manager)->get(route('app.calendar'))->assertOk()->getContent();
    expect($managerHtml)->toContain('new=1');                    // the control's href
    $managerModal = $this->actingAs($manager)->get(route('app.calendar').'?new=1')->assertOk()->getContent();
    expect($managerModal)->toContain(route('app.reservations.store'));   // the write surface really opens

    // ⚠️ Housekeeping holds no `reservations.create` — the control must not be offered at all.
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $deskHtml = $this->actingAs($housekeeper)->get(route('app.calendar'))->assertOk()->getContent();
    // ⚠️ Scoped to the CONTROL, not the bare words: "New reservation" also appears in the page's own help
    // text ("Use \u201cNew reservation\u201d to book"), which is prose, not an affordance. Asserting the raw
    // string would have failed for the wrong reason — the same over-broad read the audit kept catching.
    expect($deskHtml)->not->toContain('new=1');

    // ⚠️ AND THE MODAL — the real write surface — stays shut even when asked for directly.
    $modalHtml = $this->actingAs($housekeeper)->get(route('app.calendar').'?new=1')->assertOk()->getContent();
    expect($modalHtml)->not->toContain(route('app.reservations.store'));

    // …and the route it pointed at genuinely refuses that role (the 403 the control used to invite).
    $this->actingAs($housekeeper)->get(route('app.reservations.create'))->assertForbidden();
});

// ── FINDING 4 · the work order — `Add update` no longer scrolls to a composer that isn't there ─────────
it('offers Add update on a work order only to a role that can add one', function () {
    $s = seedScenario(1);
    $ticket = ocgTicket($s);

    $manager = operatorUser($s, [], 'Hotel Manager');
    $managerHtml = $this->actingAs($manager)->get(route('app.maintenance.show', $ticket->id))->assertOk()->getContent();
    // ⚠️ POSITIVE: the entitled role sees BOTH the anchor and the composer it targets.
    expect($managerHtml)->toContain('Add update')
        ->and($managerHtml)->toContain('id="wo-composer"');

    // ⚠️ Accountant has no `maintenance.manage`. The page stays readable — this was never a page gate — but
    // the anchor must go, because the composer it points at is already hidden for this role.
    $accountant = operatorUser($s, [], 'Accountant');
    $acctHtml = $this->actingAs($accountant)->get(route('app.maintenance.show', $ticket->id))->assertOk()->getContent();
    expect($acctHtml)->not->toContain('Add update')
        // the read-only view itself is intact — proving we gated a control, not the page
        ->and($acctHtml)->toContain('Activity');
});

// ── FINDING 5 · the email EDITOR — its write controls now match the routes they submit to ──────────────
it('offers the email editor write controls only to a role holding properties.manage', function () {
    $s = seedScenario(1);

    $admin = operatorUser($s, [], 'Property Owner');
    $adminHtml = $this->actingAs($admin)
        ->get(route('app.email-templates.edit', 'booking-confirmation'))->assertOk()->getContent();
    // ⚠️ POSITIVE: an entitled role gets both write affordances and the real guarded routes behind them.
    expect($adminHtml)->toContain('Save template')
        ->and($adminHtml)->toContain('Send me a test')
        ->and($adminHtml)->toContain(route('app.email-templates.save', 'booking-confirmation'));

    // ⚠️ Housekeeping (4 permissions) reaches this page — it carries no ROUTE gate, which is a separate
    // finding — but it must be offered no write.
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $hkHtml = $this->actingAs($housekeeper)
        ->get(route('app.email-templates.edit', 'booking-confirmation'))->assertOk()->getContent();
    expect($hkHtml)->not->toContain('Save template')
        ->and($hkHtml)->not->toContain('Send me a test')
        // ⚠️ the editable FORM is gone too, not just its buttons — an input submits its form on Enter, so a
        // rendered form with hidden buttons would still have invited the 403.
        ->and($hkHtml)->not->toContain(route('app.email-templates.save', 'booking-confirmation'))
        ->and($hkHtml)->not->toContain(route('app.email-templates.test', 'booking-confirmation'))
        // …while the page itself still renders for them (a control gate, not a page gate)
        ->and($hkHtml)->toContain('Booking confirmation');

    // …and the save it used to offer is genuinely refused, writing no row.
    $this->actingAs($housekeeper)
        ->post(route('app.email-templates.save', 'booking-confirmation'), ['heading' => 'nope'])
        ->assertForbidden();
    expect(EmailTemplate::withoutGlobalScopes()->where('template_key', 'booking-confirmation')->exists())
        ->toBeFalse();
});

// ── FINDING 6 · units photos — ⚠️ AN ADD, NOT A SWAP, AND REVENUE MANAGER IS THE PROOF ─────────────────
it('offers the room-type photo controls on units.manage while leaving the rates control on rates.manage', function () {
    $s = seedScenario(1);

    // ⚠️ POSITIVE: a `units.manage` role sees the photo controls and their real guarded route.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $managerHtml = $this->actingAs($manager)->get(route('app.units'))->assertOk()->getContent();
    expect($managerHtml)->toContain('Room-type photos')
        ->and($managerHtml)->toContain(route('app.room-types.photos.store', $s['roomType']->id));

    // ⚠️⚠️ REVENUE MANAGER — holds `rates.manage`, NOT `units.manage`. This is the role none of the four
    // demo accounts can stand in for, and the only one that separates the two permissions.
    $revenue = operatorUser($s, [], 'Revenue Manager');
    $revHtml = $this->actingAs($revenue)->get(route('app.units'))->assertOk()->getContent();

    // The photo controls are gone — they post to `units.manage` routes.
    expect($revHtml)->not->toContain('Room-type photos')
        ->and($revHtml)->not->toContain(route('app.room-types.photos.store', $s['roomType']->id));

    // ⚠️⚠️ AND THE RATES CONTROL SURVIVES. This assertion is what makes the fix an ADD rather than a SWAP:
    // `$canManageRates` guards "Price individually", which is a rates.manage control and correctly stays.
    // Had the guard been swapped to units.manage, THIS would red — and a real gate would have been broken
    // while "fixing" a different one.
    expect($revHtml)->toContain('Price individually');

    // …and the photo write is genuinely refused for that role.
    $this->actingAs($revenue)
        ->post(route('app.room-types.photos.store', $s['roomType']->id), [])
        ->assertForbidden();
});

// ── FINDING 7 · unit-requests — ⚠️ NO FIX WAS NEEDED; this PINS the gate that was already correct ──────
it('offers unit-request Approve and Decline only to a role holding units.manage', function () {
    $s = seedScenario(1);

    // ⚠️ A REAL PENDING ROW. The audit could not settle this finding because `unit_requests` had 0 rows —
    // an empty table renders no buttons for anyone, which is indistinguishable from a working gate. The row
    // is the whole point of this test.
    $owner = Owner::factory()->create([
        'organization_id' => $s['org']->id,
        'payout_currency' => 'EUR',
    ]);
    $request = UnitRequest::withoutGlobalScopes()->create([
        'organization_id' => $s['org']->id,
        'owner_id' => $owner->getKey(),
        'property_id' => $s['property']->id,
        'requested_name' => 'Casa Onda 2B',
        'status' => 'pending',
    ]);
    expect(UnitRequest::withoutGlobalScopes()->where('status', 'pending')->count())->toBe(1);

    // ⚠️ POSITIVE: a `units.manage` role sees the review controls on that real row.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $managerHtml = $this->actingAs($manager)->get(route('app.unit-requests'))->assertOk()->getContent();
    expect($managerHtml)->toContain('Casa Onda 2B')
        // ⚠️ BOTH guards on this page are covered: the review <td> (the buttons) and the form block (the
        // routes). A mutation removing only one of them must still red — proven for each separately.
        ->and($managerHtml)->toContain('>Review<')                    // the review column header (guard A)
        ->and($managerHtml)->toContain("mode = 'approve'")            // the Approve button itself (guard B)
        ->and($managerHtml)->toContain(route('app.unit-requests.approve', $request->id))
        ->and($managerHtml)->toContain(route('app.unit-requests.decline', $request->id));

    // ⚠️ Front Desk holds `units.view` but NOT `units.manage` — it reaches the page and sees the row, and
    // must see no review control. (This gate was already correct; the audit's grep missed it because the
    // view uses the component-computed $canManage rather than a @can literal.)
    $desk = operatorUser($s, [], 'Front Desk');
    $deskHtml = $this->actingAs($desk)->get(route('app.unit-requests'))->assertOk()->getContent();
    // ⚠️ Asserted on the ROUTES, not the words: "Approve" is a substring of the "Approved" status chip this
    // page also renders, so a bare-string absence check fails for the wrong reason — over-broad reads were
    // the audit's most frequent instrument error, and this is the same one.
    expect($deskHtml)->toContain('Casa Onda 2B')          // the row renders — not an empty-table false pass
        ->and($deskHtml)->not->toContain('>Review<')
        ->and($deskHtml)->not->toContain("mode = 'approve'")
        ->and($deskHtml)->not->toContain(route('app.unit-requests.approve', $request->id))
        ->and($deskHtml)->not->toContain(route('app.unit-requests.decline', $request->id));

    $this->actingAs($desk)->post(route('app.unit-requests.approve', $request->id))->assertForbidden();
    expect(UnitRequest::withoutGlobalScopes()->find($request->id)->status)->toBe('pending');
});
