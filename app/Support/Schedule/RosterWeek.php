<?php

declare(strict_types=1);

namespace App\Support\Schedule;

use App\Livewire\Operator\OperatorSchedule;
use App\Models\Property;
use App\Models\Shift;
use App\Services\LeaveService;
use Carbon\CarbonImmutable;

/**
 * THE one builder of a week's roster (Phase 7d · SG2) — real staff × Mon–Sun for a property, each cell its
 * {@see ShiftType} with the REAL leave overlay + the REAL computed conflict. Read by BOTH the on-screen grid
 * ({@see OperatorSchedule}) AND the schedule PDF, so the PDF reconciles to the screen
 * character-for-character — two builders would drift.
 *
 * 🪤 REAL, not fabricated: a cell's `on_leave` is a real approved-leave record covering that date; `conflict`
 * is a real working-shift-on-a-leave-day (assign-on-leave is prevented at write time, so a conflict only
 * arises when leave is approved AFTER a shift was set — the grid surfaces it honestly).
 */
final class RosterWeek
{
    /**
     * @param  list<array{date: string, dow: string, label: string, today: bool}>  $days
     * @param  list<array{staff: array{id: string, name: string, initials: string, department: string, role_label: string}, cells: list<array<string, mixed>>}>  $rows
     */
    public function __construct(
        public readonly array $days,
        public readonly array $rows,
        public readonly string $weekLabel,
        public readonly bool $isThisWeek,
        public readonly int $week,
        public readonly string $dept,
        public readonly int $conflictCount,
    ) {}

    public static function build(?Property $property, int $week, string $dept): self
    {
        $tz = $property !== null ? (string) ($property->timezone ?? 'UTC') : 'UTC';
        $today = CarbonImmutable::now($tz);
        $monday = $today->startOfWeek(CarbonImmutable::MONDAY)->addWeeks($week);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $monday->addDays($i);
            $days[] = ['date' => $d->toDateString(), 'dow' => $d->format('D'), 'label' => $d->format('j M'), 'today' => $d->isSameDay($today)];
        }
        $from = $days[0]['date'];
        $to = $days[6]['date'];

        $staff = app(StaffRoster::class)->staff();
        if ($dept !== 'all') {
            $staff = array_values(array_filter($staff, fn (array $s): bool => $s['department'] === $dept));
        }
        $staffIds = array_map(fn (array $s): string => $s['id'], $staff);

        // Shifts for THIS property + week, and approved-leave days for these staff — both keyed "user_id|date".
        $shiftMap = [];
        if ($property !== null && $staffIds !== []) {
            Shift::query()
                ->where('property_id', $property->getKey())
                ->whereIn('user_id', $staffIds)
                ->whereBetween('date', [$from, $to])
                ->get()
                ->each(function (Shift $s) use (&$shiftMap): void {
                    $shiftMap[$s->user_id.'|'.$s->date->toDateString()] = $s->shift_type;
                });
        }
        $leaveDays = $staffIds === [] ? [] : app(LeaveService::class)->approvedLeaveDays($staffIds, $from, $to);

        $conflictCount = 0;
        $rows = array_map(function (array $s) use ($days, $shiftMap, $leaveDays, &$conflictCount): array {
            $cells = array_map(function (array $day) use ($s, $shiftMap, $leaveDays, &$conflictCount): array {
                $key = $s['id'].'|'.$day['date'];
                $type = $shiftMap[$key] ?? ShiftType::OFF;
                $onLeave = isset($leaveDays[$key]);
                $working = ShiftType::isWorking($type);
                $conflict = $working && $onLeave;
                if ($conflict) {
                    $conflictCount++;
                }

                return [
                    'date' => $day['date'],
                    'type' => $type,
                    'label' => ShiftType::label($type),
                    'hours' => ShiftType::hours($type),
                    'working' => $working,
                    'on_leave' => $onLeave,
                    'conflict' => $conflict,
                    'display' => $onLeave ? 'Leave' : ($working ? ShiftType::label($type) : '—'),
                ];
            }, $days);

            return ['staff' => $s, 'cells' => $cells];
        }, $staff);

        return new self(
            days: $days,
            rows: $rows,
            weekLabel: $monday->format('j M').' – '.$monday->addDays(6)->format('j M Y'),
            isThisWeek: $week === 0,
            week: $week,
            dept: $dept,
            conflictCount: $conflictCount,
        );
    }
}
