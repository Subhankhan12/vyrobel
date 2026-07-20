<?php

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\FolioService;
use App\Support\Money\Money;
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

    $this->actingAs($a, 'guest')->get(route('guest.reservation', $reservation->getKey()))
        ->assertOk()
        ->assertSee($reservation->reference)
        ->assertSee('Room')                    // the charge line
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

// ── Payment deferred honestly: the balance is shown, "Settle" is stubbed, nothing is faked ────────
it('shows an outstanding balance but stubs payment (no faked pay)', function () {
    $s = seedScenario(1);
    $a = resvGuest($s['guest'], 'ada@example.test');
    $reservation = bookLedBy($s, $a);
    $folio = app(FolioService::class)->openFolio($reservation);
    app(FolioService::class)->postCharge($folio, 'room', 'Room', Money::of(20000, 'EUR'));   // charge, no payment

    $this->actingAs($a, 'guest')->get(route('guest.reservation', $reservation->getKey()))
        ->assertOk()
        ->assertSee('€200.00')                 // the real folio balance
        ->assertSee('Settle your stay · Soon') // payment is 4c — stubbed, not a real pay action
        ->assertDontSee('Pay with Apple Pay'); // the design's account-gated pay is not faked
});
