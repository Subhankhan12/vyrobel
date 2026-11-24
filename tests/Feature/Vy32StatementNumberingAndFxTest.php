<?php

declare(strict_types=1);

use App\Exceptions\Owner\StatementSealedException;
use App\Models\ManagementAgreement;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Services\OwnerAttributionService;
use App\Services\OwnerStatementService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

// ⚠️⚠️ VY-32 (audit of 2026-11-24, read in code) — TWO OF ITS FIVE SMALL DEFECTS ARE A STATEMENT'S: its number took no lock, and its
// currency conversion went through a float.
//
//   · NUMBERING — finalize() read max(sequence_no)+1 and stamped the unclaimed ledger lines with nothing serialising two finalizes for one
//     owner. The case a single process CAN show is the same race run one after the other: a finalize holding a STALE copy of a statement
//     that another call has just sealed. It passed the in-memory isLocked() check and sealed the row a second time, with a new number.
//     The owner row is now locked first and the statement re-read under the lock.
//   · FX — `round($net * (float) $rate)`: 30.00 at 1.0835 is exactly 3250.5 minor units and the float product rounded it to 3250. The rate
//     is a decimal and is now multiplied as one (OwnerStatementService::convert), half away from zero.

uses(DatabaseTransactions::class);

/** A USD-paid owner of a EUR unit, 0% commission so the net is exactly the room: one collected stay of `$roomMinor`. */
function vy32Owner(int $roomMinor): Owner
{
    $s = seedScenario(1);
    $owner = Owner::factory()->create(['payout_currency' => 'USD']);
    $s['units'][0]->update(['owner_id' => $owner->id]);
    ManagementAgreement::factory()->create(['owner_id' => $owner->id, 'commission_pct' => 0, 'effective_from' => '2026-01-01']);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $roomMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation);
    collectFolio($reservation);

    return $owner;
}

it('⚠️⚠️ VY-32 (FX, as read): 30.00 at 1.0835 is paid as 32.51 — the exact half rounds away from zero, never lost to a float', function () {
    $owner = vy32Owner(3000);

    $statement = app(OwnerStatementService::class)->generateDraft($owner->id, '2026-09-01', '2026-09-30', '1.0835000000', 'test');

    expect($statement->net_source_minor)->toBe(3000)
        ->and($statement->net_payable_minor)->toBe(3251)            // the float product gave 3250
        ->and((string) $statement->fx_rate)->toBe('1.0835000000');   // the decimal kept as given
});

it('VY-32 (FX): the conversion is exact for every case a float got wrong, and agrees with it where the float was right', function (int $minor, string $rate, int $expected) {
    expect(OwnerStatementService::convert($minor, $rate))->toBe($expected);
})->with([
    'float said 3250' => [3000, '1.0835', 3251],
    'float said 64297' => [75000, '0.8573', 64298],
    'float said 994' => [850, '1.17', 995],
    'float said 100' => [100, '1.005', 101],
    'a refund (negative) rounds away from zero too' => [-3000, '1.0835', -3251],
    'the old test case' => [17000, '1.1000000000', 18700],
]);

it('⚠️⚠️ VY-32 (numbering): a finalize holding a stale copy of a statement that was just sealed is refused — no second number, no second seal', function () {
    $owner = vy32Owner(20000);
    $service = app(OwnerStatementService::class);

    $draft = $service->generateDraft($owner->id, '2026-09-01', '2026-09-30', '1.1000000000', 'test');
    $stale = OwnerStatement::query()->findOrFail($draft->getKey());   // a second caller's copy, read before the first one sealed it

    $first = $service->finalize($draft);
    expect($first->sequence_no)->toBe(1);

    expect(fn () => $service->finalize($stale))->toThrow(StatementSealedException::class);

    $row = OwnerStatement::query()->findOrFail($draft->getKey());
    expect($row->sequence_no)->toBe(1)
        ->and(OwnerStatement::query()->where('owner_id', $owner->id)->max('sequence_no'))->toBe(1);
});
