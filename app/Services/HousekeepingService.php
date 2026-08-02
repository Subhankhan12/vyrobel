<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HousekeepingTask;
use App\Models\Unit;
use App\Models\UnitStatusLog;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Housekeeping task + unit-cleanliness management. Operates entirely within the housekeeping
 * domain: it changes a unit's `housekeeping_status` (clean/dirty/inspected) — which does NOT
 * affect availability (only `condition_status` does) — and records every transition in the
 * shared append-only `unit_status_logs` (Step 2), never a parallel log.
 */
class HousekeepingService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Transition a unit's housekeeping status and record it in unit_status_logs.
     * Emits `unit.housekeeping_changed` — NOT an availability-recompute trigger.
     */
    public function markUnitHousekeepingStatus(Unit $unit, string $toStatus, ?User $by = null): UnitStatusLog
    {
        $fromStatus = (string) $unit->housekeeping_status;

        $log = DB::transaction(function () use ($unit, $fromStatus, $toStatus, $by): UnitStatusLog {
            $unit->update(['housekeeping_status' => $toStatus]);

            return UnitStatusLog::create([
                'unit_id' => $unit->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'type' => 'housekeeping',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'created_at' => now(),
            ]);
        });

        $this->domainEvents->record('unit.housekeeping_changed', $unit, ['from' => $fromStatus, 'to' => $toStatus]);
        $this->audit->record('unit.housekeeping_changed', $unit, ['housekeeping_status' => $fromStatus], ['housekeeping_status' => $toStatus]);

        return $log;
    }

    /**
     * Advance a housekeeping task's status. When it reaches clean/inspected, sync the unit's
     * housekeeping status (which writes its own unit_status_log entry).
     */
    public function updateTaskStatus(HousekeepingTask $task, string $status, ?User $by = null): HousekeepingTask
    {
        $fromStatus = (string) $task->status;

        DB::transaction(function () use ($task, $status, $by): void {
            $updates = ['status' => $status];

            // #88 — the real START of the clock, so the board's "Elapsed {m}/{exp}m" is genuine (set once).
            if ($status === 'in_progress' && $task->started_at === null) {
                $updates['started_at'] = now();
            }
            if ($status === 'clean' || $status === 'inspected') {
                $updates['completed_at'] = now();
            }
            if ($status === 'inspected') {
                $updates['inspected_by'] = $by?->getKey() ?? Auth::id();
            }

            $task->update($updates);
        });

        $this->domainEvents->record('housekeeping.task_updated', $task, ['from' => $fromStatus, 'to' => $status]);

        // Sync the unit's cleanliness (separate write → its own log entry).
        if (in_array($status, ['clean', 'inspected'], true)) {
            $unit = $task->unit;
            if ($unit !== null) {
                $this->markUnitHousekeepingStatus($unit, $status, $by);
            }
        }

        return $task->refresh();
    }

    /**
     * Assign a housekeeper to a task (Batch C · GATE 1) — sets `assigned_to` and records the change on the
     * append-only event trail (domain + audit). The write is a plain column update within the housekeeping
     * domain; it touches no availability/inventory. The CALLER is responsible for validating that $user is a
     * REAL housekeeper for this org ({@see StaffRoster::housekeeperIds()}), so a fabricated / cross-org /
     * non-housekeeper person can never be assigned — this method only performs the write + the events.
     */
    public function assign(HousekeepingTask $task, User $user, ?User $by = null): HousekeepingTask
    {
        $fromUserId = $task->assigned_to;

        DB::transaction(function () use ($task, $user): void {
            $task->update(['assigned_to' => $user->getKey()]);
        });

        $this->domainEvents->record('housekeeping.task_assigned', $task, [
            'from_user_id' => $fromUserId,
            'to_user_id' => (string) $user->getKey(),
        ]);
        $this->audit->record('housekeeping.task_assigned', $task, ['assigned_to' => $fromUserId], ['assigned_to' => (string) $user->getKey()]);

        return $task->refresh();
    }

    /**
     * UNASSIGN a task (clear `assigned_to`) — the "Undo" behind the assign toast. A plain column reset within the
     * housekeeping domain (touches no inventory/availability), recorded on the append-only event trail. No-op
     * (no event) when the task is already unassigned, so a stray/duplicate undo is harmless.
     */
    public function unassign(HousekeepingTask $task, ?User $by = null): HousekeepingTask
    {
        $fromUserId = $task->assigned_to;
        if ($fromUserId === null) {
            return $task;   // already unassigned — nothing to undo
        }

        DB::transaction(function () use ($task): void {
            $task->update(['assigned_to' => null]);
        });

        $this->domainEvents->record('housekeeping.task_unassigned', $task, ['from_user_id' => $fromUserId]);
        $this->audit->record('housekeeping.task_unassigned', $task, ['assigned_to' => $fromUserId], ['assigned_to' => null]);

        return $task->refresh();
    }

    /**
     * Auto-balance a batch of unassigned tasks across the real housekeepers (#88 — the "Assign all" button):
     * greedy LEAST-LOAD assignment, so each task goes to whoever currently carries the fewest open jobs (ties
     * broken by input order). The caller passes the tasks already ordered (waiting-guest rooms first) + the
     * real housekeepers + their current open-task load; each assignment goes through the certified
     * {@see assign()} (so every one is validated-by-the-caller, audited, and event-recorded). Returns the count.
     *
     * @param  iterable<HousekeepingTask>  $tasks  unassigned tasks, in priority order
     * @param  list<User>  $housekeepers  the real, validated housekeepers to balance across
     * @param  array<string, int>  $load  userId → current open-task count (mutated in-memory as we assign)
     */
    public function assignAll(iterable $tasks, array $housekeepers, array $load, ?User $by = null): int
    {
        if ($housekeepers === []) {
            return 0;
        }

        $assigned = 0;
        foreach ($tasks as $task) {
            // The least-loaded housekeeper right now (in-memory load, updated as we go so the batch balances).
            $target = $housekeepers[0];
            foreach ($housekeepers as $candidate) {
                if (($load[(string) $candidate->getKey()] ?? 0) < ($load[(string) $target->getKey()] ?? 0)) {
                    $target = $candidate;
                }
            }

            $this->assign($task, $target, $by);
            $load[(string) $target->getKey()] = ($load[(string) $target->getKey()] ?? 0) + 1;
            $assigned++;
        }

        return $assigned;
    }
}
