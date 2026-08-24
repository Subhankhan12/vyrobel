<?php

declare(strict_types=1);

use App\Exceptions\Folio\OutstandingBalanceException;
use App\Models\AuditEvent;
use App\Models\Folio;
use App\Models\HousekeepingTask;
use App\Models\OwnerLedgerEntry;
use App\Models\Unit;
use App\Services\FolioService;
use App\Services\FrontDeskService;
use App\Services\OwnerAttributionService;
use App\Support\Folio\CheckoutOverrideReason;
use App\Support\Folio\CheckoutOverrideStats;
use App\Support\Folio\FolioTotals;
use App\Support\Money\Money;
use App\Support\Owner\OwnerCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * ⚠️ THE SUPERVISOR OVERRIDE. ⚠️⚠️ THE DELIVERABLE IS THE ROOM, NOT THE OVERRIDE: a blocked checkout means
 * `GenerateDepartureCleanTask` never fires, so the unit is never flipped to dirty and housekeeping never
 * runs. The chain is asserted end to end below.
 *
 * ⚠️ And it must change NO money: the balance stays owed, the notification fires, and the collection gate
 * still reads uncollected.
 */
function overrideStay(int $chargeMinor = 20000, int $paidMinor = 0): array
{
    $s = seedScenario(1);
    $owner = ownUnit($s['units'][0], ['commission_pct' => 18]);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    postRoom($reservation, $chargeMinor);
    app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

    $folio = Folio::query()->where('reservation_id', $reservation->getKey())->firstOrFail();
    if ($paidMinor > 0) {
        app(FolioService::class)->recordPayment($folio, Money::of($paidMinor, 'EUR'));
    }
    $reservation->forceFill(['status' => 'checked_in'])->save();

    return [$s, $owner, $reservation->refresh(), $folio->refresh()];
}

it('⚠️⚠️ THE WHOLE CHAIN — blocked → overridden → checked out → task generated → unit DIRTY → still owed', function () {
    [$s, , $reservation, $folio] = overrideStay();
    $unitId = (string) $s['units'][0]->id;

    // 1. Blocked while owed.
    expect(fn () => app(FrontDeskService::class)->checkOut($reservation))
        ->toThrow(OutstandingBalanceException::class);

    // ⚠️ And the room is stranded: no task, unit not dirty. This is the failure the override exists for.
    expect(HousekeepingTask::query()->where('unit_id', $unitId)->count())->toBe(0);

    // 2. Overridden.
    app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::GUEST_DEPARTED);

    // 3. Checked out, 4. task generated, 5. ⚠️ THE UNIT IS ACTUALLY DIRTY — the deliverable.
    expect($reservation->refresh()->status)->toBe('checked_out')
        ->and(HousekeepingTask::query()->where('unit_id', $unitId)->count())->toBeGreaterThan(0)
        ->and((string) Unit::query()->find($unitId)->housekeeping_status)->toBe('dirty');

    // 6. ⚠️ AND THE BALANCE IS STILL OWED — the override permitted an action, it did not resolve a debt.
    expect((int) FolioTotals::for($folio->refresh()->load(['items', 'payments']))['balance_minor'])->toBe(20000)
        ->and(OwnerCollection::reservationIsCollected((string) $reservation->getKey()))->toBeFalse();
});

it('⚠️ IT MOVES NO MONEY — the owner ledger is untouched by an override', function () {
    [, , $reservation] = overrideStay();

    $rows = OwnerLedgerEntry::query()->count();
    $sum = (int) OwnerLedgerEntry::query()->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor());

    app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::ROUNDING_RESIDUE);

    expect(OwnerLedgerEntry::query()->count())->toBe($rows)
        ->and((int) OwnerLedgerEntry::query()->get()->sum(fn (OwnerLedgerEntry $e): int => $e->signedMinor()))->toBe($sum);
});

it('⚠️ A FREE-TEXT reason is REFUSED — an override nobody can count cannot be evaluated', function () {
    [, , $reservation] = overrideStay();

    expect(fn () => app(FrontDeskService::class)->checkOut($reservation, 'the guest seemed nice'))
        ->toThrow(InvalidArgumentException::class);

    expect($reservation->refresh()->status)->toBe('checked_in');
});

it('⚠️ "other" REQUIRES a note, and is counted separately', function () {
    [, , $reservation] = overrideStay();

    expect(fn () => app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::OTHER))
        ->toThrow(InvalidArgumentException::class);

    app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::OTHER, 'night audit sign-off');

    expect($reservation->refresh()->status)->toBe('checked_out')
        ->and(CheckoutOverrideStats::since()['by_reason'])->toHaveKey(CheckoutOverrideReason::OTHER);
});

it('⚠️ THE AUDIT carries the AMOUNT, not just the act', function () {
    [, , $reservation] = overrideStay(20000, 19999);   // one cent owed

    app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::ROUNDING_RESIDUE);

    $row = AuditEvent::query()->where('action', 'reservation.checkout_overridden')->firstOrFail();

    // ⚠️ A €0.01 override and a €900 override must not look alike in six months.
    expect($row->after['reason'])->toBe(CheckoutOverrideReason::ROUNDING_RESIDUE)
        ->and($row->after['balance_minor'])->toBe(1);
});

it('⚠️ IT IS AN ACT, NOT A STATE — no standing flag survives on the folio', function () {
    [, , $reservation, $folio] = overrideStay();

    app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::GUEST_DEPARTED);

    $fresh = $folio->refresh()->getAttributes();
    foreach ($fresh as $column => $value) {
        expect(str_contains($column, 'override'))->toBeFalse("folio carries a standing override column: {$column}");
    }
});

it('⚠️ THE OVERRIDE RATE is answerable', function () {
    [, , $reservation] = overrideStay();
    app(FrontDeskService::class)->checkOut($reservation, CheckoutOverrideReason::GUEST_DEPARTED);

    $stats = CheckoutOverrideStats::since();

    expect($stats['overrides'])->toBe(1)
        ->and($stats['checkouts'])->toBeGreaterThan(0)
        ->and($stats['rate'])->toBeGreaterThan(0.0)
        ->and($stats['total_minor'])->toBe(20000);
});

it('⚠️ THE PERMISSION — a Front Desk agent cannot override, a Hotel Manager can', function () {
    [$s, , $reservation] = overrideStay();

    $this->actingAs(operatorUser($s, [], 'Front Desk'))
        ->post(route('app.front-desk.check-out', $reservation->id), [
            'override_reason' => CheckoutOverrideReason::GUEST_DEPARTED,
        ])->assertRedirect();
    expect($reservation->refresh()->status)->toBe('checked_in');   // refused

    $this->actingAs(operatorUser($s, [], 'Hotel Manager'))
        ->post(route('app.front-desk.check-out', $reservation->id), [
            'override_reason' => CheckoutOverrideReason::GUEST_DEPARTED,
        ])->assertRedirect();
    expect($reservation->refresh()->status)->toBe('checked_out');
});
