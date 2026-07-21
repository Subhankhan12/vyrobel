<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Shift;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffDepartment;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Team Schedule (Phase 7d · SG1) — the weekly staff roster grid: real staff (rows) × Mon–Sun (columns), each
 * cell a {@see ShiftType}, for the SELECTED property. Department filter + week navigation ride GET query params
 * (like the owner-bookings filters), so a full page load re-runs the /app property middleware and the roster
 * always reflects the current property — no wire-action that could bypass the tenant/property scope.
 *
 * 🔒 PROPERTY-SCOPED via {@see ResolvesCurrentProperty}: shifts are read for `currentPropertyId` only, so
 * switching property switches the roster. 🪤 REAL STAFF via {@see StaffRoster}: the rows are the org's actual
 * team; an org with no staff shows an honest empty state. Managing (the write) is a guarded controller POST.
 */
#[Layout('components.layouts.operator')]
class OperatorSchedule extends Component
{
    use FormatsOperatorData, ResolvesCurrentProperty;

    /** Week offset from the current week (0 = this week; navigated via GET links). */
    public int $week = 0;

    /** Department filter (one of {@see StaffDepartment::FILTERS}). */
    public string $dept = 'all';

    public function mount(): void
    {
        $this->week = (int) request()->query('week', '0');
        $dept = (string) request()->query('dept', 'all');
        $this->dept = StaffDepartment::isFilter($dept) ? $dept : 'all';
    }

    public function render(): View
    {
        $property = $this->currentProperty();
        $tz = $property !== null ? (string) ($property->timezone ?? 'UTC') : 'UTC';

        // The Monday of the target week, in the property's timezone (guest-facing "today" is tz-local).
        $monday = CarbonImmutable::now($tz)->startOfWeek(CarbonImmutable::MONDAY)->addWeeks($this->week);
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $monday->addDays($i);
            $days[] = ['date' => $d->toDateString(), 'dow' => $d->format('D'), 'label' => $d->format('j M'), 'today' => $d->isSameDay(CarbonImmutable::now($tz))];
        }

        $staff = app(StaffRoster::class)->staff();
        if ($this->dept !== 'all') {
            $staff = array_values(array_filter($staff, fn (array $s): bool => $s['department'] === $this->dept));
        }

        // Shifts for THIS property + week, keyed by "user_id|date" for O(1) cell lookup.
        $shiftMap = [];
        if ($property !== null) {
            Shift::query()
                ->where('property_id', $property->getKey())
                ->whereBetween('date', [$days[0]['date'], $days[6]['date']])
                ->get()
                ->each(function (Shift $s) use (&$shiftMap): void {
                    $shiftMap[$s->user_id.'|'.$s->date->toDateString()] = $s->shift_type;
                });
        }

        $rows = array_map(function (array $s) use ($days, $shiftMap): array {
            $cells = array_map(function (array $day) use ($s, $shiftMap): array {
                $type = $shiftMap[$s['id'].'|'.$day['date']] ?? ShiftType::OFF;

                return [
                    'date' => $day['date'],
                    'type' => $type,
                    'label' => ShiftType::label($type),
                    'hours' => ShiftType::hours($type),
                    'working' => ShiftType::isWorking($type),
                ];
            }, $days);

            return ['staff' => $s, 'cells' => $cells];
        }, $staff);

        return view('livewire.operator.schedule', [
            'viewer' => $this->viewer(),
            'property' => $property,
            'days' => $days,
            'rows' => $rows,
            'weekLabel' => $monday->format('j M').' – '.$monday->addDays(6)->format('j M Y'),
            'isThisWeek' => $this->week === 0,
            'departments' => StaffDepartment::FILTERS,
            'shiftTypes' => ShiftType::ALL,
            'canManage' => (bool) auth()->user()?->can('schedule.manage'),
        ]);
    }
}
