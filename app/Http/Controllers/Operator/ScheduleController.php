<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Schedule\ScheduleException;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\OpenShift;
use App\Models\Property;
use App\Models\SchedulePublication;
use App\Models\User;
use App\Notifications\Operator\SchedulePublishedNotification;
use App\Services\LeaveService;
use App\Services\PdfService;
use App\Services\ShiftRosterService;
use App\Support\Branding\PropertyBrand;
use App\Support\Events\AuditEventRecorder;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Schedule\RosterWeek;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffDepartment;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Team Schedule WRITES + the schedule PDF (Phase 7d). Shift assign + the time-off GRANT/APPROVE/DENY are
 * manager-gated (`can:schedule.manage`, invariant #30); requesting OWN time off is broad (`can:schedule.view`,
 * self-only). Writes are real POSTs (NOT Livewire actions) so the /app tenant + property middleware runs and a
 * shift lands on the SELECTED property ({@see PropertyContext::id()}, never `->first()`).
 *
 * 🔒 SELF-APPROVAL is impossible ({@see LeaveService}): a manager can't approve/deny a request that is for
 * themselves, nor grant their own leave. 🪤 REAL CONFLICT: assigning a working shift on an approved-leave day
 * is refused ({@see ShiftRosterService}). 🪤 REAL STAFF: a submitted `user_id` is re-validated against
 * {@see StaffRoster}.
 */
class ScheduleController extends Controller
{
    public function __construct(
        private readonly ShiftRosterService $roster,
        private readonly LeaveService $leave,
    ) {}

    // ── SHIFTS ──────────────────────────────────────────────────────────────────────────────────────

    public function assign(Request $request, StaffRoster $staffRoster): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $property = $this->currentProperty();

        $validated = $request->validate([
            'user_id' => ['required', 'string', Rule::in($staffRoster->staffIds())],
            'date' => ['required', 'date'],
            'shift_type' => ['required', Rule::in(ShiftType::ALL)],
        ]);

        $staff = User::query()->findOrFail($validated['user_id']);
        $date = CarbonImmutable::parse((string) $validated['date']);

        try {
            $this->roster->assign($property, $staff, $date, (string) $validated['shift_type']);
        } catch (ScheduleException $e) {
            return $this->back($request)->with('schedule_error', $e->getMessage());
        }

