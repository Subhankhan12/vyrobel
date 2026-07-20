<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerAttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Book the unit, post the room charge, run the certified attribution → owner_ledger rows. Uniquely named +
 * guarded: a sibling test file declares `attributedStay` UNGUARDED, so reusing that name would fatally
 * redeclare depending on file load order.
 */
if (! function_exists('bkStay')) {
    function bkStay(array $s, Unit $unit, int $roomMinor): Reservation
    {
        $reservation = book($s, ['unit_id' => $unit->id]);
        postRoom($reservation, $roomMinor);
        app(OwnerAttributionService::class)->attributeReservation($reservation);

        return $reservation;
    }
}

// OWNER BOOKINGS + BOOKING DETAIL (Phase 2 · Sub-gate 1) — owner-scoped reads over the owner's OWN units.
//   THE HEADLINE GUARD: reservations carry no owner_id (owner A + owner B share the operator's ORG), so the
//   owner boundary is applied THROUGH the units — the set comes from the owner-scoped units, and a {booking}
//   id is REJECTED (404) unless it touches a unit this owner owns. Both proven here (and proven to fail if the
//   guard is removed). Plus: "Net to you" reconciles to the owner ledger; guest NAME only (no contact);
//   real source enum (OTA, never a brand); stage via the shared ReservationStage.

/**
 * A linked owner-portal user who owns unit[$index], with a distinct name. Reuses the shared ownUnit() (owner +
 * 15% agreement + unit->owner_id) and links a portal user on top. Guarded so it can't collide across files.
 *
 * @return array{user: User, owner: Owner, unit: Unit}
 */
if (! function_exists('bookingsOwner')) {
    function bookingsOwner(array $s, int $index, string $name): array
    {
        /** @var Unit $unit */
        $unit = $s['units'][$index];
        $owner = ownUnit($unit);

        $user = User::factory()->create(['name' => $name]);
        Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
        $owner->update(['user_id' => $user->id]);

        return ['user' => $user, 'owner' => $owner, 'unit' => $unit];
    }
}

// ── 1. LIST ISOLATION — an owner sees only bookings on their OWN units ────────────────────────────
it('lists only the owner’s own-unit bookings, never another owner’s', function () {
    $s = seedScenario(2);
    $s['guest']->update(['first_name' => 'Lars', 'last_name' => 'Andersen']);

    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $b = bookingsOwner($s, 1, 'Bruno Vale');

    $mine = bkStay($s, $a['unit'], 20000);     // A's booking
    $theirs = bkStay($s, $b['unit'], 50000);   // B's booking — must never appear for A

    $this->actingAs($a['user'])->get('/owner/bookings?tab=all')
        ->assertOk()
        ->assertSee('Lars Andersen')            // guest NAME (name-only)
        ->assertSee($mine->reference)           // A's booking is listed…
        ->assertDontSee($theirs->reference);    // …B's booking is NOT (through-units scoping)
});

// ── 2. THE CRITICAL GUARD — a cross-owner {booking} id 404s (proven both ways + fail-closed) ──────
it('404s another owner’s booking detail and fails closed for a non-linked user', function () {
    $s = seedScenario(2);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $b = bookingsOwner($s, 1, 'Bruno Vale');

    $mine = bkStay($s, $a['unit'], 20000);
    $theirs = bkStay($s, $b['unit'], 50000);

    // A's own booking resolves…
    $this->actingAs($a['user'])->get('/owner/bookings/'.$mine->id)->assertOk();

    // …owner B's booking does NOT: it touches no unit A owns → 404, not a leak (the #26 shape).
    $this->actingAs($a['user'])->get('/owner/bookings/'.$theirs->id)->assertNotFound();

    // A non-linked authenticated member → empty owner set → the owned-unit check is empty → 404 (fail closed).
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/bookings/'.$mine->id)->assertNotFound();
});

// ── 3. NET RECONCILES to the owner ledger, to the cent (list + detail) ────────────────────────────
it('shows a “Net to you” that reconciles to the owner ledger', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');

    bkStay($s, $a['unit'], 20000);   // €200 room − 15% commission = €170 net

    // The exact figure the screen is claiming — Σ signedMinor over THIS booking's owner-scoped ledger rows.
    $ledgerNet = OwnerLedgerEntry::where('owner_id', $a['owner']->id)->get()
        ->reduce(fn (int $c, OwnerLedgerEntry $e): int => $c + $e->signedMinor(), 0);
    expect($ledgerNet)->toBe(17000);   // €170.00

    $reservation = Reservation::query()->firstOrFail();

    // The list shows the net…
    $this->actingAs($a['user'])->get('/owner/bookings?tab=all')
        ->assertOk()->assertSee('€170.00');

    // …and the detail's gross→net breakdown reconciles (Credited + Deductions = Net), to the cent.
    $this->actingAs($a['user'])->get('/owner/bookings/'.$reservation->id)
        ->assertOk()
        ->assertSee('+€200.00')   // room_revenue credit
        ->assertSee('−€30.00')    // commission deduction (U+2212)
        ->assertSee('€170.00');   // net
});

// ── 4. GUEST NAME ONLY — never contact (invariant #28) ────────────────────────────────────────────
it('renders the guest name but never their contact details', function () {
    $s = seedScenario(1);
    $s['guest']->update([
        'first_name' => 'Lars', 'last_name' => 'Andersen',
        'email' => 'l.andersen@icloud.test', 'phone' => '+4520551804',
    ]);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $reservation = bkStay($s, $a['unit'], 20000);

    foreach (['/owner/bookings?tab=all', '/owner/bookings/'.$reservation->id] as $url) {
        $this->actingAs($a['user'])->get($url)
            ->assertOk()
            ->assertSee('Lars Andersen')             // NAME — allowed
            ->assertDontSee('l.andersen@icloud.test')// email — never
            ->assertDontSee('+4520551804');          // phone — never
    }
});

// ── 5. REAL SOURCE ENUM — an OTA stay is "OTA", never a brand ─────────────────────────────────────
it('renders the real source enum and never an OTA brand', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');
    $reservation = bkStay($s, $a['unit'], 20000);
    $reservation->update(['source' => 'ota']);   // a channel booking — the brand is NOT modelled

    $response = $this->actingAs($a['user'])->get('/owner/bookings?tab=all')->assertOk();
    $response->assertSee('OTA')
        ->assertDontSee('Booking.com')
        ->assertDontSee('Airbnb');

    assertNoRawBlade($response);
});

// ── 6. STAGE via the shared ReservationStage — an unattributed FUTURE booking still lists honestly ─
it('stages a booking via ReservationStage and shows “Not yet attributed” before attribution', function () {
    $s = seedScenario(1);
    $a = bookingsOwner($s, 0, 'Amara Okafor');

    // A confirmed FUTURE booking with NO attribution run yet — it belongs on the list (Upcoming) and its net
    // is honestly "Not yet attributed", never a fabricated €0.
    $reservation = book($s, ['unit_id' => $a['unit']->id]);   // 2026-09-01..03, confirmed, no ledger rows

    $this->actingAs($a['user'])->get('/owner/bookings')   // default tab = upcoming
        ->assertOk()
        ->assertSee($reservation->reference)
        ->assertSee('Upcoming')            // stage badge from ReservationStage::meta
        ->assertSee('Not yet attributed'); // no ledger rows → honest, not €0.00
});
