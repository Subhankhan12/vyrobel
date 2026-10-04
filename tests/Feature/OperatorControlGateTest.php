<?php

use App\Models\EmailTemplate;
use App\Models\MaintenanceTicket;
use App\Models\Owner;
use App\Models\Reservation;
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
    // the write surface really opens — its PANEL (2026-10-04): the store route's address is also the full form's, linked from
    // inside the dialog, so it showed the dialog opened but not what it holds (the calendar pass's review); the panel says it
    expect($managerModal)->toContain('aria-labelledby="vy-rnr-title"');

    // ⚠️ Housekeeping holds no `reservations.create` — the control must not be offered at all.
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $deskHtml = $this->actingAs($housekeeper)->get(route('app.calendar'))->assertOk()->getContent();
    // ⚠️ Scoped to the CONTROL, not the bare words: "New reservation" appeared in the page's own help text
    // ("Use \u201cNew reservation\u201d to book" until 2026-10-04), which is prose, not an affordance. Asserting the raw
    // string would have failed for the wrong reason — the same over-broad read the audit kept catching.
    expect($deskHtml)->not->toContain('new=1');

    // ⚠️ AND THE MODAL — the real write surface — stays shut even when asked for directly.
    $modalHtml = $this->actingAs($housekeeper)->get(route('app.calendar').'?new=1')->assertOk()->getContent();
    expect($modalHtml)->not->toContain(route('app.reservations.store'))->not->toContain('aria-labelledby="vy-rnr-title"');

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

    // ⚠️⚠️ FOUNDER RULING 2026-09-22 — this used to be a CONTROL gate on an open page, and the comment here
    //    called that "a separate finding". The founder settled it: the page itself is now gated
    //    `email_templates.manage`, so Housekeeping is refused outright. The control-gate assertions below
    //    are replaced by the stronger one — no page, therefore no write affordance to hide.
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $this->actingAs($housekeeper)
        ->get(route('app.email-templates.edit', 'booking-confirmation'))->assertForbidden();

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
        'requested_name' => 'Casa Onda 2B',
        'status' => 'pending',
    ]);
    expect(UnitRequest::withoutGlobalScopes()->where('status', 'pending')->count())->toBe(1);

    // ⚠️ POSITIVE: a `units.manage` role sees the review controls on that real row.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $managerHtml = $this->actingAs($manager)->get(route('app.unit-requests'))->assertOk()->getContent();
    expect($managerHtml)->toContain('Casa Onda 2B')
        // ⚠️ BOTH guards on this page are covered: the review buttons and the request's own addresses. A mutation removing
        // only one of them must still red — proven for each separately. (2026-10-03: the page was rebuilt — the review
        // column and the old `mode = 'approve'` button are gone, so the needles name the new markup; the gate is unchanged.)
        ->and($managerHtml)->toContain("review('approve', \$el)")      // the Approve button itself (guard A)
        ->and($managerHtml)->toContain('data-review=')                  // the request's review addresses (guard B)
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
        ->and($deskHtml)->not->toContain("review('approve', \$el)")
        ->and($deskHtml)->not->toContain('data-review=')
        ->and($deskHtml)->not->toContain(route('app.unit-requests.approve', $request->id))
        ->and($deskHtml)->not->toContain(route('app.unit-requests.decline', $request->id));

    $this->actingAs($desk)->post(route('app.unit-requests.approve', $request->id))->assertForbidden();
    // ⚠️ Untouched means BOTH still pending AND not trashed. The bare scope returns a soft-deleted request
    //    exactly as it returns a live one, so a status check alone passed with the row removed (planted).
    $after = UnitRequest::withoutGlobalScopes()->withTrashed()->find($request->id);
    expect($after->status)->toBe('pending')->and($after->trashed())->toBeFalse();
});

// ── ⚠️⚠️ APPLY 03 · R3 / R9 — INVARIANT #30 READ BACKWARDS: A PERMISSION WITH NO BUTTON ─────────────────
//
// ⚠️⚠️ #30 WARNS AGAINST A CONTROL WITH NO PERMISSION — a button that invites a 403. This is the mirror:
// `ReverseCheckOut` (R3) shipped 2026-09-19 and 🔬 NO VIEW EVER LINKED ITS ROUTE, so for three days a built,
// tested and PINNED capability could not be invoked by any operator. `ReverseCheckIn` (R9) shipped in the same
// state, which is worse — R9 exists to undo a mis-clicked check-in, so the person in the trap could not reach
// the way out.
//
// ⚠️⚠️ AND NOTHING IN THE SUITE COULD HAVE NOTICED, WHICH IS THE PART WORTH KEEPING. Every R3/R9 test drives
// the ROUTE directly — `$this->post(route(...))` — which is exactly the thing an operator cannot do. A test
// that reaches a capability by a door the user does not have proves the capability, never the access.
// **These two tests are the ones that would have noticed.**

