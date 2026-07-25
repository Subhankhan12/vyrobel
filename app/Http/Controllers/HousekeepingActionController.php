<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Models\User;
use App\Services\HousekeepingService;
use App\Support\Schedule\StaffRoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Housekeeping task status advance — the REAL cleaning-status action, driven from the styled
 * Housekeeping board (design pass · Operator Screen 3) via POST → action → redirect (PRG), the same
 * tenant-safe pattern as front-desk check-in/out. A normal POST re-runs the web middleware
 * (SetTenantForWebRequest sets context), so the mutation is genuinely tenant-scoped.
 *
 * `{task}` is a plain STRING route param (never route-model-bound), resolved here via the tenant-scoped
 * global scope AFTER the middleware — so a cross-org id 404s. The transition delegates to
 * {@see HousekeepingService::updateTaskStatus} (which also syncs the unit's cleanliness + writes the
 * append-only unit_status_log on clean/inspected); no schema/guarantee is touched. Only the real
 * `housekeeping_tasks.status` enum values are accepted — an unknown target is rejected, never invented.
 */
class HousekeepingActionController extends Controller
{
    /** Target statuses reachable from the board (forward moves through the real enum). */
    private const ALLOWED = ['in_progress', 'clean', 'inspected'];

    public function updateStatus(Request $request, HousekeepingService $housekeeping, string $task): RedirectResponse
    {
        $target = (string) $request->input('status');

        if (! in_array($target, self::ALLOWED, true)) {
            abort(422, 'Unsupported housekeeping status.');
        }

        $model = HousekeepingTask::query()->findOrFail($task);   // tenant-scoped → cross-org 404

        $housekeeping->updateTaskStatus($model, $target);

        $label = ucfirst(str_replace('_', ' ', $target));

        return redirect()->to(route('app.housekeeping'))
            ->with('hk_notice', "Unit {$model->unit?->code} marked {$label}.");
    }

    /**
     * Assign a HOUSEKEEPER to a task (Batch C · GATE 1) — POST → {@see HousekeepingService::assign} → PRG,
     * gated `housekeeping.manage` on the route. 🪤 REAL STAFF ONLY: the submitted `user_id` must be one of
     * this org's real housekeepers ({@see StaffRoster::housekeeperIds()} — active Membership ∩ the
     * Housekeeping department), so a fabricated / cross-org / non-housekeeper id is rejected (422) before the
     * service is called. `{task}` is a plain string resolved tenant-scoped here → a cross-org id 404s.
     */
    public function assign(Request $request, HousekeepingService $housekeeping, StaffRoster $roster, string $task): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', Rule::in($roster->housekeeperIds())],
        ]);

        $model = HousekeepingTask::query()->findOrFail($task);          // tenant-scoped → cross-org 404
        $user = User::query()->findOrFail((string) $validated['user_id']);

        $housekeeping->assign($model, $user);

        return redirect()->to(route('app.housekeeping'))
            ->with('hk_notice', "Assigned {$user->name} to unit {$model->unit?->code}.");
    }
}
