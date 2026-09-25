<?php

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Guest\ArrivalPack;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST RESERVATION DETAIL (Phase 4b · Sub-gate 2) — one of the authed guest's OWN bookings. 🔒 ISOLATION: the
// booking is resolved THROUGH the guest's own reservations()->findOrFail (never implicit binding), so a foreign
// id → 404. Charges/balance reconcile to the folio; the timeline is real; payment is 4c (balance shown, stubbed).

if (! function_exists('resvGuest')) {
    function resvGuest(Guest $g, string $email): Guest
    {
        $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $g->password = 'guest-password-1234';
        $g->email_verified_at = now();
        $g->save();

        return $g->fresh();
    }
}

if (! function_exists('bookLedBy')) {
    function bookLedBy(array $s, Guest $guest): Reservation
    {
        return app(AssignmentService::class)->createBooking(
            ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
            [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 10000, 'currency' => 'EUR']],
        );
    }
}

// ── Own booking: real charges + balance + timeline, reconciling to the folio ──────────────────────
it('shows the guest their own booking’s real charges, balance and timeline', function () {
    $s = seedScenario(1);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $reservation = bookLedBy($s, $a);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));
    app(FolioService::class)->recordPayment($folio, Money::of(20000, 'EUR'));   // settled → a real timeline event

    // Pixel-diff: the reservation page shows the balance SUMMARY (the itemized lines live on the receipt);
    // the figures reconcile to the folio (#17), and the real payment shows in the timeline.
    $this->actingAs($a, 'guest')->get(route('guest.reservation', $reservation->getKey()))
        ->assertOk()
        ->assertSee($reservation->reference)   // in the page title
        ->assertSee('Total charges')           // the balance summary
        ->assertSee('Paid in full')            // balance reconciles (charged 200, paid 200 → 0)
        ->assertSee('Payment received');       // the real folio payment in the timeline
});

// ── ISOLATION (mutation-proven): a guest opening another guest's booking → 404 ────────────────────
it('404s when a guest opens another guest’s booking', function () {
    $s = seedScenario(2);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);
    $aRes = bookLedBy($s, $a);
    $bRes = bookLedBy($s, $b);

    $this->actingAs($a, 'guest')->get(route('guest.reservation', $aRes->getKey()))->assertOk();     // own → ok
    $this->actingAs($a, 'guest')->get(route('guest.reservation', $bRes->getKey()))->assertNotFound();   // foreign → 404
});

// ── Payment: the balance is shown + a link to the REAL pay flow (no faked pay, no coming-soon contradiction) ──
// Polish Batch 4 · I2 — the old "Settle · Soon" stub CONTRADICTED the live "Pay by card" on the Payments hub for
// the same booking. Resolved to ONE honest truth: the balance links to the Payments hub (where card payment is
// wired), never a fabricated card UI and never a coming-soon claim that the hub disproves.
it('shows an outstanding balance and links to the real pay flow (no faked pay)', function () {
    $s = seedScenario(1);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $reservation = bookLedBy($s, $a);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));   // charge, no payment

    $this->actingAs($a, 'guest')->get(route('guest.reservation', $reservation->getKey()))
        ->assertOk()
        ->assertSee('€200.00')                    // the real folio balance
        ->assertSee('Pay your balance')           // links to the Payments hub's live card payment
        ->assertSee(route('guest.payments'))      // the one honest truth: pay on the Payments hub
        ->assertDontSee('Settle your stay · Soon')// the old contradictory coming-soon stub is gone
        ->assertDontSee('Pay with Apple Pay');    // the design's account-gated card UI is not faked
});

// ── G1 pixel-diff HONESTY: the redesigned page refuses the wireframe's fabrications, and #33-gates arrival ──
it('refuses the fabricated payment/host UI and withholds the door code before the window (#33/#39)', function () {
    $s = seedScenario(1);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $reservation = bookLedBy($s, $a);   // check-in 2026-09-01 — comfortably >48h out at test-run time

    $res = $this->actingAs($a, 'guest')->get(route('guest.reservation', $reservation->getKey()))->assertOk();

    // #39: no fabricated saved-card vault / Apple Pay / biometric on the guest reservation page (SAQ A).
    $res->assertDontSee('Apple Pay')
        ->assertDontSee('Face ID')
        ->assertDontSee('A different card')
        ->assertDontSee('card on your booking')
        // …and no invented "we reply in ~10 min" support SLA.
        ->assertDontSee('~10 min');

    // #33: with no unit assigned there is no code at all, so this stay shows the honest "no code yet" line.
    // ⚠️ This assertion proves the COPY, not the window — see the real window guard below.
    $res->assertSee('Your arrival details appear here before check-in.', false);
});

