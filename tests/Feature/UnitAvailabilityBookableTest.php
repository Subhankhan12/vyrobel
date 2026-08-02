<?php

use App\Services\RateAndRestrictionService;
use App\Services\UnitAvailabilityService;
use App\Support\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The bookable-unit read must reconcile with the availability COUNT: AvailabilityService counts only
// `is_active` units in `total` and subtracts the out-of-service ones, so a NEW-booking read must never
// OFFER a paused (is_active=false) or out-of-service unit — doing so is an unbookable, non-reconciling
// option (and, if booked, puts a guest in an out-of-order room). Both certified reads use Unit::bookable().

function bookableScenario(): array
{
    $s = seedScenario(3);   // U1, U2, U3 under room type "Std"
    app(RateAndRestrictionService::class)->setRate($s['ratePlan'], '2026-09-01', '2026-09-10', Money::of(12000, 'EUR'));

    return $s;
}

it('never offers a paused or out-of-service unit for a new booking (availableRoomTypes)', function () {
    $s = bookableScenario();
    $s['units'][0]->update(['condition_status' => 'out_of_service']);   // maintenance OOS
    $s['units'][1]->update(['is_active' => false]);                      // owner-paused (#69)

    $rooms = app(UnitAvailabilityService::class)->availableRoomTypes($s['property']->id, '2026-09-02', '2026-09-05');

    // Exactly one room type, and ONLY the in-service active unit (U3) is offered.
    expect($rooms)->toHaveCount(1);
    $ids = array_column($rooms[0]['units'], 'id');
    expect($ids)->toBe([(string) $s['units'][2]->id])
        ->and($ids)->not->toContain((string) $s['units'][0]->id)   // OOS excluded
        ->and($ids)->not->toContain((string) $s['units'][1]->id);  // paused excluded
});

it('never offers a paused or out-of-service unit as a move target (freeUnitsForRoomType)', function () {
    $s = bookableScenario();
    $s['units'][0]->update(['condition_status' => 'out_of_order']);
    $s['units'][1]->update(['is_active' => false]);

    $free = app(UnitAvailabilityService::class)->freeUnitsForRoomType($s['property']->id, $s['roomType']->id, '2026-09-02', '2026-09-05');

    $ids = array_column($free, 'id');
    expect($ids)->toBe([(string) $s['units'][2]->id]);
});

it('offers all units again once they are back in service / resumed', function () {
    $s = bookableScenario();
    $s['units'][0]->update(['condition_status' => 'out_of_service', 'is_active' => true]);
    $s['units'][1]->update(['is_active' => false]);

    // Return them to a bookable state.
    $s['units'][0]->update(['condition_status' => 'in_service']);
    $s['units'][1]->update(['is_active' => true]);

    $rooms = app(UnitAvailabilityService::class)->availableRoomTypes($s['property']->id, '2026-09-02', '2026-09-05');
    $ids = array_column($rooms[0]['units'], 'id');
    expect($ids)->toHaveCount(3);   // all three offered again — real state, not a fabricated hold
});
