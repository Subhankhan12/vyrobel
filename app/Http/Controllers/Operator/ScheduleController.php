<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Exceptions\Schedule\ScheduleException;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Property;
use App\Models\User;
use App\Services\LeaveService;
use App\Services\PdfService;
use App\Services\ShiftRosterService;
use App\Support\Property\PropertyContext;
use App\Support\Schedule\RosterWeek;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffDepartment;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    // ── PDF ─────────────────────────────────────────────────────────────────────────────────────────

    /** The week's roster as a PDF — the REAL shifts/staff/leave for the SELECTED property (reconciles to the grid). */
    public function pdf(Request $request, PdfService $pdf): Response
    {
        $property = $this->currentProperty();
        $week = (int) $request->query('week', '0');
        $dept = (string) $request->query('dept', 'all');
        $dept = StaffDepartment::isFilter($dept) ? $dept : 'all';

        $roster = RosterWeek::build($property, $week, $dept);

        return $pdf->download('pdfs.operator-schedule', [
            'property' => $property,
            'roster' => $roster,
            'deptLabel' => StaffDepartment::label($dept),
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
}