it('⚠️⚠️ R9 — offers Reverse check-in on a checked-in stay, to a role that may check in, and routes THROUGH the confirmation', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    Reservation::query()->whereKey($reservation->getKey())->update(['status' => 'checked_in']);

    // ⚠️ POSITIVE FIRST — Front Desk holds `reservations.checkin`. A vacuous "sees nothing" cannot pass here.
    $desk = operatorUser($s, [], 'Front Desk');
    $deskHtml = $this->actingAs($desk)
        ->get(route('app.reservations.show', $reservation->getKey()))->assertOk()->getContent();

    expect($deskHtml)->toContain((string) $reservation->reference)          // the page really rendered
        ->and($deskHtml)->toContain(route('app.front-desk.reverse-checkin', $reservation->getKey()))
        ->and($deskHtml)->toContain('Reverse check-in');

    // ⚠️⚠️ IT IS A LINK TO THE CONFIRMATION, NOT A POST TO THE WRITER. A one-click reverse that skipped the
    //    disclosure would be a regression dressed as a convenience.
    //
    // ⚠️ ASSERTED ON THE MARKUP, NOT ON THE ROUTE NAME — and the first version of this test got that wrong.
    //    `reverse-checkin` (GET, the confirmation) and `reverse-checkin.store` (POST, the writer) are the SAME
    //    PATH on two verbs, so `route()` returns an IDENTICAL string for both and "the store route is absent"
    //    is unprovable by URL. 🔬 It failed for exactly that reason. The real discriminator is the ELEMENT:
    //    an `<a href>` reaches the disclosure; a `<form method="POST">` would skip it.
    $reverseUrl = route('app.front-desk.reverse-checkin', $reservation->getKey());
    expect($deskHtml)->toContain('<a href="'.$reverseUrl.'"');
    expect(preg_match('/<form[^>]*action="'.preg_quote($reverseUrl, '/').'"/', $deskHtml))
        ->toBe(0, 'the control POSTs straight to the writer, skipping the disclosure page');

    // ⚠️ NEGATIVE — Housekeeping holds neither checkin nor checkout (🔬 4 permissions total).
    $hk = operatorUser($s, [], 'Housekeeping');
    $hkResponse = $this->actingAs($hk)->get(route('app.reservations.show', $reservation->getKey()));

    // ⚠️ If Housekeeping cannot reach the detail page at all, the control question is moot but the ROUTE gate
    //    must still refuse — assert whichever applies rather than assuming reachability.
    if ($hkResponse->status() === 200) {
        expect($hkResponse->getContent())
            ->not->toContain(route('app.front-desk.reverse-checkin', $reservation->getKey()));
    }

    // ⚠️ AND THE ROUTE REFUSES INDEPENDENTLY — the control being hidden is the UI half, never the gate.
    $this->actingAs($hk)->get(route('app.front-desk.reverse-checkin', $reservation->getKey()))->assertForbidden();
});

it('⚠️⚠️ R3 — offers Reverse check-out on a checked-out stay, and hides it from a role that may not', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    Reservation::query()->whereKey($reservation->getKey())->update(['status' => 'checked_out']);

    $desk = operatorUser($s, [], 'Front Desk');
    $deskHtml = $this->actingAs($desk)
        ->get(route('app.reservations.show', $reservation->getKey()))->assertOk()->getContent();

    expect($deskHtml)->toContain((string) $reservation->reference)
        ->and($deskHtml)->toContain(route('app.front-desk.reverse-checkout', $reservation->getKey()))
        ->and($deskHtml)->toContain('Reverse check-out');

    // ⚠️ Same discriminator as R9's — element, not URL. R3's disclosure is the one that really matters: a
    //    checkout fans out (departure clean, dirty unit, notifications, a review email that cannot be
    //    recalled) and that page is where an operator learns which of those stand.
    $reverseOutUrl = route('app.front-desk.reverse-checkout', $reservation->getKey());
    expect($deskHtml)->toContain('<a href="'.$reverseOutUrl.'"');
    expect(preg_match('/<form[^>]*action="'.preg_quote($reverseOutUrl, '/').'"/', $deskHtml))
        ->toBe(0, 'the control POSTs straight to the writer, skipping the disclosure page');

    $hk = operatorUser($s, [], 'Housekeeping');
    $hkResponse = $this->actingAs($hk)->get(route('app.reservations.show', $reservation->getKey()));
    if ($hkResponse->status() === 200) {
        expect($hkResponse->getContent())
            ->not->toContain(route('app.front-desk.reverse-checkout', $reservation->getKey()));
    }
    $this->actingAs($hk)->get(route('app.front-desk.reverse-checkout', $reservation->getKey()))->assertForbidden();
});

it('⚠️ neither reversal is offered on a status where its action would refuse', function () {
    // ⚠️ THE STATE HALF OF THE GATE, SEPARATELY. A control shown on the wrong status invites a refusal from
    //    the action rather than a 403 from the route — a different failure with the same cost to the clerk.
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);   // `confirmed`
    $desk = operatorUser($s, [], 'Front Desk');

    $html = $this->actingAs($desk)
        ->get(route('app.reservations.show', $reservation->getKey()))->assertOk()->getContent();

    expect($html)->toContain((string) $reservation->reference)   // the page rendered — not an empty false pass
        ->and($html)->not->toContain(route('app.front-desk.reverse-checkin', $reservation->getKey()))
        ->and($html)->not->toContain(route('app.front-desk.reverse-checkout', $reservation->getKey()));
});
