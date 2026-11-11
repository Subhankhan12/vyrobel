<?php

declare(strict_types=1);

// Mutation set property-scope — DEC-i / DEC-j / DEC-q (2026-11-11): each lookup that keeps a write inside the person's properties, removed one at a time.
// Run with `php tests/Mutations/run.php --set=property-scope`.

$file = 'app/Support/Property/PropertyScope.php';
$test = 'tests/Feature/PropertyScopeWritesTest.php';

return [
    [
        'id' => 'PSW-M1',
        'name' => 'a unit is found outside the person\'s properties (a work order can be raised on another property\'s unit)',
        'file' => $file,
        'old' => '        return Unit::query()->whereIn(\'property_id\', $this->ids())->findOrFail($id);',
        'new' => '        return Unit::query()->findOrFail($id);',
        'tests' => [['file' => $test, 'filter' => 'every maintenance write, and the work order page']],
    ],
    [
        'id' => 'PSW-M2',
        'name' => 'a housekeeping task is found outside the person\'s properties',
        'file' => $file,
        'old' => '        return HousekeepingTask::query()->whereIn(\'property_id\', $this->ids());',
        'new' => '        return HousekeepingTask::query();',
        'tests' => [['file' => $test, 'filter' => 'every housekeeping write']],
    ],
    [
        'id' => 'PSW-M3',
        'name' => 'a rate proposal (whose accept-counter APPLIES a rate) is found outside the person\'s properties',
        'file' => $file,
        'old' => "            ->whereHas('roomType', fn (Builder \$r) => \$r->whereIn('property_id', \$ids))\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'a rate proposal']],
    ],
    [
        'id' => 'PSW-M4',
        'name' => 'a room type for a proposal is found outside the person\'s properties',
        'file' => $file,
        'old' => '        return RoomType::query()->whereIn(\'property_id\', $this->ids())->findOrFail($id);',
        'new' => '        return RoomType::query()->findOrFail($id);',
        'tests' => [['file' => $test, 'filter' => 'a rate proposal']],
    ],
    [
        'id' => 'PSW-M5',
        'name' => 'the work order PAGE no longer checks the ticket against the person\'s properties',
        'file' => 'app/Livewire/MaintenanceTicketDetail.php',
        'old' => '        app(PropertyScope::class)->ticket($ticket);',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'work order PAGE opens only']],
    ],
    [
        'id' => 'PSW-M6',
        'name' => 'Portfolio Edit finds the property through the tenant scope only again',
        'file' => 'app/Http/Controllers/Operator/PropertyController.php',
        'old' => '        $property = $this->accessibleProperty($data[\'property_id\']);   // the person\'s allow-list (DEC-i), then the tenant scope → 404 otherwise',
        'new' => '        $property = Property::query()->findOrFail($data[\'property_id\']);',
        'tests' => [['file' => $test, 'filter' => 'Portfolio Edit changes only']],
    ],
    [
        'id' => 'PSW-M7',
        'name' => 'the single pause/resume writes the flag outside a transaction again (a failed recompute leaves the unit flipped)',
        'file' => 'app/Services/OwnerUnitStatusService.php',
        'old' => '        DB::transaction(function () use ($unit, $active, $event, $actorId): void {',
        'new' => '        value(function () use ($unit, $active, $event, $actorId): void {',   // runs at once, with no transaction around it
        'tests' => [['file' => 'tests/Feature/UnitSingleTransactionTest.php', 'filter' => 'a failed recompute leaves the unit']],
    ],
];
