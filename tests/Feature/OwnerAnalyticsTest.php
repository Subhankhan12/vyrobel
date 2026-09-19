<?php

use App\Models\ManagementAgreement;
use App\Models\Membership;
use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Support\Owner\OwnerLedgerAttribution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 6b · SG3 — OWNER REPORTS + INSIGHTS. Owner-scoped over the SG1 metric layer; earnings reconcile to
// the owner ledger (the same figures the statements show); a real-or-omitted observation; owner isolation
// mutation-proven (owner A never sees owner B's unit analytics).

/** A ledger credit/debit on an owner's unit. */
function ledgerEntry(string $ownerId, string $unitId, string $type, string $direction, int $minor, ?string $reservationId = null, string $serviceDate = '2026-09-01'): OwnerLedgerEntry
{
    return OwnerLedgerEntry::factory()->create([
        'owner_id' => $ownerId,
        'unit_id' => $unitId,
        'reservation_id' => $reservationId,
        'entry_type' => $type,
        'direction' => $direction,
        'amount_minor' => $minor,
        'currency' => 'EUR',
        'service_date' => $serviceDate,
    ]);
}

/** A real stay on a specific unit over explicit dates (header + line dates match — for prior-year fixtures). */
function twoNightStay(array $s, string $unitId, string $checkIn, string $checkOut): Reservation
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $checkIn, 'check_out' => $checkOut, 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $unitId, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => 18000, 'currency' => 'EUR']],
    );
}

// ── 1. 🔒 OWNER ISOLATION — owner A sees their unit's analytics; owner B never sees A's ─────────────
it('scopes owner Reports + Insights to the owner\'s own units — A cannot see B', function () {
    $s = seedScenario(2);
    $s['units'][0]->update(['code' => 'OWNED-A']);
    $s['units'][1]->update(['code' => 'OWNED-B']);
    $userA = ownerRoleUser($s, 0);   // owner A ← units[0]
    $userB = ownerRoleUser($s, 1);   // owner B ← units[1]

    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);   // a stay on A's unit only

    // Owner A: their unit is listed, with the real stay.
    $this->actingAs($userA)->get('/owner/reports')->assertOk()
        ->assertSee('OWNED-A')->assertDontSee('OWNED-B');

    // Owner B: their own (empty) unit only — never A's unit or its data.
    $this->actingAs($userB)->get('/owner/reports')->assertOk()
        ->assertSee('OWNED-B')->assertDontSee('OWNED-A');

    // Insights is owner-scoped too.
    $this->actingAs($userA)->get('/owner/insights')->assertOk();
    $this->actingAs($userB)->get('/owner/insights')->assertOk();
});

// ── 2. 🪤 EARNINGS RECONCILE TO THE OWNER LEDGER (the same figure the statements show) ──────────────
it('shows owner net earnings that reconcile to the owner ledger', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // Room revenue credit − commission debit → net €255.00.
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 30000);
    ledgerEntry($ownerId, $s['units'][0]->id, 'commission', 'debit', 4500);

    // The ledger net, computed by the ONE shared attribution rule — what statements show.
    $entries = OwnerLedgerEntry::query()->where('owner_id', $ownerId)->get();
    $ledgerNet = OwnerLedgerAttribution::netPerCurrency($entries);
    expect($ledgerNet)->toBe(['EUR' => 25500]);

    // Owner Reports shows exactly that figure — reconciles by construction.
    $this->actingAs($user)->get('/owner/reports')->assertOk()->assertSee('€255.00');
});

// ── 3. ✅ OWNER INSIGHTS — real metrics, and NO fabricated narrative ───────────────────────────────
it('shows owner insights metrics with no invented narrative', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);

    $res = $this->actingAs($user)->get('/owner/insights?range=this-year');

    $res->assertOk()->assertSee('Occupancy')->assertSee('Avg nightly rate')
        // Batch B — the KPI band now carries the REAL median lead time + on-the-books, and the real
        // lead-time distribution + day-of-week occupancy (via the shared BookingCadence).
        ->assertSee('Median lead time')->assertSee('Next 90 days')
        ->assertSee('When guests book')->assertSee('Nights that fill first');

    // 🪤 The prototype's invented observations are never rendered (real-or-omitted). Distinctive phrases
    // only — a bare year like "2024" is too generic a needle (it collides with incidental ULIDs/wire-ids).
    $res->assertDontSee('Saturdays sell out first')
        ->assertDontSee('pts behind')
        ->assertDontSee('on record');

    assertNoRawBlade($res);
});

// ── 4. 🔒 A NON-LINKED USER FAILS CLOSED — sees NOTHING, never another owner's analytics ────────────
it('fails closed for a non-owner reaching owner analytics — empty, never someone else\'s data', function () {
    $s = seedScenario(1);
    $owner = ownerRoleUser($s, 0);   // a real owner with a unit + a booking
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);
    $s['units'][0]->update(['code' => 'SOMEONES-UNIT']);

    // ⚠️ R-GATE (founder, 2026-09-19): a back-office user with NO owner link is no longer shown an empty
    //    owner portal — they are sent to their OWN home, mirroring invariant #26 which bounces an owner out
    //    of /app. The scope still fails closed underneath; the gate simply refuses before it is consulted.
    $response = $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/owner/reports');
    $response->assertRedirect(route('app.dashboard'));

    // ⚠️ AND NOTHING LEAKED ON THE WAY OUT — the redirect body carries no owner data.
    expect($response->getContent())->not->toContain('SOMEONES-UNIT');
});