        return $this->back($request)->with('schedule_status', 'Shift updated.');
    }

    // ── TIME OFF ────────────────────────────────────────────────────────────────────────────────────

    /** The acting staff member requests THEIR OWN time off (pending). Self-only — never someone else's. */
    public function requestTimeOff(Request $request): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        /** @var User $staff */
        $staff = $request->user();
        $this->leave->request($staff, (string) $validated['start_date'], (string) $validated['end_date'], $validated['reason'] ?? null);

        return $this->back($request)->with('schedule_status', 'Time-off request submitted.');
    }

    /** A manager grants approved leave directly to a staff member (never themselves — self-grant is refused). */
    public function grantLeave(Request $request, StaffRoster $staffRoster): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'string', Rule::in($staffRoster->staffIds())],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        /** @var User $manager */
        $manager = $request->user();
        $staff = User::query()->findOrFail($validated['user_id']);

        try {
            $this->leave->grant($staff, $manager, (string) $validated['start_date'], (string) $validated['end_date'], $validated['reason'] ?? null);
        } catch (ScheduleException $e) {
            return $this->back($request)->with('schedule_error', $e->getMessage());
        }

        return $this->back($request)->with('schedule_status', 'Leave added.');
    }

    public function approveLeave(Request $request, string $leave): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $row = LeaveRequest::query()->whereKey($leave)->firstOrFail();   // tenant-scoped → cross-org 404

        /** @var User $actor */
        $actor = $request->user();

        try {
            $this->leave->approve($row, $actor);
        } catch (ScheduleException $e) {
            return $this->back($request)->with('schedule_error', $e->getMessage());
        }

        return $this->back($request)->with('schedule_status', 'Time off approved.');
    }

    public function denyLeave(Request $request, string $leave): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $row = LeaveRequest::query()->whereKey($leave)->firstOrFail();

        /** @var User $actor */
        $actor = $request->user();

        try {
            $this->leave->deny($row, $actor);
        } catch (ScheduleException $e) {
            return $this->back($request)->with('schedule_error', $e->getMessage());
        }

        return $this->back($request)->with('schedule_status', 'Time off declined.');
    }

    // ── PUBLISH WEEK (minimal-real: finalize + notify; edits stay live) ───────────────────────────────

    public function publishWeek(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $property = $this->currentProperty();
        $week = (int) $request->input('week', '0');
        $dept = StaffDepartment::isFilter((string) $request->input('dept', 'all')) ? (string) $request->input('dept', 'all') : 'all';
        $roster = RosterWeek::build($property, $week, $dept);
        $weekStart = (string) ($roster->days[0]['date'] ?? CarbonImmutable::today()->startOfWeek()->toDateString());

        $publication = SchedulePublication::updateOrCreate(
            ['property_id' => $property->id, 'week_starts_on' => $weekStart],
            ['published_at' => now(), 'published_by' => $request->user()?->getKey()],
        );
        $audit->record('schedule.published', $publication, null, ['week_starts_on' => $weekStart]);

        // Notify each rostered staff member (real FeedNotification; edits stay live afterwards).
        $label = CarbonImmutable::parse($weekStart)->format('j M');
        foreach ($roster->rows as $row) {
            User::query()->find($row['staff']['id'])?->notify(new SchedulePublishedNotification($property->name, $label));
        }

        return $this->back($request)->with('schedule_status', 'Week published — the team has been notified.');
    }

    // ── OPEN SHIFTS (unassigned slots; fill = create a real Shift + delete the open row) ──────────────

    public function createOpenShift(Request $request, AuditEventRecorder $audit): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $property = $this->currentProperty();
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'shift_type' => ['required', Rule::in(['early', 'day', 'late'])],
            'department' => ['nullable', 'string', 'max:40'],
        ]);

        $open = OpenShift::create([
            'property_id' => $property->id,
            'date' => CarbonImmutable::parse((string) $validated['date'])->toDateString(),
            'shift_type' => (string) $validated['shift_type'],
            'department' => $validated['department'] ?? null,
            'created_by' => $request->user()?->getKey(),
        ]);
        $audit->record('schedule.open_shift_created', $open, null, ['date' => $open->date->toDateString(), 'shift_type' => $open->shift_type]);

        return $this->back($request)->with('schedule_status', 'Open shift added.');
    }

    /** Assign an open shift to a staff member — creates a REAL Shift through the certified service, then removes the open slot. */
    public function fillOpenShift(Request $request, string $openShift, StaffRoster $staffRoster, AuditEventRecorder $audit): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $property = $this->currentProperty();
        $validated = $request->validate(['user_id' => ['required', 'string', Rule::in($staffRoster->staffIds())]]);
        $open = OpenShift::query()->findOrFail($openShift);   // tenant-scoped → cross-org 404
        $staff = User::query()->findOrFail($validated['user_id']);

        try {
            DB::transaction(function () use ($property, $staff, $open, $audit): void {
                $this->roster->assign($property, $staff, CarbonImmutable::parse($open->date->toDateString()), (string) $open->shift_type);
                $audit->record('schedule.open_shift_filled', $open, null, ['user_id' => (string) $staff->getKey()]);
                $open->delete();
            });
        } catch (ScheduleException $e) {
            return $this->back($request)->with('schedule_error', $e->getMessage());
        }

        return $this->back($request)->with('schedule_status', 'Open shift assigned.');
    }

    public function removeOpenShift(Request $request, string $openShift, AuditEventRecorder $audit): RedirectResponse
    {
        if ($readOnly = $this->guardReadOnly($request)) {
            return $readOnly;
        }

        $open = OpenShift::query()->findOrFail($openShift);
        $open->delete();
        $audit->record('schedule.open_shift_removed', null, ['open_shift_id' => $openShift], null);

        return $this->back($request)->with('schedule_status', 'Open shift removed.');
    }

    // ── PDF ─────────────────────────────────────────────────────────────────────────────────────────

    /** The week's roster as a PDF — the REAL shifts/staff/leave for the SELECTED property (reconciles to the grid). */
    public function pdf(Request $request, PdfService $pdf): Response
    {
        $property = $this->currentProperty();
        $week = (int) $request->query('week', '0');
        $dept = (string) $request->query('dept', 'all');
        $dept = StaffDepartment::isFilter($dept) ? $dept : 'all';

        $roster = RosterWeek::build($property, $week, $dept);

        // #102 — the time-off list (real LeaveRequest rows for this rota's staff overlapping the week).
        $staffIds = array_map(fn (array $row): string => (string) $row['staff']['id'], $roster->rows);
        $days = $roster->days;
        $weekStart = $days[0]['date'] ?? CarbonImmutable::today()->toDateString();
        $lastDay = end($days);
        $weekEnd = is_array($lastDay) ? ($lastDay['date'] ?? $weekStart) : $weekStart;
        $timeOff = LeaveRequest::query()
            ->whereIn('user_id', $staffIds)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('end_date', '>=', $weekStart)
            ->whereDate('start_date', '<=', $weekEnd)
            ->with('user:id,name')
            ->orderBy('start_date')
            ->get()
            ->map(fn (LeaveRequest $l): array => [
                'name' => (string) ($l->user->name ?? '—'),
                'kind' => ucfirst((string) ($l->reason ?: 'Leave')),
                'days' => $l->rangeLabel(),
                'status' => ucfirst((string) $l->status),
            ])->all();

        return $pdf->download('pdfs.operator-schedule', [
            'property' => $property,
            'roster' => $roster,
            'deptLabel' => StaffDepartment::label($dept),
            'brand' => PropertyBrand::for($property),
            'timeOff' => $timeOff,
        ], 'schedule-'.$property->getKey().'-week-'.$week.'.pdf');
    }

    // ── helpers ─────────────────────────────────────────────────────────────────────────────────────

    /** The SELECTED property (context set by the /app middleware) — never `->first()`, never a request id. */
    private function currentProperty(): Property
    {
        return Property::query()->findOrFail(app(PropertyContext::class)->id());
    }

    /** Back to the roster, preserving the week + department the user was viewing. */
    private function back(Request $request): RedirectResponse
    {
        $week = (int) $request->input('week', '0');
        $dept = (string) $request->input('dept', 'all');

        return redirect()->route('app.schedule', [
            'week' => $week,
            'dept' => StaffDepartment::isFilter($dept) ? $dept : 'all',
        ]);
    }

    /**
     * #104b GATE 3 — read-only guard (a real gap the audit found: the schedule writes didn't respect the paused
     * workspace though the banner showed, unlike RateActionController/HousekeepingActionController). A paused/
     * lapsed workspace refuses every roster write. Returns a redirect to short-circuit, or null to proceed.
     */
    private function guardReadOnly(Request $request): ?RedirectResponse
    {
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->back($request)->with('schedule_error', 'Your workspace is read-only right now — the roster is paused.');
        }

        return null;
    }
}
