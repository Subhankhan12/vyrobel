<?php

use App\Models\Owner;
use App\Services\AssignmentService;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// PHASE 6b · SG1 — THE SHARED METRIC LAYER, proven by RECONCILIATION against hand-calculated scenarios.
//
// Every figure is REAL — derived from real reservation_units / availability, never fabricated. The proof is
// that a KNOWN booking produces KNOWN metrics, and the identity RevPAR = ADR × occupancy holds exactly.

/** Book one unit line with explicit dates/price through the certified single write path. */
function bookLine(array $s, $unit, string $checkIn, string $checkOut, int $priceMinor, string $source = 'direct')
{
    return app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $checkIn, 'check_out' => $checkOut, 'lead_guest_id' => $s['guest']->id, 'source' => $source],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'unit_id' => $unit->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => $priceMinor, 'currency' => 'EUR']],
    );
}

$sep = fn (int $d): CarbonImmutable => CarbonImmutable::parse(sprintf('2026-09-%02d', $d));

// ── 1. 🪤 RECONCILIATION — a fully-in-range stay yields the exact hand-calculated metrics ───────────
it('computes ADR / occupancy / RevPAR / revenue that reconcile to a hand-calculated booking', function () use ($sep) {
    $s = seedScenario(2);   // 1 property · 1 room type · 2 units · availability published Sep 1–10 (total = 2/night)
    bookLine($s, $s['units'][0], '2026-09-01', '2026-09-04', 30000);   // 3 nights @ €100/night, fully inside [Sep1, Sep8)

    $m = PerformanceMetrics::forProperties([$s['property']->id], $sep(1), $sep(8), 'EUR');

    // Available room-nights = 2 units × 7 dates (Sep 1–7) = 14. Sold = 3. Room revenue = €300.00 (full stay in range).
    expect($m->availableRoomNights)->toBe(14)
        ->and($m->roomNightsSold)->toBe(3)
        ->and($m->roomRevenueMinor)->toBe(30000)
        ->and($m->occupancyPct())->toBe(21)                 // round(3/14 × 100)
        ->and($m->adrMinor())->toBe(10000)                  // 30000 ÷ 3
        ->and($m->revparMinor())->toBe(2143);               // round(30000 ÷ 14)

    // THE IDENTITY, exactly: RevPAR = ADR × occupancy = roomRevenue ÷ availableRoomNights.
    expect($m->roomRevenueMinor)->toBe($m->adrMinor() * $m->roomNightsSold)              // ADR × RN = RR
        ->and($m->revparMinor())->toBe((int) round($m->roomRevenueMinor / $m->availableRoomNights));
});

// ── 2. 🪤 BOUNDARY PRO-RATA — a stay straddling the range edge contributes only its in-range nights ─
it('pro-rates a boundary-straddling stay to its in-range nights only', function () use ($sep) {
    $s = seedScenario(2);
    bookLine($s, $s['units'][0], '2026-09-06', '2026-09-09', 30000);   // 3 nights; range [Sep1, Sep8) covers Sep6,7 only

    $m = PerformanceMetrics::forProperties([$s['property']->id], $sep(1), $sep(8), 'EUR');

    // In-range nights = 2 of 3 → revenue = 30000 × 2/3 = 20000 (honest partial, not the full charge).
    expect($m->roomNightsSold)->toBe(2)
        ->and($m->roomRevenueMinor)->toBe(20000)
        ->and($m->adrMinor())->toBe(10000);
});

// ── 3. 🪤 EMPTY RANGE — no activity → an honest zero, never a fabricated curve ──────────────────────
it('returns an honest empty metric set for a range with no data', function () use ($sep) {
    $s = seedScenario(2);
    bookLine($s, $s['units'][0], '2026-09-01', '2026-09-04', 30000);

    // A range with no published availability and no stays (Sep 20–25 was never seeded).
    $m = PerformanceMetrics::forProperties([$s['property']->id], $sep(20), $sep(25), 'EUR');

    expect($m->isEmpty())->toBeTrue()
        ->and($m->roomNightsSold)->toBe(0)
        ->and($m->availableRoomNights)->toBe(0)
        ->and($m->roomRevenueMinor)->toBe(0)
        ->and($m->occupancyPct())->toBe(0)
        ->and($m->adrMinor())->toBe(0);
});

// ── 4. 🔒 OWNER SCOPE — an owner's metrics cover THEIR units only (mutation-proven isolation) ───────
it('scopes metrics to an owner\'s own units — owner A sees the booking, owner B sees zero', function () use ($sep) {
    $s = seedScenario(2);
    $ownerA = Owner::factory()->create(['payout_currency' => 'EUR']);
    $ownerB = Owner::factory()->create(['payout_currency' => 'EUR']);
    $s['units'][0]->update(['owner_id' => $ownerA->id]);
    $s['units'][1]->update(['owner_id' => $ownerB->id]);

    bookLine($s, $s['units'][0], '2026-09-01', '2026-09-04', 30000);   // on owner A's unit only

    $a = PerformanceMetrics::forOwnerUnits([$s['units'][0]->id], $sep(1), $sep(8), 'EUR');
    $b = PerformanceMetrics::forOwnerUnits([$s['units'][1]->id], $sep(1), $sep(8), 'EUR');

    // Owner A: 1 unit × 7 days = 7 available, 3 sold, €300 room revenue.
    expect($a->availableRoomNights)->toBe(7)
        ->and($a->roomNightsSold)->toBe(3)
        ->and($a->roomRevenueMinor)->toBe(30000)
        ->and($a->occupancyPct())->toBe(43);               // round(3/7 × 100)

    // Owner B: the SAME room type, but their unit had no booking → zero. B never sees A's data.
    expect($b->roomNightsSold)->toBe(0)
        ->and($b->roomRevenueMinor)->toBe(0)
        ->and($b->occupancyPct())->toBe(0)
        ->and($b->availableRoomNights)->toBe(7);
});

// ── 5. CHANNEL MIX + CANCELLATION — real source split + a real cancellation rate ───────────────────
it('derives a real channel mix and cancellation rate from the bookings', function () use ($sep) {
    $s = seedScenario(2);
    bookLine($s, $s['units'][0], '2026-09-01', '2026-09-03', 20000, 'direct');   // 2 nights direct
    bookLine($s, $s['units'][1], '2026-09-01', '2026-09-03', 20000, 'ota');      // 2 nights OTA
    $cancelled = bookLine($s, $s['units'][0], '2026-09-04', '2026-09-06', 20000, 'direct');
    app(AssignmentService::class)->cancelReservation($cancelled, 'guest request');

    $m = PerformanceMetrics::forProperties([$s['property']->id], $sep(1), $sep(8), 'EUR');

    // Channel mix (room-nights by real source) — only the two active stays, never the cancelled one.
    expect($m->sourceNights)->toBe(['direct' => 2, 'ota' => 2])
        ->and($m->roomNightsSold)->toBe(4);

    // Arrivals in range: 2 active + 1 cancelled → cancellation rate = round(1/3 × 100) = 33%.
    expect($m->bookings)->toBe(2)
        ->and($m->cancellations)->toBe(1)
        ->and($m->cancellationRatePct())->toBe(33);
});
