<?php

use App\Models\Availability;
use App\Models\Property;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Obligation 4 — a unit that is both out_of_order AND blocked is subtracted ONCE, and
// available() never goes negative.

it('does not double-count a unit that is both out_of_order and under a block', function () {
    newDemoOrg();
    $property = Property::create(['name' => 'H', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Std', 'kind' => 'room']);
    $inService = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'A']);
    $oooUnit = Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'B', 'condition_status' => 'out_of_order']);

    // Block the SAME unit that is already out_of_order (routes through the single write path;
    // the domain event triggers a recompute).
    app(AssignmentService::class)->blockInventory([
        'property_id' => $property->id, 'unit_id' => $oooUnit->id,
        'start_date' => '2026-09-01', 'end_date' => '2026-09-02', 'reason' => 'maintenance',
    ]);

    $row = Availability::query()->where('room_type_id', $roomType->id)->whereDate('date', '2026-09-01')->first();

    // total 2, out_of_order 1 (unit B), blocked 0 (B is not in-service, so its block is not
    // counted again) → available = 1. If it double-counted, blocked would be 1 → available 0.
    expect($row->total)->toBe(2)
        ->and($row->out_of_order)->toBe(1)
        ->and($row->blocked)->toBe(0)
        ->and($row->available())->toBe(1);
    expect($inService->id)->not->toBeNull();
});

it('floors available() at zero and never returns a negative number', function () {
    newDemoOrg();
    $property = Property::create(['name' => 'H', 'type' => 'hotel', 'currency' => 'EUR', 'timezone' => 'UTC']);
    $roomType = RoomType::create(['property_id' => $property->id, 'name' => 'Std', 'kind' => 'room']);
    Unit::create(['property_id' => $property->id, 'room_type_id' => $roomType->id, 'code' => 'A']);

    // Two room-type-level blocks (each counts as 1) against a single-unit type → total 1 − 2 = -1.
    foreach (['b1', 'b2'] as $ignored) {
        app(AssignmentService::class)->blockInventory([
            'property_id' => $property->id, 'room_type_id' => $roomType->id,
            'start_date' => '2026-09-01', 'end_date' => '2026-09-02', 'reason' => 'overbooking_protection',
        ]);
    }

    $row = Availability::query()->where('room_type_id', $roomType->id)->whereDate('date', '2026-09-01')->first();

    expect($row->total)->toBe(1)
        ->and($row->blocked)->toBe(2)
        ->and($row->total - $row->sold - $row->out_of_order - $row->blocked)->toBe(-1) // raw arithmetic is negative
        ->and($row->available())->toBe(0);                                            // but available() floors at 0

    expect(app(AvailabilityService::class)->availableOn($roomType->id, '2026-09-01'))->toBe(0);
});
