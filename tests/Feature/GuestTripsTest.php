<?php

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// GUEST TRIPS (Phase 4b · Sub-gate 1) — the post-login landing: the authed guest's OWN stays only. 🔒 GUEST
// ISOLATION: the trips come from the guest's own reservations (lead_guest_id), resolved from the guard — never a
// request id — so guest A never sees guest B's booking. Amounts + status reconcile to the folio (shared GuestTrip).

/** Claim a guest row into a portal account. */
function tripGuest(Guest $g, string $email): Guest
{
    $g->update(['email' => $email, 'first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $g->password = 'guest-password-1234';
    $g->email_verified_at = now();
    $g->save();

    return $g->fresh();
}

/** Book a reservation LED by a specific guest (book() always uses the scenario guest). */
function bookFor(array $s, Guest $guest): Reservation
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'lead_guest_id' => $guest->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2026-09-01', 'check_out' => '2026-09-03', 'price_minor' => 10000, 'currency' => 'EUR']],
    );
}

// ── ISOLATION: a guest sees ONLY their own trips, never another guest's ───────────────────────────
it('shows the authed guest their own trips and never another guest’s', function () {
    $s = seedScenario(2);
    $a = tripGuest($s['guest'], 'ada@example.test');
    $b = Guest::create(['email' => 'bob@example.test', 'first_name' => 'Bob', 'last_name' => 'Vale']);

    $aRes = bookFor($s, $a);
    $bRes = bookFor($s, $b);

    $this->actingAs($a, 'guest')->get('/guest/trips')
        ->assertOk()
        ->assertSee($aRes->reference)        // A's own booking…
        ->assertDontSee($bRes->reference);   // …never B's
});

// ── Amounts reconcile: an unpaid balance shows as "due", a settled one as "Paid in full" ──────────
it('shows the guest-facing balance, reconciling to the folio', function () {
    $s = seedScenario(1);
    $a = tripGuest($s['guest'], 'ada@example.test');
    $reservation = bookFor($s, $a);
    postRoom($reservation, 20000);   // a €200 charge, no payment → balance due

    $this->actingAs($a, 'guest')->get('/guest/trips')
        ->assertOk()
        ->assertSee('€200.00')     // the real folio balance via MoneyFormatter
        ->assertSee('Upcoming');   // the shared ReservationStage badge (a future confirmed stay)
});

// ── An unauthenticated visitor bounces to the GUEST login (realm separation holds) ────────────────
it('redirects an unauthenticated visitor to the guest login', function () {
    $this->get('/guest/trips')->assertRedirect(route('guest.login'));
});

// ── #33: the arrival SECRETS never reach the list ────────────────────────────────────────────────
// Trips is a LIST. The door code and wifi are window-gated on the Arrival surface (ArrivalPack), and a list
// has no window to gate on — so they must not appear here at all, even for a stay whose window is OPEN and
// whose unit really does carry a code. A card may LINK to the gated surface; it may never inline the secret.
it('never renders the door code or wifi on the trips list, even mid-stay with a code set', function () {
    $s = seedScenario(1);
    $a = tripGuest($s['guest'], 'ada@example.test');

    // A stay that is happening RIGHT NOW — the arrival window is open — on a unit with real secrets.
    // seedScenario only publishes Sep 2026, so the calendar needs the current window too.
    app(AvailabilityService::class)->recompute(
        $s['roomType']->id, today()->subDays(3)->toDateString(), today()->addDays(3)->toDateString()
    );
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => today()->subDay()->toDateString(),
            'check_out' => today()->addDay()->toDateString(), 'lead_guest_id' => $a->getKey(), 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => today()->subDay()->toDateString(),
            'check_out' => today()->addDay()->toDateString(), 'price_minor' => 10000, 'currency' => 'EUR']],
    );
    $s['units'][0]->update(['door_code' => '447291', 'wifi_ssid' => 'SeasideGuest', 'wifi_password' => 'sunrise-tide-88']);

    // ⚠️ createBooking reserves at ROOM-TYPE level and leaves reservation_units.unit_id NULL. Without this
    // assignment the booking has no physical unit, so there is no door code to leak and the guard below
    // passes vacuously — proven: a mutation that inlined the code into the card stayed GREEN until this
    // line existed. Assign through the certified path so the secret is genuinely reachable.
    app(AssignmentService::class)->assignUnit($reservation->reservationUnits()->firstOrFail(), $s['units'][0]->id);
    expect($reservation->fresh()->reservationUnits()->first()->unit->door_code)->toBe('447291');

    $html = $this->actingAs($a, 'guest')->get('/guest/trips')->assertOk()->getContent();

    // The stay IS on the page (so this can never pass vacuously on an empty list)…
    expect($html)->toContain($reservation->reference);
    // …and none of its secrets are.
    foreach (['447291', 'SeasideGuest', 'sunrise-tide-88'] as $secret) {
        expect($html)->not->toContain($secret);
    }
});

// ── #39: a guest with NO stays gets an honest empty state, never a placeholder trip ───────────────
it('shows an honest empty state for a guest with no trips at all', function () {
    $s = seedScenario(1);
    $a = tripGuest($s['guest'], 'ada@example.test');   // claimed, but has never booked

    $html = $this->actingAs($a, 'guest')->get('/guest/trips')->assertOk()
        ->assertSee('No upcoming stays')
        ->getContent();

    // Every hero stat is a real zero — not a fabricated "369 stays" — and no trip card was invented.
    expect($html)->not->toMatch('/R-[A-Z0-9]{6,}/')        // no reservation reference anywhere
        ->and($html)->not->toContain('View reservation');   // no trip-card CTA
});

// ── G1 pixel-diff: the travel-account HERO + Upcoming/Past/Cancelled tabs + search (all REAL, native GET) ──
it('renders the travel-account hero, real tab counts, and filters by tab + search', function () {
    $s = seedScenario(2);
    $a = tripGuest($s['guest'], 'ada@example.test');
    bookFor($s, $a);   // one upcoming stay

    // Hero renders with the real first name + the "Your travel account" band; the trip card shows.
    $this->actingAs($a, 'guest')->get(route('guest.trips'))
        ->assertOk()
        ->assertSee('Your travel account')
        ->assertSee('Welcome back, Ada')
        ->assertSee('View reservation');

    // The Cancelled tab has no trips → honest empty state, and the upcoming trip is NOT shown there.
    $this->actingAs($a, 'guest')->get(route('guest.trips', ['tab' => 'cancelled']))
        ->assertOk()
        ->assertSee('No cancelled bookings')
        ->assertDontSee('R-');   // no reservation reference rows on the cancelled tab

    // Search that can't match → honest "no match" empty state (never a fabricated result).
    $this->actingAs($a, 'guest')->get(route('guest.trips', ['q' => 'zzz-no-such-place']))
        ->assertOk()
        ->assertSee('match your search');
});
