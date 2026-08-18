<?php

use App\Models\Owner;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// OPERATOR FIX 2 — Finding 1's route gates, per the founder ruling: gate the routes that render OWNER/FINANCE
// money; leave OPERATIONAL money open to the roles that already reach it (a front-desk agent needs a guest's
// balance; a maintenance lead needs cost figures).
//
// ⚠️⚠️ EVERY GATE IS ASSERTED IN BOTH DIRECTIONS, AND THAT IS NOT OPTIONAL. A 403-only test passes just as
// happily on a route that is broken, misnamed or removed. The 200 half is what proves the gate lets the right
// roles through — the probe rule, applied to authorization.
//
// ⚠️ AND THE ROUTES DELIBERATELY LEFT OPEN ARE ASSERTED TOO. A fix pass that silently over-gates is a worse
// outcome than the leak: it locks the operational roles out of the screens they run the property from.

/** A booking through the certified path, so there is a real folio to ask for a PDF of. */
function fgReservation(array $s): Reservation
{
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-06-01', '2026-06-30');

    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-06-10',
            'check_out' => '2026-06-13', 'lead_guest_id' => $s['guest']->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][0]->id, 'rate_plan_id' => $s['ratePlan']->id,
            'check_in' => '2026-06-10', 'check_out' => '2026-06-13', 'price_minor' => 30000, 'currency' => 'EUR']],
    );
}

// ── 1 · OWNERS list + detail — owner financials and an owner's email (`reports.view`) ──────────────────
it('gates the owner list and owner detail on the finance read permission', function () {
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['organization_id' => $s['org']->id, 'payout_currency' => 'EUR']);

    // ⚠️ ENTITLED — Hotel Manager holds `reports.view`. Both pages still render exactly as before; the gate
    // is transparent to those who may pass.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager)->get(route('app.owners'))->assertOk();
    $this->actingAs($manager)->get(route('app.owners.show', $owner->getKey()))->assertOk();

    // ⚠️ GATED — Housekeeping was reading owner financials and an owner's email address before this pass.
    $housekeeper = operatorUser($s, [], 'Housekeeping');
    $this->actingAs($housekeeper)->get(route('app.owners'))->assertForbidden();
    $this->actingAs($housekeeper)->get(route('app.owners.show', $owner->getKey()))->assertForbidden();

    // …and Front Desk, an operational role with no finance read, is out too.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))->get(route('app.owners'))->assertForbidden();
});

// ── 2 · PAYOUT RUN — payout figures (`payouts.manage`, matching its own POST sibling) ──────────────────
it('gates the payout run on the same permission its own POST requires', function () {
    $s = seedScenario(1);

    // ⚠️ ENTITLED — Accountant holds `payouts.manage` and the run screen still opens.
    $this->actingAs(operatorUser($s, [], 'Accountant'))->get(route('app.payouts.run'))->assertOk();

    // ⚠️ GATED — Housekeeping and Hotel Manager do not hold it. (Hotel Manager is included deliberately:
    // the GET now matches the POST exactly, so read and write agree on this surface.)
    $this->actingAs(operatorUser($s, [], 'Housekeeping'))->get(route('app.payouts.run'))->assertForbidden();
    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get(route('app.payouts.run'))->assertForbidden();
});

// ── 3 · FOLIO PDF — the downloadable document (`folios.view`) ──────────────────────────────────────────
it('gates the folio PDF on the folio read permission while keeping front desk in', function () {
    $s = seedScenario(1);
    $reservation = fgReservation($s);

    // ⚠️ ENTITLED — and the artifact is a REAL PDF, not just a 200. A status-only assertion would pass on an
    // error page rendered with a 200.
    $desk = operatorUser($s, [], 'Front Desk');   // operational: a guest's folio is their job (the ruling)
    $response = $this->actingAs($desk)->get(route('app.reservations.folio-pdf', $reservation->getKey()));
    $response->assertOk();
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');

    // ⚠️ GATED — Housekeeping has no folio business, and gets no document.
    $this->actingAs(operatorUser($s, [], 'Housekeeping'))
        ->get(route('app.reservations.folio-pdf', $reservation->getKey()))->assertForbidden();
});

