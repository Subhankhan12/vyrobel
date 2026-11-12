<?php

declare(strict_types=1);

use App\Livewire\Owner\OwnerInsights;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Review;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Support\Analytics\BookingCadence;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Owner\OwnerOccupancy;
use App\Support\Owner\OwnerOccupancyTrend;
use App\Support\Reviews\OwnerReviewSummary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

// ⚠️⚠️ D-OWNERSINCE-WINDOWS (found 2026-11-12 by the three-decisions pass, fixed 2026-11-12). DEC-p lets an operator give an owner a unit FROM A DAY
// (`units.owner_since`). The MONEY followed it from the start — earnings, the new-booking notice, a repair's cost — but every owner-facing FIGURE and
// SCREEN still read the unit's whole history: an owner given a unit mid-month saw occupancy for nights before their day (with a denominator that
// counted them too), revenue for stays that were never theirs, and the guests' names on the Block Calendar. The rule, everywhere: a stay that STARTS
// before `owner_since` is not the owner's; the nights before it were not theirs to sell. A unit with no `owner_since` is read exactly as before.
//
// The world: unit U1 is the owner's from 5 Sep 2026. "Early Guest" stays 2–4 Sep (before — the operator's), "Late Guest" stays 6–8 Sep (after — theirs).
// Every test keeps its CONTROL (no `owner_since` → both stays, every night) so a cut that over-reaches is as red as one that under-reaches.

/**
 * @return array<string, mixed>
 */
function oswWorld(?string $since = '2026-09-05'): array
{
    $s = seedScenario(2);
    $unit = $s['units'][0];
    $owner = ownUnit($unit);
    if ($since !== null) {
        $unit->update(['owner_since' => $since]);
    }

    $early = Guest::create(['first_name' => 'Early', 'last_name' => 'Guest']);
    $late = Guest::create(['first_name' => 'Late', 'last_name' => 'Guest']);

    return $s + [
        'unit' => $unit, 'owner' => $owner, 'actor' => ownerActor($owner, $s),
        'before' => oswBook($s, $unit, $early, '2026-09-02', '2026-09-04', 20000),
        'after' => oswBook($s, $unit, $late, '2026-09-06', '2026-09-08', 30000),
    ];
}

function oswBook(array $s, $unit, Guest $guest, string $in, string $out, int $priceMinor): Reservation
{
    $reservation = app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $in, 'check_out' => $out, 'lead_guest_id' => $guest->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $in, 'check_out' => $out,
            'price_minor' => $priceMinor, 'currency' => 'EUR', 'unit_id' => $unit->id]],
    );
    postRoom($reservation, $priceMinor);

    return $reservation->refresh();
}

