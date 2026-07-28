<?php

use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerAttributionService;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Owner\OwnerTeamRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// Wave 1 · Sub-gate 3 (Owner Unit Detail) — a NEW owner-scoped detail screen. Locks its PRESENTATION
// guarantees:
//   • the unit's REAL columns render (name/code/kind/sleeps/condition), never an invented label;
//   • OCCUPANCY IS THIS UNIT'S — a non-owner unit in the SAME room type is excluded. This is the
//     headline: room types are shared with hotel rooms, operator-owned units and other owners' units,
//     so the room-type-wide OwnerPortalService::occupancy()/AvailabilityService::availabilityRange()
//     reads would show this owner a number driven by units they do not own. Test 2 fails if anyone
//     swaps one in.
//   • the attributed money reconciles to the cent against the real owner_ledger rows;
//   • owner isolation holds — cross-owner / operator-owned / non-linked all 404 (fail closed);
//   • NO guest PII, and none of the design's fictions are rendered.

/**
 * A linked owner-portal user who owns the unit at $index. Reuses the shared ownUnit() helper
 * (tests/Support/owner_helpers.php) for the owner + governing agreement + unit assignment, and only
 * adds the portal user link on top. Named for this file so it cannot collide with another test file's
 * helper — the shared ones live in tests/Support, per that file's own discipline.
 *
 * @param  array<string, mixed>  $unitAttributes  real `units` columns to set before assigning the owner
 * @return array{user: User, owner: Owner, unit: Unit}
 */
function unitDetailOwner(array $s, int $index, array $unitAttributes = []): array
{
    /** @var Unit $unit */
    $unit = $s['units'][$index];

    if ($unitAttributes !== []) {
        $unit->update($unitAttributes);
    }

    $owner = ownUnit($unit);   // shared helper: owner + 15% agreement + unit->owner_id

    $user = User::factory()->create(['name' => 'Amara Okafor']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner->update(['user_id' => $user->id]);   // link the owner record to a portal user

    return ['user' => $user, 'owner' => $owner, 'unit' => $unit];
}

/** Book the unit (2026-09-01..03 → 2 nights), post the room charge, and run the certified attribution. */
function attributedStay(array $s, Unit $unit, int $roomMinor): Reservation
{
    $reservation = book($s, ['unit_id' => $unit->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);

    return $reservation;
}

// ── 1. The unit's REAL facts + the REAL month occupancy ──────────────────────────────────────────
it('renders the unit\'s real facts and the real month occupancy', function () {
    $s = seedScenario(1);
    $a = unitDetailOwner($s, 0, [
        'code' => 'CASA-01',
        'name' => 'Casa Ondine',
        'unit_kind' => 'apartment',       // REAL enum
        'max_occupancy' => 4,             // the design's "Sleeps"
        'condition_status' => 'out_of_service',   // REAL enum — surfaced honestly
    ]);

    attributedStay($s, $a['unit'], 20000);   // 2 nights in Sep 2026 → the focus month resolves there

    $response = $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id);

    $response->assertOk()
        ->assertSee('Casa Ondine')        // REAL name (the title)
        ->assertSee('CASA-01')            // REAL code
        ->assertSee('Apartment')          // REAL unit_kind
        ->assertSee('Std')                // REAL room type
        ->assertSee('Sleeps')             // REAL max_occupancy
        ->assertSee('Out of service')     // REAL condition_status
        ->assertSee('September 2026')     // the resolved focus month
        ->assertSee('2 nights')           // DERIVED occupancy — this unit, this month
        ->assertSee('Confirmed')          // the REAL reservation status enum
        ->assertSee('Direct')             // the REAL source enum
        ->assertSee('15.00%');            // the REAL effective agreement's commission_pct

    assertNoRawBlade($response);
});

// ── 2. THE OCCUPANCY-CORRECTNESS PROOF — the room-type-wide trap ─────────────────────────────────
// Two units in the SAME room type, both booked in the same month, owned by DIFFERENT owners. A's Unit
// Detail must count ONLY A's unit. A room-type-wide read (availabilityRange / OwnerPortalService::
// occupancy) would report both bookings — this test is what makes that regression impossible.
it('counts only this unit — a non-owner unit in the same room type is excluded', function () {
    $s = seedScenario(2);   // U1 + U2 share one room type
    $a = unitDetailOwner($s, 0, ['code' => 'OWNEDUNIT']);
    $b = unitDetailOwner($s, 1, ['code' => 'FOREIGNUNIT']);   // a DIFFERENT owner, same room type

    $mine = attributedStay($s, $a['unit'], 20000);     // 2 nights → net €170.00
    $theirs = attributedStay($s, $b['unit'], 50000);   // 2 nights, same room type + dates → net €425.00

    // Guard the premise: the two bookings really are in the same room type on different units.
    expect((string) $a['unit']->room_type_id)->toBe((string) $b['unit']->room_type_id)
        ->and((string) $a['unit']->id)->not->toBe((string) $b['unit']->id);

    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)
        ->assertOk()
        ->assertSee('2 nights')                  // THIS unit's booking only
        ->assertDontSee('4 nights')              // NOT U1+U2 — the room-type-wide count is excluded
        ->assertSee('OWNEDUNIT')
        ->assertDontSee('FOREIGNUNIT')
        ->assertSee($mine->reference)            // this unit's stay is listed…
        ->assertDontSee($theirs->reference)      // …the other unit's is not
        ->assertSee('€170.00')                   // this unit's attributed net
        ->assertDontSee('€425.00');              // the other unit's net never appears

    // And the shared derivation itself agrees: this unit alone sold 2 nights.
    expect(OwnerOccupancy::forMonth([(string) $a['unit']->id], Carbon::parse('2026-09-01'))['nights'])->toBe(2);
});