// ── 5. ⚠️ GROSS-TO-NET reconciles with the REAL agreement commission rate — never the wireframe's 15% ──
it('reports gross-to-net at the REAL agreement commission rate and never fabricates VAT', function () {
    $s = seedScenario(1);

    // An owner whose agreement commission is a DISTINCTIVE 22% — so "Commission · 22%" proves the rate is
    // READ FROM THE AGREEMENT, not the wireframe's hardcoded 15%.
    $user = User::factory()->create(['status' => 'active']);
    Membership::create(['organization_id' => $s['org']->id, 'user_id' => $user->id, 'status' => 'active']);
    $owner = Owner::factory()->create(['user_id' => $user->id, 'payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 22.00, 'effective_from' => '2026-01-01']);

    // Gross €300.00 room revenue − €66.00 commission (22%) → net €234.00 (all dated in this year).
    ledgerEntry($owner->id, $s['units'][0]->id, 'room_revenue', 'credit', 30000);
    ledgerEntry($owner->id, $s['units'][0]->id, 'commission', 'debit', 6600);

    $res = $this->actingAs($user)->get('/owner/reports?range=this-year')->assertOk()
        // the REAL agreement rate — read from the agreement, never the wireframe's hardcoded 15%
        ->assertSee('Commission · 22%')
        ->assertDontSee('Commission · 15%')
        // gross-to-net reconciles (#17): gross €300.00 − commission €66.00 = net €234.00
        ->assertSee('€300.00')
        ->assertSee('€234.00')
        // ⚠️ HONEST VAT — this owner isn't VAT-registered, so the real engine shows the honest state, no figure
        // (the computation is proven in OwnerTaxSurfaceTest; here we prove nothing is fabricated for a non-reg owner)
        ->assertSee('not marked VAT-registered')
        ->assertSee('Not yet available')            // the honest chip (no computed figure)
        ->assertDontSee('Output VAT')
        ->assertDontSee('Net VAT due')
        ->assertDontSee('Mark filed')
        ->assertDontSee('payable to')               // no "Net VAT due · payable to [authority]" (an owner could file on it)
        ->assertDontSee('VAT return');              // no numbers-bearing VAT-return CSV

    assertNoRawBlade($res);
});

// ── 6. The Reports EXPORT menu is CAP_EXPORTS-gated (a view-only sub-user may view, but not export) ──
it('gates the Reports export menu to CAP_EXPORTS', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);   // primary owner → CAP_EXPORTS
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    $this->actingAs($user)->get('/owner/reports')->assertOk()->assertSee('Export');

    // A view-only sub-user may VIEW Reports but gets NO export control (#42).
    $this->actingAs(ownerSubUser($s, $ownerId, 'view_only'))->get('/owner/reports')->assertOk()->assertDontSee('Export');
});

// ── 7. INSIGHTS — REAL derived observations + an HONEST rates link, never a fabricated rate proposal ──
it('shows Insights with real derived observations and an honest rates link — never a fake proposal', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);   // real bookings → real aggregates

    $res = $this->actingAs($user)->get('/owner/insights?range=this-year')->assertOk()
        ->assertSee('Occupancy by month')
        ->assertSee('Rate performance')
        ->assertSee('Worth a look')                 // REAL derived observations (decisions stay yours)
        // the honest "act on it" path is a REAL link to the owner Rates page — not a fabricated workflow
        ->assertSee('Turn insight into rate')
        ->assertSee('Open your Rates')
        ->assertSee(route('owner.rates'))
        // ⚠️ the design's fabricated rate-proposal entity is REFUSED (#39) — no proposal count / approval flow
        ->assertDontSee('proposals waiting')
        ->assertDontSee('Review proposals')
        ->assertDontSee('rate proposals');

    assertNoRawBlade($res);
});

// ── 7b. INSIGHTS — the vs-last-year occupancy delta is REAL, shown ONLY when prior-year data exists ──
it('shows a real vs-last-year occupancy delta only when the owner has prior-year data', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $unit = $s['units'][0];

    // A this-year stay with NO prior-year counterpart → the vs-last-year caption is honestly absent (#39).
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-03-01', '2026-03-12');
    twoNightStay($s, $unit->id, '2026-03-04', '2026-03-07');
    $this->actingAs($user)->get('/owner/insights?range=this-year')->assertOk()
        ->assertDontSee('versus the same month last year');

    // Add a REAL stay in the SAME month one year earlier → the delta + caption now render (own history).
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-03-01', '2025-03-12');
    twoNightStay($s, $unit->id, '2025-03-04', '2025-03-07');
    $this->actingAs($user)->get('/owner/insights?range=this-year')->assertOk()
        ->assertSee('versus the same month last year');
});

