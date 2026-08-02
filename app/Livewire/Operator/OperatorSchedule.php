<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\OpenShift;
use App\Models\Property;
use App\Models\SchedulePublication;
use App\Services\LeaveService;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Schedule\RosterWeek;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffDepartment;
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

    /** GATE 9.2 — 'week' (the drag grid) or 'day' (a single day's agenda). Rides a GET param. */
    public string $view = 'week';

    /** GATE 9.2 — the focused day (Y-m-d) for the day view; empty = default to today (or the week's first day). */
    public string $focusDay = '';

    public function mount(): void
    {
        $this->week = (int) request()->query('week', '0');
        $dept = (string) request()->query('dept', 'all');
        $this->dept = StaffDepartment::isFilter($dept) ? $dept : 'all';
        $this->view = request()->query('view') === 'day' ? 'day' : 'week';
        $this->focusDay = (string) request()->query('day', '');
    }

    public function render(): View
    {
        $property = $this->currentProperty();
        $canManage = (bool) auth()->user()?->can('schedule.manage');

        // The ONE roster builder — the PDF renders from the same, so screen and PDF reconcile.
        $roster = RosterWeek::build($property, $this->week, $this->dept);

        // GATE 9.2 — the focused day for the day view: the ?day= within this week, else today, else the first day.
        $focusIdx = $this->resolveFocusIdx($roster);
        // Per-department coverage always spans ALL departments (never the grid's filter) — build once unfiltered.
        $coverageRoster = $this->dept === 'all' ? $roster : RosterWeek::build($property, $this->week, 'all');

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
            // #104 GATE 3d — the wireframe's "Coverage this week" summary, derived from the SAME builder the grid
            // renders (so it reconciles, never a re-query) — team size · total shifts · rostered hours.
            'coverage' => ['team' => $roster->teamSize(), 'shifts' => $roster->shiftCount(), 'hours' => $roster->hours()],
            // GATE 9.2 — the view toggle, the day-view agenda, and the HONEST per-department coverage bars.
            'view' => $this->view,
            'focusIdx' => $focusIdx,
            'dayView' => $this->view === 'day' ? $roster->dayView($focusIdx) : null,
            'coverageBars' => $coverageRoster->coverageByDepartment(),
            // #104b GATE 3 — the interactive-rota additions: the published state (finalize + notify), the open-shift
            // slots for the week, and per-day coverage — all derived for THIS property + week.
            'published' => $this->publishedState($property, $roster),
            'openShifts' => $this->openShiftsFor($property, $roster),
            'weekStart' => (string) ($roster->days[0]['date'] ?? ''),
            // A paused/lapsed workspace is read-only — the roster write controls hide (they'd 302 to a banner anyway).
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
        ]);
    }

    /**
     * GATE 9.2 — the day-view focus index (0–6). The explicit `?day=` if it falls in this week; otherwise today
     * when this week contains it; otherwise the first day. Never out of range.
     */
    private function resolveFocusIdx(RosterWeek $roster): int
    {
        if ($this->focusDay !== '') {
            foreach ($roster->days as $i => $day) {
                if ($day['date'] === $this->focusDay) {
                    return $i;
                }
            }
        }

        foreach ($roster->days as $i => $day) {
            if ($day['today']) {
                return $i;
            }
        }

        return 0;
    }

    /** The published stamp for this property + week (null = not published; the roster is always live regardless). */
    private function publishedState(Property $property, RosterWeek $roster): ?string
    {
        $weekStart = (string) ($roster->days[0]['date'] ?? '');
        if ($weekStart === '') {
            return null;
        }

        $pub = SchedulePublication::query()
            ->where('property_id', $property->id)
            ->whereDate('week_starts_on', $weekStart)
            ->first();

        return $pub?->published_at !== null ? CarbonImmutable::parse($pub->published_at)->diffForHumans() : null;
    }

    /**
     * The open (unassigned) shift slots for this property + week, staff-assignable.
     *
     * @return list<array{id: string, date: string, day_label: string, shift_label: string, department: ?string}>
     */
    private function openShiftsFor(Property $property, RosterWeek $roster): array
    {
        $days = collect($roster->days);
        $first = (string) ($days->first()['date'] ?? '');
        $last = (string) ($days->last()['date'] ?? '');
        if ($first === '') {
            return [];
        }

        return OpenShift::query()
            ->where('property_id', $property->id)
            ->whereBetween('date', [$first, $last])
            ->orderBy('date')
            ->get()
            ->map(fn (OpenShift $o): array => [
                'id' => (string) $o->getKey(),
                'date' => $o->date->toDateString(),
                'day_label' => $o->date->format('D j M'),
                'shift_label' => ShiftType::label((string) $o->shift_type),
                'department' => $o->department,
            ])->all();
    }
}