// ── 3. The attributed money reconciles TO THE CENT ───────────────────────────────────────────────
it('reconciles this unit\'s attributed revenue to the cent', function () {
    $s = seedScenario(1);
    $a = unitDetailOwner($s, 0);

    attributedStay($s, $a['unit'], 20000);   // €200.00 room revenue − 15% commission

    // The REAL rows for THIS unit in the month — the exact figure the screen is claiming.
    $expected = OwnerLedgerEntry::query()
        ->where('unit_id', $a['unit']->id)
        ->whereBetween('service_date', ['2026-09-01', '2026-09-30'])
        ->get()
        ->reduce(fn (int $carry, OwnerLedgerEntry $entry): int => $carry + $entry->signedMinor(), 0);

    expect($expected)->toBe(17000)                          // €170.00
        ->and($expected)->toBe(ownerNet($a['owner']->id));  // the owner's whole ledger is this one unit

    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)
        ->assertOk()
        ->assertSee('+€200.00')    // Credited — the room_revenue bucket
        ->assertSee('−€30.00')     // Deductions — the commission bucket (U+2212, per MoneyFormatter)
        ->assertSee('€170.00');    // Net — Credited + Deductions, to the cent
});

// ── 4. ISOLATION — cross-owner, operator-owned and non-linked all 404 ────────────────────────────
it('404s another owner\'s unit and fails closed for a non-linked user', function () {
    $s = seedScenario(3);
    $a = unitDetailOwner($s, 0, ['code' => 'AUNIT']);
    $b = unitDetailOwner($s, 1, ['code' => 'BUNIT']);
    $operatorOwned = $s['units'][2];   // owner_id stays null — operator inventory

    // A's own unit resolves…
    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)->assertOk();

    // …owner B's does NOT: OwnerScope constrains the findOrFail → 404 rather than a leak.
    $this->actingAs($a['user'])->get('/owner/units/'.$b['unit']->id)->assertNotFound();

    // …and neither does operator-owned inventory in the same room type.
    $this->actingAs($a['user'])->get('/owner/units/'.$operatorOwned->id)->assertNotFound();

    // A non-linked authenticated member resolves to an EMPTY owner set → `1 = 0` → 404 (fail closed).
    $stranger = User::factory()->create();
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $stranger->id, 'status' => 'active']);
    $this->actingAs($stranger)->get('/owner/units/'.$a['unit']->id)->assertNotFound();
});

// ── 5. NO GUEST PII — a booking is identified by its reference, never by a person ────────────────
it('never renders guest PII', function () {
    $s = seedScenario(1);
    $s['guest']->update(['first_name' => 'Zephyrina', 'last_name' => 'Quillfeather']);
    $a = unitDetailOwner($s, 0);

    $reservation = attributedStay($s, $a['unit'], 20000);

    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)
        ->assertOk()
        ->assertSee($reservation->reference)   // the stay IS identified — by its REAL reference
        ->assertDontSee('Zephyrina')
        ->assertDontSee('Quillfeather');
});

// ── 6. None of the design's fictions are rendered ────────────────────────────────────────────────
it('renders none of the design\'s fictions', function () {
    $s = seedScenario(1);
    $a = unitDetailOwner($s, 0);
    attributedStay($s, $a['unit'], 20000);

    // NOTE (#65 exact-match): the Wave-1 refusals for pricing (nightly + min-stay), day-blocking, reviews and
    // the message relay are now BUILT REAL (their backends shipped later — OwnerRateService / OwnerBlockService /
    // Phase-5 reviews / the owner↔operator relay); those are covered by their own tests below + the service
    // suites. What remains REFUSED are the genuinely-unmodelled fictions asserted here.
    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)
        ->assertOk()
        // Weekend uplift is NOT modelled — shown as an honest "Not available", never a fabricated figure.
        ->assertDontSee('Weekend uplift · ')
        // No owner-facing OTA sync, housekeeping, guest-messaging or review-category backend exists.
        ->assertDontSee('Sync calendars')          // OTA channel sync is account-gated (honest note only)
        ->assertDontSee('Schedule a cleaning')     // no owner cleaning scheduler
        ->assertDontSee('Cleaning schedule')       // no fake cleaner/schedule (honest "managed by your team")
        ->assertDontSee('Guest messages')          // guest threads / owner→guest forbidden (#28) — a name-free relay instead
        ->assertDontSee('Cleanliness')             // review category bars are not modelled
        ->assertDontSee('Best performer')          // single-unit owner → no cross-unit ranking claim
        ->assertDontSee('Payout timeline')         // payouts are per-statement/per-owner, never per-unit
        ->assertDontSee('saved you')               // the fabricated "Direct saved you €X" (directRev × 0.15)
        ->assertDontSee('All bookings')            // no owner bookings list on this screen
        // The OTA BRAND IS NOT MODELLED — `reservations.source` is direct|walk_in|ota|corporate|group|
        // agent|manual — so a brand can never be rendered, on this screen or any other.
        ->assertDontSee('Booking.com')
        ->assertDontSee('Airbnb');
});