// ── #33 THE REAL WINDOW GUARD — the code appears INSIDE the window and never outside it ──────────
// ⚠️ THE TEST ABOVE WAS VACUOUS AND STAYED GREEN FOR A YEAR. Its docblock claimed the door code was "gated to
// the release window", but `createBooking` reserves at ROOM-TYPE level and leaves reservation_units.unit_id
// NULL — so there was no unit, no code, and nothing the gate could withhold. PROVEN: deleting the window
// check from ArrivalPack entirely (`'door_code' => $code`) left all four tests GREEN. Identical root cause to
// the vacuous guard caught at G1. A #33 fixture MUST assign a real unit through the certified path.
//
// This guard drives the SAME booking across the boundary: one day before the window opens (withheld) and one
// day after (released), so it fails if the gate is removed in either direction.
it('withholds the door code until the arrival window opens, then shows it (#33)', function () {
    $s = seedScenario(1);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $reservation = bookLedBy($s, $a);   // check-in 2026-09-01
    app(AssignmentService::class)->assignUnit($reservation->reservationUnits()->firstOrFail(), $s['units'][0]->id);
    $s['units'][0]->update(['door_code' => '447291', 'wifi_ssid' => 'SeasideGuest']);

    // The secret is genuinely reachable — without this the guard can only pass vacuously.
    expect($reservation->fresh()->reservationUnits()->first()->unit->door_code)->toBe('447291');

    $url = route('guest.reservation', $reservation->getKey());

    // ── OUTSIDE the window (LEAD_DAYS + 1 before check-in) — withheld, and honest about WHEN ──
    $this->travelTo(CarbonImmutable::parse('2026-09-01')->subDays(ArrivalPack::LEAD_DAYS + 1));
    $this->actingAs($a, 'guest')->get($url)->assertOk()
        ->assertDontSee('447291')
        ->assertDontSee('SeasideGuest')
        ->assertSee('unlock on', false);        // the real released_from date, not a hardcoded interval

    // ── INSIDE the window (the day it opens) — released ──
    $this->travelTo(CarbonImmutable::parse('2026-09-01')->subDays(ArrivalPack::LEAD_DAYS));
    $this->actingAs($a, 'guest')->get($url)->assertOk()
        ->assertSee('447291')
        ->assertSee('SeasideGuest');

    $this->travelBack();
});

// ── The unlock date FOLLOWS the engine — it is never a hardcoded interval ─────────────────────────
// The prototype's mock reads "Door code arrives 48h before check-in", and the page shipped that literal.
// It equals LEAD_DAYS=2 today, so it looked right; move the constant and the guest is told the wrong day.
it('derives the unlock date from ArrivalPack, never a hardcoded interval', function () {
    $s = seedScenario(1);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $reservation = bookLedBy($s, $a);
    app(AssignmentService::class)->assignUnit($reservation->reservationUnits()->firstOrFail(), $s['units'][0]->id);
    $s['units'][0]->update(['door_code' => '447291']);

    $this->travelTo(CarbonImmutable::parse('2026-08-01'));
    $expected = CarbonImmutable::parse('2026-09-01')->subDays(ArrivalPack::LEAD_DAYS)->format('D, j M');

    $html = (string) $this->actingAs($a, 'guest')->get(route('guest.reservation', $reservation->getKey()))->assertOk()
        ->assertSee($expected, false)          // the gate's own released_from
        ->getContent();

    // ⚠️ F-68 (2026-09-25) — the prototype's mock interval must not be restated. `assertDontSee('48h')` searched the raw
    //    HTML, which carries a random 40-char CSRF token: about 1 render in 6,300 that token contains "48h", and this
    //    went red for no product reason (demonstrated with such a token — matched in the hidden `_token` input). The most
    //    probable mechanism of the 2026-09-23 red, which left no failure message. ⚠️ NOT `assertDontSeeText`: measured by
    //    mutation (C-M2), strip_tags() of THIS page swallows the visible sentence, so it went blind. Instead the interval
    //    must not stand as a WORD of its own — a token has letters/digits on both sides of "48h"; the copy does not.
    expect($html)->not->toMatch('/(?<![A-Za-z0-9])48\s?h(?:ours?)?(?![A-Za-z0-9])/');
    $this->travelBack();
});
