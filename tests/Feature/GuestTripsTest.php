<?php

use App\Models\Guest;
use App\Models\Reservation;
use App\Services\AssignmentService;
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