// ── Batch B · GATE 2 — the REAL per-unit charts + channel mix + best-performer badge ──────────────
it('renders the real 6-month revenue trend, 12-month occupancy trend, channel mix and best badge', function () {
    $s = seedScenario(2);
    $a = unitDetailOwner($s, 0, ['code' => 'CASA-LEAD']);
    $s['units'][1]->update(['owner_id' => $a['owner']->id, 'code' => 'CASA-IDLE']);   // owner's 2nd unit, idle
    attributedStay($s, $a['unit'], 20000);   // 2 nights Sep 2026 → real revenue + real ledger net

    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)
        ->assertOk()
        ->assertSee('Revenue · 6 months')                  // the NET revenue trend (shared bar component)
        ->assertSee('Net to you')
        ->assertSee('Occupancy · 12 months')               // the occupancy trend (trailing 12 months)
        ->assertSee('Channel mix')                         // this unit's real source mix
        ->assertSee('room-nights by booking source')       // by the real source enum, never an OTA brand (#39)
        ->assertSee('★ Best performer of your 2 units');   // the real cross-unit ranking badge
});

// ── #28 — a single-unit owner gets NO "best performer" badge (a "best of 1" is meaningless) ───────
it('shows no best-performer badge for a single-unit owner', function () {
    $s = seedScenario(1);
    $a = unitDetailOwner($s, 0, ['code' => 'SOLO-01']);
    attributedStay($s, $a['unit'], 20000);

    $this->actingAs($a['user'])->get('/owner/units/'.$a['unit']->id)
        ->assertOk()
        ->assertSee('Revenue · 6 months')
        ->assertDontSee('Best performer of your');   // no cross-unit claim with a single unit
});

// ── Exact-match pass (#65) — the aggregated real sections + HONEST states (no fabrication) ───────────
it('renders real pricing (honest weekend uplift), reviews, and honest cleaning/relay/payout — no fabrication', function () {
    $s = seedScenario(1);
    $o = unitDetailOwner($s, 0);

    $res = $this->actingAs($o['user'])->get('/owner/units/'.$o['unit']->id)->assertOk()
        ->assertSee('Pricing & availability')
        ->assertSee('Weekend uplift')->assertSee('Not available')      // honest — weekend uplift is NOT modelled
        ->assertSee('Reviews & rating')->assertSee('No published reviews')   // honest-empty (none seeded)
        ->assertSee('coordinated by your management team')             // honest cleaning — never a fake cleaner
        ->assertSee('Message your management team')                    // the owner↔operator relay (name-free #28)
        ->assertSee('per statement across your whole portfolio')       // honest — payouts are not per-unit
        // ⚠️ never the wireframe's fabrications (#39)
        ->assertDontSee('saved you')
        ->assertDontSee('SparkleCare')->assertDontSee('Ana Ruiz')->assertDontSee('CoastClean');

    assertNoRawBlade($res);
});

it('gates the block + edit-pricing writes to CAP_ACTIONS — a view-only sub-user gets neither control', function () {
    $s = seedScenario(1);
    $o = unitDetailOwner($s, 0);
    $ownerId = (string) $o['owner']->id;

    // The primary owner (co-owner data access) gets the real write controls.
    $this->actingAs($o['user'])->get('/owner/units/'.$o['unit']->id)->assertOk()
        ->assertSee('owner/rates/apply')      // the edit-pricing form action (real OwnerRateService write)
        ->assertSee('owner/calendar/block');  // the tap-to-block form action (real OwnerBlockService write)

    // A view-only sub-user may VIEW the unit but gets NO write controls (#20/#30 — gate the control too).
    $viewer = ownerSubUser($s, $ownerId, OwnerTeamRole::VIEW_ONLY);
    $this->actingAs($viewer)->get('/owner/units/'.$o['unit']->id)->assertOk()
        ->assertDontSee('owner/rates/apply')
        ->assertDontSee('owner/calendar/block');
});
