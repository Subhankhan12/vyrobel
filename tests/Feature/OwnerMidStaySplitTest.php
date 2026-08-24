<?php

declare(strict_types=1);

use App\Models\OwnerLedgerEntry;
use App\Models\OwnerStatement;
use App\Models\ReservationUnit;
use App\Models\ReservationUnitMove;
use App\Services\AssignmentService;
use App\Services\OwnerAttributionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ MONEY PATH — the mid-stay split. The operator states THE FIRST NIGHT THE GUEST SLEPT IN THE NEW UNIT;
 * nights before it belong to the old unit's owner, that night and after to the new one.
 *
 * ⚠️ THE INVARIANT THESE EXIST FOR: division loses cents, so the shares must add back to the original
 * total EXACTLY, at every ratio — not just the tidy ones.
 */
function splitStay(int $nights, int $roomMinor): array
{
    $s = seedScenario(2);
    $ownerA = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);

    $ci = CarbonImmutable::parse('2026-09-01');
    $reservation = book($s, [
        'unit_id' => $s['units'][0]->id,
        'check_in' => $ci->toDateString(),
        'check_out' => $ci->addDays($nights)->toDateString(),
    ]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    return [$s, $ownerA, $ownerB, $reservation, $ci];
}

function splitNetFor(string $ownerId, string $reservationId): int
{
    return (int) OwnerLedgerEntry::query()
        ->where('reservation_id', $reservationId)->where('owner_id', $ownerId)->get()
        ->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
}

function splitRoomFor(string $ownerId, string $reservationId): int
{
    return (int) OwnerLedgerEntry::query()
        ->where('reservation_id', $reservationId)->where('owner_id', $ownerId)
        ->where('entry_type', 'room_revenue')->get()
        ->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());
}

it('⚠️ THE SUM IS EXACT AT EVERY RATIO — many splits, not one tidy fixture', function () {
    // 7 nights × every interior boundary, and totals chosen to round badly (primes, odd cents).
    foreach ([100003, 99999, 20000, 7, 12345] as $roomMinor) {
        foreach ([1, 2, 3, 4, 5, 6] as $moveAfter) {
            [$s, $ownerA, $ownerB, $reservation, $ci] = splitStay(7, $roomMinor);
            $before = splitRoomFor((string) $ownerA->id, (string) $reservation->id);

            $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
            // ⚠️ Operators record a move DURING the stay — a night that has not happened cannot be stated.
            CarbonImmutable::setTestNow($ci->addDays(7));
            Carbon\Carbon::setTestNow($ci->addDays(7));
            app(AssignmentService::class)->assignUnit(
                $line, (string) $s['units'][1]->id, $ci->addDays($moveAfter)->toDateString()
            );

            $a = splitRoomFor((string) $ownerA->id, (string) $reservation->id);
            $b = splitRoomFor((string) $ownerB->id, (string) $reservation->id);

            // ⚠️ TO THE CENT. If division lost anything, this is where it shows.
            expect($a + $b)->toBe($before, "room total lost cents at {$roomMinor} split after {$moveAfter}");
            expect($a)->toBeGreaterThan(0)->and($b)->toBeGreaterThan(0);
        }
    }
});

it('nights fall on the right side of the boundary', function () {
    [$s, $ownerA, $ownerB, $reservation, $ci] = splitStay(6, 60000);

    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id, $ci->addDays(2)->toDateString());

    // 2 nights before the boundary, 4 from it — 60000 over 6 nights = 20000 / 40000.
    expect(splitRoomFor((string) $ownerA->id, (string) $reservation->id))->toBe(20000)
        ->and(splitRoomFor((string) $ownerB->id, (string) $reservation->id))->toBe(40000);
});

it('⚠️ THREE SEGMENTS — a guest who moves twice is not collapsed to two', function () {
    $s = seedScenario(3);
    $ownerA = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $ownerB = ownUnit($s['units'][1], ['commission_pct' => 18]);
    $ownerC = ownUnit($s['units'][2], ['commission_pct' => 18]);

    $ci = CarbonImmutable::parse('2026-09-01');
    $reservation = book($s, [
        'unit_id' => $s['units'][0]->id,
        'check_in' => $ci->toDateString(),
        'check_out' => $ci->addDays(6)->toDateString(),
    ]);
    postRoom($reservation, 60000);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $svc = app(AssignmentService::class);
    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $line = $svc->assignUnit($line, (string) $s['units'][1]->id, $ci->addDays(2)->toDateString());
    $svc->assignUnit($line, (string) $s['units'][2]->id, $ci->addDays(4)->toDateString());

    // 2 + 2 + 2 nights → 20000 each, and all three owners are present.
    expect(splitRoomFor((string) $ownerA->id, (string) $reservation->id))->toBe(20000)
        ->and(splitRoomFor((string) $ownerB->id, (string) $reservation->id))->toBe(20000)
        ->and(splitRoomFor((string) $ownerC->id, (string) $reservation->id))->toBe(20000);
});

