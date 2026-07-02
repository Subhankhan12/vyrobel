<?php

use App\Actions\Reservations\CheckOutReservation;
use App\Models\HousekeepingTask;
use App\Models\UnitStatusLog;
use App\Services\HousekeepingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Obligation 10 — event→listener discipline: checkout auto-creates exactly one departure task
// via the listener; housekeeping status events never move availability.sold (no recompute loop).

it('creates exactly one departure task via the checkout listener without touching inventory', function () {
    $s = seedScenario(1);
    $reservation = book($s, ['unit_id' => $s['units'][0]->id]);
    $soldBefore = soldOn($s['roomType']->id, '2026-09-01');

    expect(HousekeepingTask::count())->toBe(0);

    app(CheckOutReservation::class)->handle($reservation); // emits event; listener creates the task

    $task = HousekeepingTask::query()->first();
    expect(HousekeepingTask::count())->toBe(1)
        ->and($task->type)->toBe('departure')
        ->and($task->status)->toBe('pending')
        ->and($task->unit_id)->toBe($s['units'][0]->id)
        ->and($reservation->refresh()->status)->toBe('checked_out')
        ->and($reservation->reservationUnits()->first()->status)->toBe('active') // listener didn't cancel
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe($soldBefore);       // inventory untouched
});

it('does not move availability.sold when a housekeeping status transition fires', function () {
    $s = seedScenario(1);
    $unit = $s['units'][0];
    book($s, ['unit_id' => $unit->id]);
    $soldBefore = soldOn($s['roomType']->id, '2026-09-01');

    app(HousekeepingService::class)->markUnitHousekeepingStatus($unit->refresh(), 'dirty');

    expect($unit->refresh()->housekeeping_status)->toBe('dirty')
        ->and(UnitStatusLog::query()->where('unit_id', $unit->id)->where('type', 'housekeeping')->count())->toBeGreaterThan(0)
        ->and(soldOn($s['roomType']->id, '2026-09-01'))->toBe($soldBefore); // recompute never triggered
});