// ── osw01 · THE SHARED FIGURES (PerformanceMetrics::forOwnerUnits → Reports, Insights, My Units, the statement, the PDFs) ────────────────────────
it('osw01 the owner\'s figures count only the stays that began on or after their day, over only the nights they had', function () {
    $s = oswWorld();
    $m = PerformanceMetrics::forOwnerUnits([(string) $s['unit']->id], CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-10'), 'EUR');

    expect($m->roomNightsSold)->toBe(2)                 // Late Guest's two nights — Early Guest's are the operator's
        ->and($m->roomRevenueMinor)->toBe(30000)
        ->and($m->availableRoomNights)->toBe(5)         // 5–9 Sep: the nights the owner HAD, not all nine
        ->and($m->bookings)->toBe(1)
        ->and($m->occupancyPct())->toBe(40);          // 2 of 5, not 4 of 9
});

it('osw01 CONTROL: a unit with no owner_since reads its whole history, exactly as before', function () {
    $s = oswWorld(null);
    $m = PerformanceMetrics::forOwnerUnits([(string) $s['unit']->id], CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-10'), 'EUR');

    expect($m->roomNightsSold)->toBe(4)->and($m->roomRevenueMinor)->toBe(50000)->and($m->availableRoomNights)->toBe(9)->and($m->bookings)->toBe(2);
});

it('osw01 a period that ends before the day has no nights and no figure — never a division by zero', function () {
    $s = oswWorld();
    $m = PerformanceMetrics::forOwnerUnits([(string) $s['unit']->id], CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-09-05'), 'EUR');

    expect($m->roomNightsSold)->toBe(0)->and($m->availableRoomNights)->toBe(0)->and($m->occupancyPct())->toBe(0);
});

// ── osw02 · THE MONTH GRID (OwnerOccupancy → My Units, Unit Detail, the trend, the CSV) ─────────────────────────────────────────────────────────
it('osw02 the month\'s occupancy is the nights booked over the nights held — and the grid knows how many units were the owner\'s each day', function () {
    $s = oswWorld();
    $occ = OwnerOccupancy::forMonth([(string) $s['unit']->id], Carbon::parse('2026-09-01'));
    $id = (string) $s['unit']->id;

    expect($occ['nights'])->toBe(2)                         // not 4
        ->and($occ['available'])->toBe(26)                  // 5–30 Sep, not 30
        ->and($occ['per_unit_available'][$id])->toBe(26)
        ->and($occ['pct'])->toBe(8)                         // 2 of 26 (7.7%), not 4 of 30
        ->and($occ['heat'][2])->toBe(0)->and($occ['heat'][3])->toBe(0)->and($occ['heat'][6])->toBe(1)
        ->and($occ['held'][4])->toBe(0)->and($occ['held'][5])->toBe(1)->and($occ['held'][30])->toBe(1);

    // the Overview / Statement trend's per-unit % is over the same nights
    expect(OwnerOccupancyTrend::ending([$id], Carbon::parse('2026-09-01'))['per_unit_pct'][$id])->toBe(8);
});

it('osw02 CONTROL: with no owner_since the month reads every stay over every night, as before', function () {
    $s = oswWorld(null);
    $occ = OwnerOccupancy::forMonth([(string) $s['unit']->id], Carbon::parse('2026-09-01'));

    expect($occ['nights'])->toBe(4)->and($occ['available'])->toBe(30)->and($occ['pct'])->toBe(13)
        ->and($occ['held'][1])->toBe(1)->and($occ['heat'][2])->toBe(1)
        ->and(OwnerOccupancyTrend::ending([(string) $s['unit']->id], Carbon::parse('2026-09-01'))['per_unit_pct'][(string) $s['unit']->id])->toBe(13);
});

// ── osw03 · THE BOOKING PAGES (a stay before the owner's day is not theirs: no page, no confirmation) ─────────────────────────────────────────────
it('osw03 a booking from before the owner\'s day is a 404 — page and confirmation — and one from after it opens', function () {
    $s = oswWorld();

    test()->actingAs($s['actor'])->get(route('owner.bookings.show', $s['before']->id))->assertNotFound();
    test()->actingAs($s['actor'])->get(route('owner.bookings.confirmation', $s['before']->id))->assertNotFound();

    // the PERMITTED counterpart: the same owner, the stay that began on their day
    test()->actingAs($s['actor'])->get(route('owner.bookings.show', $s['after']->id))->assertOk()->assertSee($s['after']->reference);
    test()->actingAs($s['actor'])->get(route('owner.bookings.confirmation', $s['after']->id))->assertOk();
});

it('osw03 a guest\'s earlier stay from before the owner\'s day does not make them a "repeat guest" to the owner', function () {
    $s = oswWorld();
    // Late Guest also stayed on 1 Sep — the night before the operator's Early Guest, and before the owner's day
    $lateGuest = Guest::query()->findOrFail($s['after']->lead_guest_id);
    oswBook($s, $s['unit'], $lateGuest, '2026-09-01', '2026-09-02', 10000);

    test()->actingAs($s['actor'])->get(route('owner.bookings.show', $s['after']->id))->assertOk()
        ->assertSee('New guest')->assertDontSee('Repeat guest');
});

it('osw03 CONTROL: with no owner_since that same guest IS a repeat guest', function () {
    $s = oswWorld(null);
    $lateGuest = Guest::query()->findOrFail($s['after']->lead_guest_id);
    oswBook($s, $s['unit'], $lateGuest, '2026-09-01', '2026-09-02', 10000);

    test()->actingAs($s['actor'])->get(route('owner.bookings.show', $s['after']->id))->assertOk()->assertSee('Repeat guest');
});

it('osw03 CONTROL: with no owner_since both stays open', function () {
    $s = oswWorld(null);

    test()->actingAs($s['actor'])->get(route('owner.bookings.show', $s['before']->id))->assertOk();
    test()->actingAs($s['actor'])->get(route('owner.bookings.confirmation', $s['before']->id))->assertOk();
});

// ── osw04 · THE BLOCK CALENDAR (a guest's NAME from before the owner's day is not theirs to read, #28) ────────────────────────────────────────
it('osw04 the Block Calendar names the guest of a stay from the owner\'s day on, and not one from before it', function () {
    $s = oswWorld();
    $html = test()->actingAs($s['actor'])->get(route('owner.calendar', ['month' => '2026-09']))->assertOk()->getContent();

    expect($html)->toContain('Late Guest')->not->toContain('Early Guest');
});

it('osw04 CONTROL: with no owner_since the calendar names both', function () {
    $s = oswWorld(null);
    $html = test()->actingAs($s['actor'])->get(route('owner.calendar', ['month' => '2026-09']))->assertOk()->getContent();

    expect($html)->toContain('Late Guest')->toContain('Early Guest');
});

// ── osw05 · UNIT DETAIL (its calendar, its day detail, its bookings list) and the MY UNITS CSV ───────────────────────────────────────────────────
it('osw05 Unit Detail lists only the stays from the owner\'s day on', function () {
    $s = oswWorld();
    $html = test()->actingAs($s['actor'])->get(route('owner.units.show', [$s['unit']->id, 'month' => '2026-09']))->assertOk()->getContent();

    // the stay list AND the calendar's day detail (which carries the guest's name) — Early Guest's stay is the operator's
    expect($html)->toContain($s['after']->reference)->not->toContain($s['before']->reference)
        ->and($html)->toContain('Late Guest')->not->toContain('Early Guest');
});

it('osw05 CONTROL: with no owner_since Unit Detail lists both', function () {
    $s = oswWorld(null);
    $html = test()->actingAs($s['actor'])->get(route('owner.units.show', [$s['unit']->id, 'month' => '2026-09']))->assertOk()->getContent();

    expect($html)->toContain($s['after']->reference)->toContain($s['before']->reference)
        ->and($html)->toContain('Late Guest')->toContain('Early Guest');
});

it('osw05 the My Units CSV counts, per day, the units the owner HAD — none before their day — and books only their stays', function () {
    $s = oswWorld();
    $csv = test()->actingAs($s['actor'])->get(route('owner.units.export', ['month' => '2026-09']))->assertOk()->streamedContent();

    expect($csv)->toContain('2026-09-02,0,0,0%')        // the operator's guest, before the owner's day: no night, no unit held
        ->and($csv)->toContain('2026-09-04,0,0,0%')
        ->and($csv)->toContain('2026-09-05,0,1,0%')     // the day it became theirs: held, nobody in it
        ->and($csv)->toContain('2026-09-06,1,1,100%');  // their guest
});

// ── osw06 · THE ANALYTICS (BookingCadence — lead time and the weekday grid) ────────────────────────────────────────────────────────────────────
it('osw06 lead time counts only the owner\'s stays, and the weekday grid is over the nights the owner had', function () {
    $s = oswWorld();
    $id = (string) $s['unit']->id;
    $start = CarbonImmutable::parse('2026-08-24');   // reaches back over the weekdays before the owner's day (5 Sep)
    $end = CarbonImmutable::parse('2026-09-10');

    expect(BookingCadence::leadTimeForOwnerUnits([$id], $start, $end)['count'])->toBe(1);   // not 2

    // Mon → Sun. Wed 2 Sep and Thu 3 Sep are Early Guest's nights — before the owner's day: nothing booked, and no night held to count it against.
    // Sun 6 Sep and Mon 7 Sep are Late Guest's: the ONLY Sunday / Monday the owner has (30 Aug, 24 and 31 Aug were not theirs to sell) → 100%, not 50% / 33%.
    $pcts = array_column(BookingCadence::weekdayOccupancyForOwnerUnits([$id], $start, $end)['weekdays'], 'pct');
    expect($pcts[2])->toBe(0)->and($pcts[3])->toBe(0)
        ->and($pcts[6])->toBe(100)->and($pcts[0])->toBe(100);
});

it('osw06 CONTROL: with no owner_since lead time counts both and every weekday is over every date', function () {
    $s = oswWorld(null);
    $id = (string) $s['unit']->id;
    $start = CarbonImmutable::parse('2026-08-24');
    $end = CarbonImmutable::parse('2026-09-10');

    expect(BookingCadence::leadTimeForOwnerUnits([$id], $start, $end)['count'])->toBe(2);
    $pcts = array_column(BookingCadence::weekdayOccupancyForOwnerUnits([$id], $start, $end)['weekdays'], 'pct');
    expect($pcts[2])->toBe(33)->and($pcts[3])->toBe(50)      // Wed 26 Aug, 2 Sep, 9 Sep (one night booked); Thu 27 Aug, 3 Sep (one booked)
        ->and($pcts[6])->toBe(50)->and($pcts[0])->toBe(33);  // Sun 30 Aug + 6 Sep; Mon 24 + 31 Aug + 7 Sep
});

// ── osw07 · REVIEWS (a stay before the owner's day, its rating and its guest's first name, are not theirs) ──────────────────────────────────────
it('osw07 the owner\'s Reviews show the review of a stay from their day on, and not one from before it', function () {
    $s = oswWorld();
    foreach ([['before', 'An early stay review.', 2], ['after', 'A late stay review.', 5]] as [$key, $body, $rating]) {
        Review::create([
            'guest_id' => $s[$key]->lead_guest_id, 'reservation_id' => $s[$key]->getKey(),
            'unit_id' => $s['unit']->getKey(), 'room_type_id' => $s['unit']->room_type_id,
            'rating' => $rating, 'tags' => [], 'body' => $body,
            'visibility' => 'public', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    $html = test()->actingAs($s['actor'])->get(route('owner.reviews'))->assertOk()->getContent();
    expect($html)->toContain('A late stay review.')->not->toContain('An early stay review.');

    // the summary's count and average are the owner's reviews only — one review, rated 5, not two averaging 3.5
    $summary = OwnerReviewSummary::forUnits([(string) $s['unit']->id]);
    expect($summary['count'])->toBe(1)->and($summary['average'])->toBe(5.0);
});

it('osw07 CONTROL: with no owner_since the owner reads both reviews', function () {
    $s = oswWorld(null);
    foreach ([['before', 'An early stay review.', 2], ['after', 'A late stay review.', 5]] as [$key, $body, $rating]) {
        Review::create([
            'guest_id' => $s[$key]->lead_guest_id, 'reservation_id' => $s[$key]->getKey(),
            'unit_id' => $s['unit']->getKey(), 'room_type_id' => $s['unit']->room_type_id,
            'rating' => $rating, 'tags' => [], 'body' => $body,
            'visibility' => 'public', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    $html = test()->actingAs($s['actor'])->get(route('owner.reviews'))->assertOk()->getContent();
    expect($html)->toContain('A late stay review.')->toContain('An early stay review.');
});

// ── osw08 · MY UNITS (the month's day detail and the unit's own %) ──────────────────────────────────────────────────────────────────────────────
it('osw08 My Units reads the owner\'s stays only — the day detail names Late Guest, not Early Guest — and the unit\'s % is over the nights it was theirs', function () {
    $s = oswWorld();
    $html = test()->actingAs($s['actor'])->get(route('owner.units', ['month' => '2026-09']))->assertOk()->getContent();

    expect($html)->toContain('Late Guest')->not->toContain('Early Guest')
        ->and($html)->toContain('font-size:13px;font-weight:500;font-variant-numeric:tabular-nums">8%</span>');   // the unit's own %: 2 nights of the 26 the owner had — not 2 of 30 (7%) and not 4 of 30 (13%)
});

it('osw08 CONTROL: with no owner_since My Units names both and reads 4 of 30', function () {
    $s = oswWorld(null);
    $html = test()->actingAs($s['actor'])->get(route('owner.units', ['month' => '2026-09']))->assertOk()->getContent();

    expect($html)->toContain('Late Guest')->toContain('Early Guest')->toContain('font-size:13px;font-weight:500;font-variant-numeric:tabular-nums">13%</span>');
});

// ── osw09 · UNIT DETAIL's calendar counts and its default month ─────────────────────────────────────────────────────────────────────────────
it('osw09 Unit Detail\'s calendar counts only the nights that were the owner\'s as booked', function () {
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow('2026-09-01 09:00:00');   // the calendar greys out days already past, so "today" sits before the stays
        $s = oswWorld();
        $html = test()->actingAs($s['actor'])->get(route('owner.units.show', [$s['unit']->id, 'month' => '2026-09']))->assertOk()->getContent();

        expect($html)->toContain('2 booked ·')->not->toContain('4 booked ·');    // Late Guest's two nights; Early Guest's two were the operator's
    } finally {
        Carbon::setTestNow($previous);
    }
});

it('osw09 CONTROL: with no owner_since the same calendar counts all four nights', function () {
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow('2026-09-01 09:00:00');
        $s = oswWorld(null);
        $html = test()->actingAs($s['actor'])->get(route('owner.units.show', [$s['unit']->id, 'month' => '2026-09']))->assertOk()->getContent();

        expect($html)->toContain('4 booked ·');
    } finally {
        Carbon::setTestNow($previous);
    }
});

it('osw09 Unit Detail opens on the month of the owner\'s latest stay — never on an operator stay from before their day', function () {
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow('2026-11-12 09:00:00');
        $s = oswWorld();
        app(AssignmentService::class)->cancelReservation($s['after']);   // the owner has no stay of their own now; only Early Guest's remains

        // no `month` in the address: the default month is the owner's latest stay, and there is none — so it is the current month, not Early Guest's September
        $html = test()->actingAs($s['actor'])->get(route('owner.units.show', $s['unit']->id))->assertOk()->getContent();
        expect($html)->toContain('November 2026')->not->toContain('September 2026');
    } finally {
        Carbon::setTestNow($previous);
    }
});

it('osw09 CONTROL: with no owner_since it opens on the month of the latest stay, as before', function () {
    $previous = Carbon::getTestNow();
    try {
        Carbon::setTestNow('2026-11-12 09:00:00');
        $s = oswWorld(null);
        app(AssignmentService::class)->cancelReservation($s['after']);

        $html = test()->actingAs($s['actor'])->get(route('owner.units.show', $s['unit']->id))->assertOk()->getContent();
        expect($html)->toContain('September 2026');
    } finally {
        Carbon::setTestNow($previous);
    }
});

// ── osw10 · INSIGHTS' "busiest night" observation ───────────────────────────────────────────────────────────────────────────────────────────
function oswBusiestNight(array $s): ?string
{
    $component = new OwnerInsights;
    $method = new ReflectionMethod($component, 'busiestWeekday');

    return $method->invoke($component, [(string) $s['unit']->id], ['start' => CarbonImmutable::parse('2026-08-01'), 'end' => CarbonImmutable::parse('2026-10-01')]);
}

it('osw10 Insights does not claim a busiest night from the operator\'s stays before the owner\'s day', function () {
    $s = oswWorld('2026-09-20');   // the owner has the unit from 20 Sep: every stay below is the operator's
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-08-01', '2026-09-30');
    foreach (['2026-08-14', '2026-08-21', '2026-08-28', '2026-09-11', '2026-09-18'] as $friday) {   // five more Fridays (4 Sep is free too, but 5 is enough)
        oswBook($s, $s['unit'], $s['guest'], $friday, date('Y-m-d', strtotime($friday.' +1 day')), 10000);
    }

    expect(oswBusiestNight($s))->toBeNull();      // nothing of this is the owner's → no pattern to name
});

it('osw10 CONTROL: with no owner_since the same stays name Friday', function () {
    $s = oswWorld(null);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-08-01', '2026-09-30');
    foreach (['2026-08-14', '2026-08-21', '2026-08-28', '2026-09-11', '2026-09-18'] as $friday) {
        oswBook($s, $s['unit'], $s['guest'], $friday, date('Y-m-d', strtotime($friday.' +1 day')), 10000);
    }

    expect(oswBusiestNight($s))->toContain('Friday');
});
