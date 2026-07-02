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
}