it('⚠️ A→B→A MERGES rather than double-posting — the key does not collide', function () {
    [$s, $ownerA, $ownerB, $reservation, $ci] = splitStay(6, 60000);

    $svc = app(AssignmentService::class);
    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $line = $svc->assignUnit($line, (string) $s['units'][1]->id, $ci->addDays(2)->toDateString());
    $svc->assignUnit($line, (string) $s['units'][0]->id, $ci->addDays(4)->toDateString());

    // A holds segments 1 and 3 (2+2 nights), B holds segment 2 (2 nights).
    // ⚠️ Without the segment suffix in the idempotency key, A's second segment would silently overwrite
    //    its first and A would show 20000 instead of 40000.
    expect(splitRoomFor((string) $ownerA->id, (string) $reservation->id))->toBe(40000)
        ->and(splitRoomFor((string) $ownerB->id, (string) $reservation->id))->toBe(20000);
});

it('⚠️ BOUNDED BY ARITHMETIC AT THE BOUNDARY — outside the stay is refused', function () {
    [$s, , , $reservation, $ci] = splitStay(5, 50000);
    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));
    $svc = app(AssignmentService::class);
    $target = (string) $s['units'][1]->id;

    expect(fn () => $svc->assignUnit($line, $target, $ci->toDateString()))
        ->toThrow(InvalidArgumentException::class);                       // == check-in: no nights before it
    expect(fn () => $svc->assignUnit($line, $target, $ci->subDay()->toDateString()))
        ->toThrow(InvalidArgumentException::class);                       // before arrival
    expect(fn () => $svc->assignUnit($line, $target, $ci->addDays(5)->toDateString()))
        ->toThrow(InvalidArgumentException::class);                       // == check-out: no nights after it
});

it('⚠️ BOTH DATES ARE RECORDED — the gap is the manipulation signal', function () {
    [$s, , , $reservation, $ci] = splitStay(6, 60000);

    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));

    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id, $ci->addDays(2)->toDateString());

    $move = ReservationUnitMove::query()->where('reservation_unit_id', $line->getKey())->firstOrFail();

    expect($move->effective_date->toDateString())->toBe($ci->addDays(2)->toDateString())
        ->and($move->stated_at)->not->toBeNull()
        // ⚠️ THE DELTA IS THE WHOLE POINT, so it is asserted EXACTLY. The move was stated on day 7 for a
        //    boundary on day 2, so a five-day gap must be visible and permanent. `>= 0` would have been
        //    vacuous — it is satisfied by stated_at merely copying the effective date, which is precisely
        //    the shape that makes backdating undetectable.
        ->and($move->statedLateDays())->toBe(5);
});

it('⚠️ A SEALED PERIOD IS MARKED ON BOTH SIDES, never rewritten', function () {
    [$s, $ownerA, $ownerB, $reservation, $ci] = splitStay(6, 60000);

    // Both owners' periods are sealed — the one LOSING nights and the one GAINING them.
    foreach ([$ownerA, $ownerB] as $o) {
        OwnerStatement::query()->create([
            'organization_id' => $reservation->organization_id,
            'owner_id' => $o->id,
            'period_start' => $ci->startOfMonth()->toDateString(),
            'period_end' => $ci->endOfMonth()->toDateString(),
            'currency' => 'EUR', 'source_currency' => 'EUR',
            'status' => 'finalized', 'locked' => true,
        ]);
    }

    CarbonImmutable::setTestNow($ci->addDays(7));
    Carbon\Carbon::setTestNow($ci->addDays(7));
    $line = ReservationUnit::query()->where('reservation_id', $reservation->id)->firstOrFail();
    app(AssignmentService::class)->assignUnit($line, (string) $s['units'][1]->id, $ci->addDays(2)->toDateString());

    // ⚠️ Visible on BOTH statements — a one-sided mark is how a discrepancy becomes an argument.
    foreach ([$ownerA, $ownerB] as $o) {
        $marked = OwnerLedgerEntry::query()
            ->where('reservation_id', $reservation->id)
            ->where('owner_id', $o->id)
            ->where('prior_period_adjustment', true)
            ->exists();

        expect($marked)->toBeTrue("owner {$o->id} carries no prior-period mark");
    }
});
