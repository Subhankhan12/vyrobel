<?php

use App\Models\Property;
use App\Models\RoomType;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Support\Property\PropertyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// PHASE 6b · SG2 — OPERATOR INSIGHTS + REPORTS. Real aggregates over the SG1 metric layer, scoped by the
// Phase-6a property context; permission-gated `reports.view`; NO fabricated history/narrative.

// ── 1. 🔒 PERMISSION — reports.view sees analytics; Front Desk is refused (mutation-proven) ─────────
it('gates Insights and Reports on reports.view — a manager sees them, Front Desk is refused', function () {
    $s = seedScenario(1);

    foreach (['/app/insights', '/app/reports'] as $url) {
        $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get($url)->assertOk();
        $this->actingAs(operatorUser($s, [], 'Front Desk'))->get($url)->assertForbidden();
    }
});

// ── 2. ✅ REAL METRICS — a known booking surfaces its real figures + real channel; NO invented history ─
it('shows real metrics and channel mix from real bookings, with no fabricated history', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 10000]);   // Sep 1–3 2026, direct, €100

    $res = $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/insights?range=this-year');

    $res->assertOk()
        ->assertSee('Occupancy')->assertSee('ADR')->assertSee('RevPAR')   // real metric cards
        ->assertSee('Direct')                                             // real channel mix (reservations.source)
        ->assertSee('2 of');                                              // real sold room-nights (2 nights)

    // 🪤 THE FABRICATION GUARD — none of the prototype's invented narrative is rendered. Distinctive phrases
    // only (a bare year like "2024" is too generic a needle — it collides with incidental ULIDs/wire-ids;
    // the "no invented multi-year history" is covered by the trend showing only real months, browser-verified).
    $res->assertDontSee('best September')->assertDontSee('on record')
        ->assertDontSee('pts behind');   // the prototype's invented "October is pacing 9 pts behind" narrative

    assertNoRawBlade($res);
});

// ── 3. 🪤 THIN/EMPTY — an honest empty state, never a fake curve ────────────────────────────────────
it('shows an honest empty state for a range with no data', function () {
    $s = seedScenario(1);   // no bookings at all

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/insights?range=6m')
        ->assertOk()
        ->assertSee('Not enough data');   // the honest thin-trend note, not an invented trend
});

// ── 4. 🪤 REPORTS — real revenue + real cost, untracked categories HONESTLY omitted (not fabricated) ─
it('reports real revenue and maintenance, and marks untracked cost categories honestly', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 10000]);

    $res = $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/reports?range=this-year');

    $res->assertOk()
        ->assertSee('Room')->assertSee('Gross revenue')            // real folio revenue breakdown
        ->assertSee('Maintenance')                                  // the one real cost line
        // The prototype's other cost lines have no data → listed as untracked, never a fabricated figure.
        ->assertSee('Channel commissions')->assertSee('Not tracked yet')
        ->assertSee('Top units')
        ->assertDontSee('best month');   // no fabricated "best month on record" narrative

    assertNoRawBlade($res);
});

// ── 5b. THE PORTFOLIO COMPARISON — real ADR/RevPAR/revenue via the metric layer (deferred from 6a) ──
it('shows a real Portfolio comparison reconciling to the metric layer', function () {
    $s = seedScenario(1);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 20000]);   // Sep 1–3, 2 nights → ADR €100.00

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/portfolio')
        ->assertOk()
        ->assertSee('This year, side by side')
        ->assertSee('€100.00');   // real ADR (€200 room revenue ÷ 2 room-nights), reconciling to SG1
});

// ── 5. 🔒 PER-PROPERTY SCOPE — Insights follows the selected property (the 6a context) ──────────────
it('scopes Insights to the selected property, not an arbitrary one', function () {
    $s = seedScenario(1);
    $s['roomType']->update(['name' => 'DataWing']);
    book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 10000]);

    // A second, EMPTY property in the same org.
    $empty = Property::create(['name' => 'Empty Property', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    RoomType::create(['property_id' => $empty->id, 'name' => 'QuietWing', 'kind' => 'room']);
    $user = operatorUser($s, [], 'Hotel Manager');

    // Selected = the booked property → shows its real sold room-nights (2 nights on the one stay).
    $this->actingAs($user)->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/insights?range=this-year')->assertOk()->assertSee('2 of');

    // Selected = the empty property → its own honest zero, not the other property's data.
    $this->actingAs($user)->withSession(['current_property_id' => $empty->id])
        ->get('/app/insights?range=this-year')->assertOk()->assertSee('0 of 0 room-nights');

    // "All properties" rolls both up → still the two real sold room-nights.
    $this->actingAs($user)->withSession(['current_property_id' => PropertyContext::ALL])
        ->get('/app/insights?range=this-year')->assertOk()->assertSee('2 of');
});

// ── #88 — vs-LAST-YEAR is REAL where prior-year data exists, honestly absent otherwise ──────────────
it('shows a real vs-last-year delta only where prior-year data exists', function () {
    $s = seedScenario(2);
    book($s, ['unit_id' => $s['units'][0]->id]);   // this-year stay (Sep 2026)

    // A real prior-year stay in the same month one year back → a genuine LY comparison to measure.
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-09-10');
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => '2025-09-01', 'check_out' => '2025-09-03', 'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][1]->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => '2025-09-01', 'check_out' => '2025-09-03', 'price_minor' => 10000, 'currency' => 'EUR']],
    );

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/insights?range=this-year')
        ->assertOk()
        ->assertSee('pts')          // the REAL occupancy vs-LY delta pill
        ->assertSee('Last year');   // the LY series legend appears only when prior-year data exists
});

// ── #88 — the wireframe-rebuilt Insights: real KPIs + day-of-week + channel mix + real-derived insights ──
it('renders the real booking-window KPI, day-of-week occupancy, channel mix and pace (no fabrication)', function () {
    $s = seedScenario(2);
    $b = book($s, ['unit_id' => $s['units'][0]->id, 'price_minor' => 10000]);   // arrives 2026-09-01
    // A real, backdated created_at → a real lead time to measure (production has genuine timestamps).
    DB::table('reservations')->where('id', $b->id)->update(['created_at' => '2026-08-18 10:00:00']);

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/insights?range=this-year')
        ->assertOk()
        ->assertSee('Portfolio insights')       // the wireframe title
        ->assertSee('Nights that fill first')    // the REAL day-of-week occupancy
        ->assertSee('Booking window')            // the REAL median-lead KPI
        ->assertSee('Channel mix')               // real sources
        ->assertSee('Pace by property')          // real forward pace
        ->assertSee('Worth a look')              // the real-derived insight section
        ->assertSee('decisions stay yours')      // the honest framing kept
        ->assertSee('Channel-level pace')        // the Expedia fabrication REFUSED → honest gated card
        ->assertDontSee('costing')               // no fabricated "Expedia costing €X"
        ->assertDontSee('Booking pace')          // the old "Soon" tile is gone
        ->assertDontSee('needs more booking history');
});