// ── 8. ✅ NET-BY-CHANNEL is REAL (ledger × reservation.source) and Σ(channels) == the period net (#17) ──
it('shows REAL net-by-channel that reconciles to the period net — never nights-only', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // Two real reservations on the owner's unit — two distinct channels (source is the REAL enum, not a brand).
    $direct = book($s, ['unit_id' => $s['units'][0]->id]);       // source=direct (the helper default)
    $ota = Reservation::create([
        'organization_id' => $s['property']->organization_id,
        'property_id' => $s['property']->id,
        'reference' => 'RES-OTA-TEST',
        'source' => 'ota',
        'status' => 'confirmed',
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-12',
        'currency' => 'EUR',
        'lead_guest_id' => $s['guest']->id,
    ]);

    // Direct net €240.00 (300 − 60) · OTA net €160.00 (200 − 40) → period net €400.00.
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 30000, (string) $direct->id);
    ledgerEntry($ownerId, $s['units'][0]->id, 'commission', 'debit', 6000, (string) $direct->id);
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 20000, (string) $ota->id);
    ledgerEntry($ownerId, $s['units'][0]->id, 'commission', 'debit', 4000, (string) $ota->id);

    $res = $this->actingAs($user)->get('/owner/reports?range=this-year')->assertOk()
        ->assertSee('net by channel')                // NET, not "room-nights by channel"
        ->assertSee('Direct')
        ->assertSee('OTA')                           // the SHARED source label (OwnerBookingsReader) — never an OTA brand (#39)
        ->assertSee('€240.00')                       // direct channel net
        ->assertSee('€160.00')                       // ota channel net
        ->assertSee('€400.00');                      // period net — Σ(channels) reconciles to the cent (#17)

    assertNoRawBlade($res);
});

// ── 9. ✅ The REPORTING-YEAR dropdown lists REAL statement years — never the wireframe's fabricated 2024/2023 ──
it('shows the real reporting-year dropdown, not fabricated prior years', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);

    $res = $this->actingAs($user)->get('/owner/reports')->assertOk()
        ->assertSee('Reporting year')
        ->assertSee('Earlier years live in Documents')
        // the current year is always offered, even before its first statement…
        ->assertSee((string) now()->year)
        ->assertSee('Year to date')
        ->assertSee('Last 6 months');

    // ⚠️ …and the wireframe's fabricated years are ABSENT from the reporting-year list. This negative was
    // missing: the test asserted only positives while its comment claimed "the fabricated years are gone",
    // so hardcoding the wireframe's four-year list left it GREEN. Asserted against the year option's own
    // GET key, not the bare year — a bare "2024" also appears in the custom-range MONTH picker, which is a
    // legitimate control, so the loose needle would fail on correct markup.
    $res->assertDontSee('year='.(now()->year - 2))
        ->assertDontSee('year='.(now()->year - 3));

    assertNoRawBlade($res);
});

// ── 10. ✅ The custom From/To date range clips the owner ledger to the window (a real GET, no write) ──
it('clips the report to a real custom date range', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // Sep net €300.00 (in the window) and Jun net €999.00 (outside it).
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 30000, null, '2026-09-01');
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 99900, null, '2026-06-01');

    $this->actingAs($user)->get('/owner/reports?from=2026-09&to=2026-09')->assertOk()
        ->assertSee('Sep 2026')          // the range label reflects the window
        ->assertSee('€300.00')           // September is inside the window
        ->assertDontSee('€999.00');      // June is clipped out
});

// ── A month that earned NOTHING draws NOTHING (honest zero) ───────────────────────────────────────
// Owner-25 measured the live chart and found five genuinely EUR0.00 months each rendering a visible
// bar: the height carried a two-percent floor, so the chart contradicted its own printed figure. The
// month AXIS was already contiguous (every month in the range is seeded, so an empty month keeps its
// slot) — it was only the bar that lied. The hairline below keeps a small non-zero month visible.
it('draws no bar at all for a month that earned nothing', function () {
    $s = seedScenario(1);
    $user = ownerRoleUser($s, 0);
    $ownerId = (string) $s['units'][0]->fresh()->owner_id;

    // ⚠️ REAL earnings in ONE month are required: with an empty ledger the chart does not render at all,
    // so the fixture could never reach the assertion (the empty-fixture trap). One earning month means the
    // rest of the calendar year is a genuine zero — exactly the case being guarded.
    ledgerEntry($ownerId, $s['units'][0]->id, 'room_revenue', 'credit', 30000);
    ledgerEntry($ownerId, $s['units'][0]->id, 'commission', 'debit', 4500);

    $html = $this->actingAs($user)->get('/owner/reports')->assertOk()->getContent();

    // The reporting year runs the full calendar, so months before the owner's first stay are real zeros.
    expect($html)->toContain('class="vy-rep-bar" style="height:0%"')
        // …and a zero bar carries no floor of any kind.
        ->and($html)->not->toContain('style="height:0%;min-height');
});