// ── 4 · EMAIL PREVIEWS — gated on `folios.view`, the same permission as the folio PDF ─────────────────
it('gates the email previews on the folio read permission, keeping the operator workflow', function () {
    $s = seedScenario(1);
    fgReservation($s);   // the preview 404s honestly with no booking to sample

    // ⚠️ POSITIVE CONTROL, AND IT IS THE WHOLE ARGUMENT FOR THIS PERMISSION. The previews render real booking
    // money — but that money is the POINT of the surface, not a leak: PaymentReminderBrandTest exists to
    // prove an operator previewing a payment reminder sees correct arithmetic. Hotel Manager is the actor
    // both existing preview tests use, and it holds `folios.view`, so the documented workflow survives.
    // `properties.manage` would have 403'd exactly this role — which is why it was reverted in OP-FIX-2.
    $manager = operatorUser($s, [], 'Hotel Manager');
    $this->actingAs($manager)->get(route('app.email-previews'))->assertOk();
    $body = $this->actingAs($manager)
        ->get(route('app.email-previews.show', 'booking-confirmation'))->assertOk()->getContent();
    expect($body)->toContain('Booking confirmed');          // the preview really renders, not an empty 200

    // …and a front-desk agent, the other operational role with folio business, keeps it too.
    $this->actingAs(operatorUser($s, [], 'Front Desk'))->get(route('app.email-previews'))->assertOk();

    // ⚠️ EXCLUDED — BOTH roles rendered, not one. Finding 6 was invisible precisely because a role that is
    // never rendered is where a gating defect hides.
    foreach (['Housekeeping', 'Maintenance'] as $role) {
        $excluded = operatorUser($s, [], $role);
        $this->actingAs($excluded)->get(route('app.email-previews'))->assertForbidden();
        $this->actingAs($excluded)->get(route('app.email-previews.show', 'booking-confirmation'))->assertForbidden();
    }
});

// ── 5 · ⚠️ THE ROUTES DELIBERATELY LEFT OPEN — the founder ruling, pinned ──────────────────────────────
it('leaves the operational-money pages open to the roles that run the property', function () {
    $s = seedScenario(1);
    $reservation = fgReservation($s);

    // ⚠️ Front Desk runs the property from these screens and NEEDS the balances on them. Gating them would
    // have been a worse outcome than the leak this pass closes — pinned so a later sweep cannot quietly
    // tighten them.
    $desk = operatorUser($s, [], 'Front Desk');
    foreach ([
        route('app.dashboard'), route('app.front-desk'), route('app.reservations'),
        route('app.reservations.show', $reservation->getKey()), route('app.calendar'), route('app.rack'),
    ] as $url) {
        $this->actingAs($desk)->get($url)->assertOk();
    }

    // …and a maintenance lead keeps the cost figures on the maintenance board.
    $this->actingAs(operatorUser($s, [], 'Maintenance'))->get(route('app.maintenance'))->assertOk();
});

// ── 6 · ⚠️ SECURITY EXPORT — LEFT OPEN because it is SELF-SCOPED; this pins that ───────────────────────
it('keeps the security export open and proves it exports only the requesting user', function () {
    $s = seedScenario(1);

    $alice = operatorUser($s, ['name' => 'Alice Front', 'email' => 'alice.fg@example.test'], 'Front Desk');
    $bob = operatorUser($s, ['name' => 'Bob Keeper', 'email' => 'bob.fg@example.test'], 'Housekeeping');

    // ⚠️ OPEN FOR BOTH — a 200 across roles is NOT a leak when the payload is per-user. The audit measured
    // the status and stopped; this reads the CONTENTS, which is the only thing that settles it.
    $aliceBody = $this->actingAs($alice)->get(route('app.security.export'))->assertOk()->streamedContent();
    $bobBody = $this->actingAs($bob)->get(route('app.security.export'))->assertOk()->streamedContent();

    // ⚠️ EACH EXPORT CARRIES ITS OWN IDENTITY AND NOT THE OTHER'S. This is the assertion that would red if a
    // future change made the export org-wide — turning a one-off manual verification into a standing guard.
    expect($aliceBody)->toContain('alice.fg@example.test')
        ->and($aliceBody)->not->toContain('bob.fg@example.test')
        ->and($bobBody)->toContain('bob.fg@example.test')
        ->and($bobBody)->not->toContain('alice.fg@example.test');
});
