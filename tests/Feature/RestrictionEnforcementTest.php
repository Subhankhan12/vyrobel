<?php

declare(strict_types=1);

use App\Exceptions\Booking\RestrictionViolationException;
use App\Models\Reservation;
use App\Models\Restriction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ RESTRICTIONS ARE ENFORCED AT BOOKING. They were written, stored, pushed and BADGED, and read by
 * nothing — so an operator saw "7n min" and a one-night stay booked straight through it.
 *
 * ⚠️⚠️ CTA AND CTD ARE MIRRORS AND THE EASIEST TO INVERT. Each is proven BOTH ways below: the forbidden case
 * refused AND the pass-through case permitted. A refusal-only test cannot tell a correct implementation from
 * an inverted one.
 */
function restrict(array $s, string $date, array $flags): void
{
    Restriction::query()->updateOrCreate(
        [
            'room_type_id' => (string) $s['roomType']->id,
            'rate_plan_id' => null,
            'date' => $date,
        ],
        $flags + ['organization_id' => (string) $s['org']->id],
    );
}

function bookNights(array $s, string $in, string $out): Reservation
{
    return book($s, ['unit_id' => $s['units'][0]->id, 'check_in' => $in, 'check_out' => $out]);
}

it('⚠️ ZERO MOVEMENT — with no restriction set, a booking that works today still works', function () {
    $s = seedScenario(1);
    $r = bookNights($s, '2026-09-01', '2026-09-02');   // one night, nothing set

    expect($r->exists)->toBeTrue();
});

it('MIN_STAY — a stay shorter than the arrival date’s minimum is REFUSED', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-01', ['min_stay' => 7]);

    expect(fn () => bookNights($s, '2026-09-01', '2026-09-02'))
        ->toThrow(RestrictionViolationException::class);
});

it('MIN_STAY — a stay that MEETS the minimum still books', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-01', ['min_stay' => 3]);

    expect(bookNights($s, '2026-09-01', '2026-09-04')->exists)->toBeTrue();
});

it('⚠️ MIN_STAY is judged on the ARRIVAL date — a minimum on a LATER night does not govern the stay', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-03', ['min_stay' => 7]);   // mid-stay, not the arrival

    expect(bookNights($s, '2026-09-01', '2026-09-05')->exists)->toBeTrue();
});

it('STOP_SELL — a stay spanning a stopped night is REFUSED, even mid-stay', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-03', ['stop_sell' => true]);

    expect(fn () => bookNights($s, '2026-09-01', '2026-09-05'))
        ->toThrow(RestrictionViolationException::class);
});

it('⚠️ STOP_SELL on the DEPARTURE date does not block — nobody sleeps that night', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-05', ['stop_sell' => true]);

    expect(bookNights($s, '2026-09-01', '2026-09-05')->exists)->toBeTrue();
});

it('⚠️⚠️ CTA — REFUSES a stay STARTING on that date', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-01', ['closed_to_arrival' => true]);

    expect(fn () => bookNights($s, '2026-09-01', '2026-09-04'))
        ->toThrow(RestrictionViolationException::class);
});

it('⚠️⚠️ CTA — PERMITS a stay that merely PASSES THROUGH that date (the inversion proof)', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-03', ['closed_to_arrival' => true]);

    // Arrives the 1st, sleeps through the 3rd. CTA must not touch it.
    expect(bookNights($s, '2026-09-01', '2026-09-05')->exists)->toBeTrue();
});

it('⚠️⚠️ CTD — REFUSES a stay ENDING on that date', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-05', ['closed_to_departure' => true]);

    expect(fn () => bookNights($s, '2026-09-01', '2026-09-05'))
        ->toThrow(RestrictionViolationException::class);
});

it('⚠️⚠️ CTD — PERMITS a stay that merely PASSES THROUGH that date (the inversion proof)', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-03', ['closed_to_departure' => true]);

    // Sleeps through the 3rd and departs the 5th. CTD must not touch it.
    expect(bookNights($s, '2026-09-01', '2026-09-05')->exists)->toBeTrue();
});

it('a stay satisfying EVERY restriction still books — not over-blocked', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-01', ['min_stay' => 2, 'closed_to_departure' => true]);
    restrict($s, '2026-09-04', ['closed_to_arrival' => true]);

    expect(bookNights($s, '2026-09-01', '2026-09-04')->exists)->toBeTrue();
});

it('⚠️ NO BACK-FILL — a restriction added AFTER a booking does not invalidate it', function () {
    $s = seedScenario(1);
    $r = bookNights($s, '2026-09-01', '2026-09-02');

    restrict($s, '2026-09-01', ['min_stay' => 7, 'stop_sell' => true]);

    // ⚠️ Nothing re-validates. A retroactive refusal would cancel real stays.
    expect($r->refresh()->status)->toBe('confirmed');
});

it('⚠️ THE REFUSAL IS ACTIONABLE — it names the restriction, the date and the value', function () {
    $s = seedScenario(1);
    restrict($s, '2026-09-01', ['min_stay' => 7]);

    try {
        bookNights($s, '2026-09-01', '2026-09-02');
        $this->fail('the booking should have been refused');
    } catch (RestrictionViolationException $e) {
        expect($e->restriction)->toBe('min_stay')
            ->and($e->date)->toBe('2026-09-01')
            ->and($e->value)->toBe(7)
            ->and($e->getMessage())->toContain('2026-09-01')
            ->and($e->getMessage())->toContain('7');
    }
});
