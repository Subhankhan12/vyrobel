<?php

declare(strict_types=1);

// Mutation set owner-since — D-OWNERSINCE-WINDOWS (2026-11-12): every owner-facing read of a unit's stays and nights is cut at the day the unit became the
// owner's (`units.owner_since`), one mutation per reader, each aimed at the test that watches that reader. Run with `php tests/Mutations/run.php --set=owner-since`.

$test = 'tests/Feature/OwnerSinceWindowsTest.php';

return [
    [
        'id' => 'OSW-M1',
        'name' => 'the shared owner figures read every stay of the unit again',
        'file' => 'app/Support/Analytics/PerformanceMetrics.php',
        'old' => 'ReservationUnit::query()->ownerStays()->whereIn(\'unit_id\', $unitIds),',
        'new' => 'ReservationUnit::query()->whereIn(\'unit_id\', $unitIds),',
        'tests' => [['file' => $test, 'filter' => 'osw01 the owner']],
    ],
    [
        'id' => 'OSW-M2',
        'name' => 'the shared owner figures count every night as available again (count × days)',
        'file' => 'app/Support/Analytics/PerformanceMetrics.php',
        'old' => '$availableRoomNights = OwnerStayWindow::nights($unitIds, $start, $end);',
        'new' => '$availableRoomNights = count($unitIds) * $days;',
        'tests' => [['file' => $test, 'filter' => 'osw01 the owner']],
    ],
    [
        'id' => 'OSW-M3',
        'name' => 'the month grid reads every stay of the unit again',
        'file' => 'app/Support/Owner/OwnerOccupancy.php',
        'old' => '                ->ownerStays()                          // ← only the stays that are the owner\'s (began on or after owner_since)
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw02 the month']],
    ],
    [
        'id' => 'OSW-M4',
        'name' => 'the month\'s per-unit nights available are the whole month again',
        'file' => 'app/Support/Owner/OwnerOccupancy.php',
        'old' => '$perUnitAvailable = OwnerStayWindow::nightsByUnit($unitIds, $monthStart, $monthEnd, $since);',
        'new' => '$perUnitAvailable = array_fill_keys($unitIds, $days);',
        'tests' => [['file' => $test, 'filter' => 'osw02 the month']],
    ],
    [
        'id' => 'OSW-M5',
        'name' => 'the month grid holds every unit on every day again',
        'file' => 'app/Support/Owner/OwnerOccupancy.php',
        'old' => '$held[$d] = OwnerStayWindow::unitsHeldOn($unitIds, $since, $monthStart->copy()->day($d)->toDateString());',
        'new' => '$held[$d] = count($unitIds);',
        'tests' => [['file' => $test, 'filter' => 'osw02 the month']],
    ],
    [
        'id' => 'OSW-M6',
        'name' => 'the Block Calendar names the guest of an operator stay again',
        'file' => 'app/Livewire/Owner/OwnerBlockCalendar.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: a stay before the owner\'s day is not theirs — nor is its guest\'s name
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw04 the Block']],
    ],
    [
        'id' => 'OSW-M7',
        'name' => 'the owner\'s booking page opens a stay from before their day',
        'file' => 'app/Livewire/Owner/OwnerBookingDetail.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: a stay that began before the owner\'s day is not theirs → 404
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw03 a booking']],
    ],
    [
        'id' => 'OSW-M8',
        'name' => 'the owner\'s booking confirmation opens a stay from before their day',
        'file' => 'app/Http/Controllers/Owner/OwnerBookingConfirmationPdfController.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: a stay that began before the owner\'s day is not theirs → 404
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw03 a booking']],
    ],
    [
        'id' => 'OSW-M9',
        'name' => 'Unit Detail\'s calendar counts the operator\'s nights as booked again',
        'file' => 'app/Livewire/Owner/OwnerUnitDetail.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: only the stays that are the owner\'s
            ->where(\'unit_id\', $unit->id)->where(\'status\', \'active\')
            ->whereDate(\'check_in\', \'<\', $monthEnd->toDateString())
            ->whereDate(\'check_out\', \'>\', $monthStart->toDateString())
            ->get([\'check_in\', \'check_out\'])',
        'new' => '            ->where(\'unit_id\', $unit->id)->where(\'status\', \'active\')
            ->whereDate(\'check_in\', \'<\', $monthEnd->toDateString())
            ->whereDate(\'check_out\', \'>\', $monthStart->toDateString())
            ->get([\'check_in\', \'check_out\'])',
        'tests' => [['file' => $test, 'filter' => 'osw09 Unit Detail\'s calendar']],
    ],
    [
        'id' => 'OSW-M10',
        'name' => 'Unit Detail\'s day detail names an operator stay\'s guest again',
        'file' => 'app/Livewire/Owner/OwnerUnitDetail.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: only the stays that are the owner\'s
            ->where(\'unit_id\', $unit->id)->where(\'status\', \'active\')
            ->whereDate(\'check_in\', \'<\', $monthEnd->toDateString())
            ->whereDate(\'check_out\', \'>\', $monthStart->toDateString())
            ->with(\'reservation.leadGuest\')',
        'new' => '            ->where(\'unit_id\', $unit->id)->where(\'status\', \'active\')
            ->whereDate(\'check_in\', \'<\', $monthEnd->toDateString())
            ->whereDate(\'check_out\', \'>\', $monthStart->toDateString())
            ->with(\'reservation.leadGuest\')',
        'tests' => [['file' => $test, 'filter' => 'osw05 Unit Detail']],
    ],
    [
        'id' => 'OSW-M11',
        'name' => 'Unit Detail lists an operator stay again',
        'file' => 'app/Livewire/Owner/OwnerUnitDetail.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: only the stays that are the owner\'s
            ->where(\'unit_id\', $unit->id)
            ->where(\'status\', \'active\')
            ->whereDate(\'check_in\', \'<\', $monthEnd->toDateString())',
        'new' => '            ->where(\'unit_id\', $unit->id)
            ->where(\'status\', \'active\')
            ->whereDate(\'check_in\', \'<\', $monthEnd->toDateString())',
        'tests' => [['file' => $test, 'filter' => 'osw05 Unit Detail']],
    ],
    [
        'id' => 'OSW-M12',
        'name' => 'Unit Detail\'s default month is an operator stay\'s month again',
        'file' => 'app/Livewire/Owner/OwnerUnitDetail.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: the default month is the owner\'s latest stay, never an earlier operator one
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw09 Unit Detail opens']],
    ],
    [
        'id' => 'OSW-M13',
        'name' => 'My Units\' day detail names an operator stay\'s guest again',
        'file' => 'app/Livewire/Owner/OwnerMyUnits.php',
        'old' => '            ->ownerStays()                                              // D-OWNERSINCE-WINDOWS: only the stays that are the owner\'s
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw08 My Units reads']],
    ],
    [
        'id' => 'OSW-M14',
        'name' => 'My Units\' per-unit % is over the whole month again',
        'file' => 'app/Livewire/Owner/OwnerMyUnits.php',
        'old' => '$held = $occupancy[\'per_unit_available\'][$id] ?? 0;',
        'new' => '$held = $occupancy[\'days\'];',
        'tests' => [['file' => $test, 'filter' => 'osw08 My Units reads']],
    ],
    [
        'id' => 'OSW-M15',
        'name' => 'the My Units CSV holds every unit on every day again',
        'file' => 'app/Http/Controllers/Owner/OwnerUnitsExportController.php',
        'old' => '$held = $occ[\'held\'][$d] ?? $occ[\'unit_count\'];',
        'new' => '$held = $occ[\'unit_count\'];',
        'tests' => [['file' => $test, 'filter' => 'osw05 the My Units CSV']],
    ],
    [
        'id' => 'OSW-M16',
        'name' => 'lead time counts an operator stay again',
        'file' => 'app/Support/Analytics/BookingCadence.php',
        'old' => 'ReservationUnit::query()->ownerStays()->whereIn(\'unit_id\', $unitIds)->select(\'reservation_id\'),',
        'new' => 'ReservationUnit::query()->whereIn(\'unit_id\', $unitIds)->select(\'reservation_id\'),',
        'tests' => [['file' => $test, 'filter' => 'osw06 lead time counts only']],
    ],
    [
        'id' => 'OSW-M17',
        'name' => 'the weekday grid counts an operator stay again',
        'file' => 'app/Support/Analytics/BookingCadence.php',
        'old' => 'self::activeLines(ReservationUnit::query()->ownerStays()->whereIn(\'unit_id\', $unitIds), $start, $end);',
        'new' => 'self::activeLines(ReservationUnit::query()->whereIn(\'unit_id\', $unitIds), $start, $end);',
        'tests' => [['file' => $test, 'filter' => 'osw06 lead time counts only']],
    ],
    [
        'id' => 'OSW-M18',
        'name' => 'the weekday grid holds every unit on every date again',
        'file' => 'app/Support/Analytics/BookingCadence.php',
        'old' => '$den[(int) $d->dayOfWeek] += OwnerStayWindow::unitsHeldOn($unitIds, $since, $d->toDateString());',
        'new' => '$den[(int) $d->dayOfWeek] += count($unitIds);',
        'tests' => [['file' => $test, 'filter' => 'osw06 lead time counts only']],
    ],
    [
        'id' => 'OSW-M19',
        'name' => 'the owner\'s review list shows a review of an operator stay again',
        'file' => 'app/Support/Reviews/ReviewList.php',
        'old' => 'ReviewRating::publicScope()->ownerStays()->whereIn(\'unit_id\', $unitIds)',
        'new' => 'ReviewRating::publicScope()->whereIn(\'unit_id\', $unitIds)',
        'tests' => [['file' => $test, 'filter' => 'osw07 the owner']],
    ],
    [
        'id' => 'OSW-M20',
        'name' => 'the owner\'s review summary averages an operator stay\'s review again',
        'file' => 'app/Support/Reviews/OwnerReviewSummary.php',
        'old' => '            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: only the reviews of stays that are the owner\'s
',
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw07 the owner']],
    ],
    [
        'id' => 'OSW-M21',
        'name' => 'the booking page\'s repeat-guest tag counts an operator stay again',
        'file' => 'app/Livewire/Owner/OwnerBookingDetail.php',
        'old' => 'ReservationUnit::query()->ownerStays()->whereIn(\'unit_id\', $ownerUnitIds)->where(\'status\', \'active\')->select(\'reservation_id\')',
        'new' => 'ReservationUnit::query()->whereIn(\'unit_id\', $ownerUnitIds)->where(\'status\', \'active\')->select(\'reservation_id\')',
        'tests' => [['file' => $test, 'filter' => 'earlier stay from before the owner']],
    ],
    [
        'id' => 'OSW-M22',
        'name' => 'the trend\'s per-unit % is over the whole month again',
        'file' => 'app/Support/Owner/OwnerOccupancyTrend.php',
        'old' => '$held = $current[\'per_unit_available\'][(string) $id] ?? 0;',
        'new' => '$held = $current[\'days\'];',
        'tests' => [['file' => $test, 'filter' => 'osw02 the month']],
    ],
    [
        'id' => 'OSW-M23',
        'name' => 'the scope itself lets an operator stay through (the date test is inverted)',
        'file' => 'app/Models/ReservationUnit.php',
        'old' => '->whereColumn(\'reservation_units.check_in\', \'<\', \'units.owner_since\');',
        'new' => '->whereColumn(\'reservation_units.check_in\', \'>\', \'units.owner_since\');',
        'tests' => [['file' => $test, 'filter' => 'osw01 the owner']],
    ],
    [
        'id' => 'OSW-M24',
        'name' => 'the window helper starts every unit at the period start (owner_since ignored)',
        'file' => 'app/Support/Owner/OwnerStayWindow.php',
        'old' => '$from = CarbonImmutable::parse(isset($since[$id]) && $since[$id] > $startDay ? $since[$id] : $startDay);',
        'new' => '$from = CarbonImmutable::parse($startDay);',
        'tests' => [['file' => $test, 'filter' => 'osw01 the owner']],
    ],
    [
        'id' => 'OSW-M25',
        'name' => 'Insights\' busiest night is read from the operator\'s stays again',
        'file' => 'app/Livewire/Owner/OwnerInsights.php',
        'old' => "            ->ownerStays()                                      // D-OWNERSINCE-WINDOWS: only the stays that are the owner's\n",
        'new' => '',
        'tests' => [['file' => $test, 'filter' => 'osw10 Insights']],
    ],
];
