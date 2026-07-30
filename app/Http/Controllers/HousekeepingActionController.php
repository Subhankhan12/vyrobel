<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Operator\RoomTypePhotoController;
use App\Models\HousekeepingTask;
use App\Models\Media;
use App\Models\ReservationUnit;
use App\Models\User;
use App\Services\HousekeepingService;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Property\PropertyContext;
use App\Support\Schedule\StaffRoster;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        if ($this->paused()) {
            return $this->pausedRedirect();
        }

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
            'priority' => ['sometimes', 'boolean'],
        ]);

        $model = HousekeepingTask::query()->findOrFail($task);          // tenant-scoped → cross-org 404

        if ($this->paused()) {
            return $this->pausedRedirect();
        }

        // "Clean next · priority" (the modal toggle) → bump the task to high priority (top of their queue).
        if ($request->boolean('priority')) {
            $model->update(['priority' => 'high']);
        }

        $user = User::query()->findOrFail((string) $validated['user_id']);

        $housekeeping->assign($model, $user);

        return redirect()->to(route('app.housekeeping'))
            ->with('hk_notice', "Assigned {$user->name} to unit {$model->unit?->code}.");
    }

    /**
     * ASSIGN ALL unassigned open tasks in scope, auto-balanced (#88) — POST → {@see HousekeepingService::assignAll}
     * → PRG, `housekeeping.manage`-gated. Waiting-guest rooms (a reservation arriving today for the unit) are
     * assigned first; each goes to the least-loaded REAL housekeeper. Tenant + property scoped; read-only respected.
     */
    public function assignAll(Request $request, HousekeepingService $housekeeping, StaffRoster $roster): RedirectResponse
    {
        if ($this->paused()) {
            return $this->pausedRedirect();
        }

        $readIds = app(PropertyContext::class)->readIds();
        $housekeeperIds = $roster->housekeeperIds();
        $housekeepers = User::query()->whereIn('id', $housekeeperIds)->get()->all();

        if ($housekeepers === []) {
            return redirect()->to(route('app.housekeeping'))->with('hk_error', 'No housekeepers on the roster to assign to.');
        }

        // Current open-task load per housekeeper (in scope) — the balance starts from reality, not zero.
        $load = HousekeepingTask::query()
            ->whereIn('property_id', $readIds)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as c')
            ->groupBy('assigned_to')
            ->pluck('c', 'assigned_to')->all();
        foreach ($housekeeperIds as $id) {
            $load[$id] = (int) ($load[$id] ?? 0);
        }

        // The units with a guest arriving today → their tasks go first ("guest waiting" priority). A reservation
        // has no direct unit — the assigned unit is on the `reservation_units` line — so read the active lines.
        $arrivingUnitIds = ReservationUnit::query()
            ->where('status', 'active')
            ->whereNotNull('unit_id')
            ->whereDate('check_in', CarbonImmutable::today()->toDateString())
            ->whereHas('reservation', fn ($q) => $q->whereIn('property_id', $readIds)->whereNotIn('status', ['cancelled', 'no_show']))
            ->pluck('unit_id')->filter()->all();

        $tasks = HousekeepingTask::query()
            ->whereIn('property_id', $readIds)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNull('assigned_to')
            ->get()
            ->sortBy([
                fn (HousekeepingTask $t): int => in_array((string) $t->unit_id, array_map('strval', $arrivingUnitIds), true) ? 0 : 1,
                fn (HousekeepingTask $t): int => $t->priority === 'high' ? 0 : 1,
                fn (HousekeepingTask $t): string => (string) $t->date,
            ]);

        $count = $housekeeping->assignAll($tasks, $housekeepers, $load, $request->user());

        return redirect()->to(route('app.housekeeping'))
            ->with('hk_notice', $count > 0 ? "{$count} ".($count === 1 ? 'room' : 'rooms').' assigned · queues balanced.' : 'Nothing to assign — every room already has a housekeeper.');
    }

    /**
     * Attach a PROOF photo to a task (#88) — the app's file-upload security discipline (as
     * {@see RoomTypePhotoController}): validate real image CONTENT, a generated
     * filename (no path traversal), the `public` disk, a `Media` row on the task (`collection='hk_proof'`).
     * `housekeeping.manage`-gated; tenant-scoped (`{task}` string → cross-org 404); read-only respected.
     */
    public function uploadPhoto(Request $request, string $task): RedirectResponse
    {
        $model = HousekeepingTask::query()->findOrFail($task);

        if ($this->paused()) {
            return $this->pausedRedirect();
        }

        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('photo');
        $ext = $file->extension() ?: 'jpg';                      // from the VALIDATED file, never the client name
        $name = (string) Str::ulid().'.'.$ext;
        $path = $file->storeAs('housekeeping/'.$model->getKey(), $name, 'public');

        Media::create([
            'mediable_type' => $model->getMorphClass(),
            'mediable_id' => $model->getKey(),
            'collection' => 'hk_proof',
            'disk' => 'public',
            'path' => $path,
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'alt' => 'Housekeeping proof · '.(string) $model->unit->code,
        ]);

        return redirect()->to(route('app.housekeeping', ['task' => $model->getKey()]))->with('hk_notice', 'Photo added.');
    }

    /** Remove a proof photo — deletes the stored bytes + the row. Tenant + task scoped; gated + read-only. */
    public function deletePhoto(Request $request, string $task, string $media): RedirectResponse
    {
        $model = HousekeepingTask::query()->findOrFail($task);

        if ($this->paused()) {
            return $this->pausedRedirect();
        }

        $row = Media::query()
            ->where('mediable_type', $model->getMorphClass())
            ->where('mediable_id', $model->getKey())
            ->findOrFail($media);

        Storage::disk((string) $row->disk)->delete((string) $row->path);
        $row->delete();

        return redirect()->to(route('app.housekeeping', ['task' => $model->getKey()]))->with('hk_notice', 'Photo removed.');
    }

    private function paused(): bool
    {
        return app(OperatorWorkspace::class)->isReadOnly();
    }

    private function pausedRedirect(): RedirectResponse
    {
        return redirect()->to(route('app.housekeeping'))
            ->with('hk_error', 'Your workspace is paused (read-only) — housekeeping changes are off until you resume.');
    }
}
