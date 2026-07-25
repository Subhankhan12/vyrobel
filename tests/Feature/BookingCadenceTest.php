<?php

use App\Support\Analytics\BookingCadence;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Batch B · GATE 3 — the SHARED BookingCadence: lead-time distribution + median, day-of-week occupancy, and
// on-the-books revenue. EVERY figure is a real, deterministic aggregate from real created_at → check_in rows
// (#39) — one implementation read by BOTH operator and owner Insights (anti-drift).

it('computes real lead-time buckets + median from real created_at, and moves with the data (#39)', function () {
    $s = seedScenario(2);
    app(TenantContext::class)->set($s['org']->id);

    $b1 = book($s, ['unit_id' => $s['units'][0]->id]);   // check_in 2026-09-01 (header)
    $b2 = book($s, ['unit_id' => $s['units'][1]->id]);

    // Backdate created_at to KNOWN leads before check-in: b1 → 2 days (<1wk), b2 → 60 days (1–3 months).
    DB::table('reservations')->where('id', $b1->id)->update(['created_at' => '2026-08-30 10:00:00']);
    DB::table('reservations')->where('id', $b2->id)->update(['created_at' => '2026-07-03 10:00:00']);

    $start = CarbonImmutable::parse('2026-01-01');
    $end = CarbonImmutable::parse('2027-01-01');
    $lt = BookingCadence::leadTimeForProperties([(string) $s['property']->id], $start, $end);

    expect($lt['count'])->toBe(2)
        ->and($lt['median_days'])->toBe(31)                                   // (2 + 60) / 2 = 31, from real rows
        ->and($lt['buckets'][0])->toMatchArray(['label' => 'Under a week', 'count' => 1])
        ->and($lt['buckets'][2])->toMatchArray(['label' => '1–3 months', 'count' => 1]);

    // 🪤 #39 — the figure is REAL, not hardcoded: change a created_at and the distribution moves.
    DB::table('reservations')->where('id', $b2->id)->update(['created_at' => '2026-08-31 10:00:00']);   // now lead 1
    $lt2 = BookingCadence::leadTimeForProperties([(string) $s['property']->id], $start, $end);

    expect($lt2['buckets'][0]['count'])->toBe(2)                              // both bookings now < 1 week
        ->and($lt2['median_days'])->toBe(1);
});

it('has an honest empty state when there is no booking history (#39)', function () {
    $s = seedScenario(1);
    app(TenantContext::class)->set($s['org']->id);

    $start = CarbonImmutable::parse('2026-01-01');
    $end = CarbonImmutable::parse('2027-01-01');
    $lt = BookingCadence::leadTimeForProperties([(string) $s['property']->id], $start, $end);

    expect($lt['has_data'])->toBeFalse()
        ->and($lt['count'])->toBe(0)
        ->and($lt['median_days'])->toBeNull()                                 // no invented median
        ->and(collect($lt['buckets'])->sum('count'))->toBe(0);
});

it('computes real day-of-week occupancy + on-the-books revenue, owner scope matching property scope', function () {
    $s = seedScenario(2);
    app(TenantContext::class)->set($s['org']->id);

    book($s, ['unit_id' => $s['units'][0]->id]);   // 2026-09-01..03 (Tue+Wed), €100
    book($s, ['unit_id' => $s['units'][1]->id]);

    $start = CarbonImmutable::parse('2026-01-01');
    $end = CarbonImmutable::parse('2027-01-01');

    // DOW occupancy — real: the booked weekdays carry occupancy, the rest are 0 (never fabricated).
    $wd = BookingCadence::weekdayOccupancyForProperties([(string) $s['property']->id], $start, $end);
    expect($wd['has_data'])->toBeTrue()
        ->and($wd['weekdays'])->toHaveCount(7)
        ->and($wd['weekdays'][0]['label'])->toBe('Mon');   // Mon → Sun order

    // On the books — real forward-booked room revenue (the two €100 stays fall in the next 90 days).
    $unitIds = array_map(fn ($u): string => (string) $u->id, $s['units']);
    $otbProperty = BookingCadence::onTheBooksForProperties([(string) $s['property']->id], CarbonImmutable::parse('2026-08-01'), 90, 'EUR');
    $otbOwner = BookingCadence::onTheBooksForOwnerUnits($unitIds, CarbonImmutable::parse('2026-08-01'), 90, 'EUR');
    expect($otbProperty)->toBe(20000)          // 2 × €100, real
        ->and($otbOwner)->toBe($otbProperty);  // the two scopes agree over the same units (shared machinery)
});
