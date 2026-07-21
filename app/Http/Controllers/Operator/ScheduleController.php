<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\User;
use App\Services\ShiftRosterService;
use App\Support\Property\PropertyContext;
use App\Support\Schedule\ShiftType;
use App\Support\Schedule\StaffDepartment;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Team Schedule WRITES (Phase 7d · SG1) — assign / clear a staff shift. Gated `can:schedule.manage` at the
 * ROUTE (a manager-level act, NOT Front Desk; invariant #30). The write is a real POST — NOT a Livewire action
 * — so it passes through the /app tenant + property middleware, and the shift lands on the SELECTED property
 * ({@see PropertyContext::id()}, never `->first()`, never a request-supplied property id).
 *
 * 🪤 REAL STAFF: the submitted `user_id` is re-validated against {@see StaffRoster::staffIds()} (the org's real
 * active staff), so a shift can never be assigned to a non-staff / cross-org user.
 */
class ScheduleController extends Controller
{
    public function __construct(private readonly ShiftRosterService $roster) {}

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

        $this->roster->assign($property, $staff, $date, (string) $validated['shift_type']);

        return $this->back($request)->with('schedule_status', 'Shift updated.');
    }

    /** The SELECTED property (context set by the /app middleware) — never `->first()`, never a request id. */
    private function currentProperty(): Property
    {
        return Property::query()->findOrFail(app(PropertyContext::class)->id());
    }

    /** Back to the roster, preserving the week + department the manager was viewing. */
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
