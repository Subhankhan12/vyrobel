<?php

use App\Models\Reservation;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// THE SHARED STAGE RESOLVER — the one definition of In-house / Upcoming / Past, consumed by the
// Reservations list, the Guests CRM and the Guest profile (Wave 2; docs/DECISIONS.md 2026-07-16).
//
// The load-bearing property is not any single rule — it is that `for()` (the badge) and `scope()` (the SQL
// filter) can NEVER disagree. A filter chip that classifies differently from the badge it filters on is the
// drift this class exists to prevent, and test 1 proves it over EVERY (status × date) combination.

/**
 * Put a reservation at a known status + window.
 *
 * Each booking takes its OWN unit: book() defaults every stay to the same dates, so reusing one unit trips
 * the certified no-double-booking guard (which is correct — it just isn't what this file tests). The unit
 * index is passed by the caller; classification is about the row, not how it was booked.
 */
function stagedReservation(array $s, string $status, string $checkIn, string $checkOut, int $unitIndex = 0): Reservation
{
    $reservation = book($s, ['unit_id' => $s['units'][$unitIndex]->id]);

    // Set the terminal/derived state directly: AssignmentService guards real transitions, but this test is
    // about how a row IS classified, not how it got there.
    Reservation::query()->whereKey($reservation->getKey())->update([
        'status' => $status, 'check_in' => $checkIn, 'check_out' => $checkOut,
    ]);

    return $reservation->fresh();
}

// ── 1. THE HEADLINE — for() and scope() agree on EVERY status × date combination ──────────────────
it('classifies every status and date window identically in PHP and in SQL', function () {
    $s = seedScenario(28);   // one unit per row — see stagedReservation()
    $today = CarbonImmutable::parse('2026-06-15');

    // Every one of the 7 real enum values, across past / current / future windows.
    $windows = [
        'future' => ['2026-07-01', '2026-07-05'],
        'current' => ['2026-06-14', '2026-06-18'],   // spans today
        'past' => ['2026-05-01', '2026-05-05'],
        'ends_today' => ['2026-06-10', '2026-06-15'], // check_out == today → half-open ⇒ over
    ];
    $statuses = ['enquiry', 'tentative', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];

    $expected = [];   // reservation id → stage, per for()
    $unit = 0;

    foreach ($statuses as $status) {
        foreach ($windows as [$in, $out]) {
            $r = stagedReservation($s, $status, $in, $out, $unit++);
            $expected[$r->getKey()] = ReservationStage::for($r, $today);
        }
    }

    expect($expected)->toHaveCount(28);   // 7 statuses × 4 windows — full coverage

    // Now ask SQL the same question, stage by stage. Every row must land in exactly ONE stage, and it must
    // be the SAME stage for() gave it.
    $seen = [];
    foreach (ReservationStage::FILTERABLE as $stage) {
        $ids = ReservationStage::scope(Reservation::query(), $stage, $today)->pluck('id')->all();

        foreach ($ids as $id) {
            expect($expected[$id])->toBe($stage, "SQL put {$id} in [{$stage}] but for() says [{$expected[$id]}]");
            $seen[] = $id;
        }
    }

    // …and no row was missed or double-counted: the stages partition the set.
    expect(collect($seen)->duplicates())->toBeEmpty('a reservation matched more than one stage')
        ->and(collect($seen)->sort()->values()->all())->toBe(collect(array_keys($expected))->sort()->values()->all());
});

// ── 2. The locked rules (decision 2), stated as tests ─────────────────────────────────────────────
it('applies the locked stage rules over the real status enum', function () {
    $s = seedScenario(4);
    $today = CarbonImmutable::parse('2026-06-15');

    // In-house = checked in (the enum already means "not checked out").
    expect(ReservationStage::for(stagedReservation($s, 'checked_in', '2026-06-14', '2026-06-18', 0), $today))
        ->toBe(ReservationStage::IN_HOUSE);

    // Upcoming = confirmed AND check-in in the future.
    expect(ReservationStage::for(stagedReservation($s, 'confirmed', '2026-07-01', '2026-07-05', 1), $today))
        ->toBe(ReservationStage::UPCOMING);

    // Past = checked out…
    expect(ReservationStage::for(stagedReservation($s, 'checked_out', '2026-05-01', '2026-05-05', 2), $today))
        ->toBe(ReservationStage::PAST);

    // …OR the end date passed without a check-in (a stale confirmed booking is genuinely past).
    expect(ReservationStage::for(stagedReservation($s, 'confirmed', '2026-05-01', '2026-05-05', 3), $today))
        ->toBe(ReservationStage::PAST);
});

// ── 3. A cancelled FUTURE stay is NOT "past" — it never happened ──────────────────────────────────
// The design lumped cancelled in with checked_out; the inventory flagged it. This locks the distinction.
it('keeps cancelled and no-show terminal rather than folding them into past', function () {
    $s = seedScenario(4);
    $today = CarbonImmutable::parse('2026-06-15');

    $unit = 0;
    foreach (['cancelled', 'no_show'] as $terminal) {
        expect(ReservationStage::for(stagedReservation($s, $terminal, '2026-07-01', '2026-07-05', $unit++), $today))
            ->toBe(ReservationStage::CANCELLED)                       // a FUTURE cancelled stay
            ->and(ReservationStage::for(stagedReservation($s, $terminal, '2026-05-01', '2026-05-05', $unit++), $today))
            ->toBe(ReservationStage::CANCELLED);                      // and a past-dated one
    }
});

// ── 4. The gap the three headline buckets don't cover is named honestly, not folded ───────────────
it('classifies an arrival that came due but was never checked in as DUE, not upcoming/in-house/past', function () {
    $s = seedScenario(1);
    $today = CarbonImmutable::parse('2026-06-15');

    // Confirmed, arrival date reached, stay still live, nobody checked them in.
    $stage = ReservationStage::for(stagedReservation($s, 'confirmed', '2026-06-15', '2026-06-18'), $today);

    expect($stage)->toBe(ReservationStage::DUE)
        ->and($stage)->not->toBe(ReservationStage::UPCOMING)   // the date came
        ->and($stage)->not->toBe(ReservationStage::IN_HOUSE)   // they are not here
        ->and($stage)->not->toBe(ReservationStage::PAST);      // the stay is live
});

// ── 5. Half-open [check_in, check_out) — a stay ending TODAY is over ──────────────────────────────
it('treats a stay whose check_out is today as over (half-open window)', function () {
    $s = seedScenario(1);
    $today = CarbonImmutable::parse('2026-06-15');

    expect(ReservationStage::for(stagedReservation($s, 'confirmed', '2026-06-10', '2026-06-15'), $today))
        ->toBe(ReservationStage::PAST);
});

// ── 6. Labels key to real vocabulary — no invented status words ───────────────────────────────────
it('exposes warm labels for every filterable stage and never the design fictions', function () {
    $labels = [];
    foreach (ReservationStage::FILTERABLE as $stage) {
        $meta = ReservationStage::meta($stage);
        expect($meta)->toHaveKeys(['label', 'tone'])
            ->and($meta['label'])->not->toBeEmpty();
        $labels[] = $meta['label'];
    }

    // "Completed" / "In house" are the design's inventions — "Completed" is checked_out, and the real
    // spelling is "In-house". Neither fiction may appear.
    expect($labels)->not->toContain('Completed')
        ->and($labels)->not->toContain('In house')
        ->and($labels)->toContain('In-house')
        ->and($labels)->toContain('Upcoming')
        ->and($labels)->toContain('Past');
});
