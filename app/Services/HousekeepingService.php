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
use RuntimeException;

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

        $this->assertTransition($task, $status);

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

        // ⚠️ F-17 — WHO cleaned the room. `domain_events` has NO actor column, so the domain event above
        // proves the change happened and can never say who made it. This is the missing sibling: the three
        // other writers in this very file (markUnitHousekeepingStatus, assign, unassign) already record here,
        // and one of their docblocks even claims "domain + audit" for the pair. ⚠️ PII-SAFE by construction —
        // a status and an id, no names, no contact details (these rows are operator-readable).
        $this->audit->record(
            'housekeeping.task_updated',
            $task,
            ['status' => $fromStatus],
            ['status' => $status],
            actorType: $by !== null ? 'user' : null,
            actorId: $by?->getKey() !== null ? (string) $by->getKey() : null,
        );

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

    /**
     * ⚠️ THE STATUS LADDER'S BOUND (F-15) — the source-state check this transition never had. Copied in shape
     * from the reference implementation {@see MaintenanceService::assertTransition()}: the guard
     * lives in the SERVICE, so EVERY entrance meets it (a guard on one entrance is not a guard), and the
     * controller keeps no status logic of its own — it catches the refusal and flashes it.
     *
     * ⚠️⚠️ THE MAP IS DELIBERATELY THE MOST PERMISSIVE ONE THAT CLOSES THE PROVEN DEFECT. Which moves SHOULD be
     * legal is a PRODUCT decision and is not settled here; inventing policy inside a guard would be worse than
     * the missing guard. So this refuses only what is clearly illegal and leaves every ambiguous move working
     * exactly as it does today, pending a founder ruling (see docs/HOUSEKEEPING-LADDER.md):
     *
     *   • `in_progress` and `clean` accept EVERY source state. `in_progress` is a PROGRESS MARKER, so skipping
     *     it loses no assertion — a housekeeper who cleans a room without pressing Start is doing the job, and
     *     refusing them would break a shift. Backward moves (a clean room found dirty, a wrong inspection) are
     *     likewise left open. ⚠️ These are AMBIGUOUS-AND-PERMITTED, not endorsed.
     *   • `inspected` accepts ONLY `clean` (the real step) and `inspected` (a double-press, unchanged). It is
     *     refused from `pending` and `in_progress` because `clean` is not a marker but an ASSERTION — that a
     *     person declared this room cleaned. Reaching guest-ready without it is exactly F-15: a dirty room
     *     became guest-ready with `started_at` NULL, and `inspected_by` named the person who skipped the work.
     *     That also collapses the cleaner/inspector separation F-17 shows is recorded nowhere else.
     */
    private const ALLOWED_FROM = [
        'pending' => ['pending', 'in_progress', 'clean', 'inspected'],
        'in_progress' => ['pending', 'in_progress', 'clean', 'inspected'],
        'clean' => ['pending', 'in_progress', 'clean', 'inspected'],
        'inspected' => ['clean', 'inspected'],
    ];

    /**
     * Refuse a transition the ladder does not permit. Throws {@see RuntimeException} exactly as the maintenance
     * reference does, so a caller that does not catch it fails loudly rather than writing a silent bad row.
     */
    private function assertTransition(HousekeepingTask $task, string $to): void
    {
        $from = (string) $task->status;

        if (! array_key_exists($to, self::ALLOWED_FROM)) {
            throw new RuntimeException("Unsupported housekeeping status \"{$to}\".");
        }

        if (! in_array($from, self::ALLOWED_FROM[$to], true)) {
            throw new RuntimeException("Cannot mark a {$from} housekeeping task {$to}.");
        }
    }
}
