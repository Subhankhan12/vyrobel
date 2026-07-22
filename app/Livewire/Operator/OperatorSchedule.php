<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Services\LeaveService;
use App\Support\Schedule\RosterWeek;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffDepartment;
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
        $canManage = (bool) auth()->user()?->can('schedule.manage');

        // The ONE roster builder — the PDF renders from the same, so screen and PDF reconcile.
        $roster = RosterWeek::build($property, $this->week, $this->dept);

        // The manager's time-off queue: pending requests to approve/deny. Read for managers only.
        $pending = $canManage
            ? app(LeaveService::class)->pendingRequests()->map(fn ($r): array => [
                'id' => (string) $r->getKey(),
                'name' => (string) $r->user->name,
                'range' => $r->rangeLabel(),
                'reason' => (string) ($r->reason ?? ''),
                // A manager can't approve their OWN request (self-approval separation) — the UI shows a note
                // instead of the buttons, so it never invites the 403/guard.
                'is_self' => (string) $r->user_id === (string) auth()->id(),
            ])->all()
            : [];

        return view('livewire.operator.schedule', [
            'viewer' => $this->viewer(),
            'property' => $property,
            'days' => $roster->days,
            'rows' => $roster->rows,
            'weekLabel' => $roster->weekLabel,
            'isThisWeek' => $roster->isThisWeek,
            'conflictCount' => $roster->conflictCount,
            'departments' => StaffDepartment::FILTERS,
            'shiftTypes' => ShiftType::ALL,
            'canManage' => $canManage,
            'pending' => $pending,
        ]);
    }
}
