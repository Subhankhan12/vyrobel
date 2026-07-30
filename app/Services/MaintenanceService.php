<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Availability;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\OwnerExpense;
use App\Models\Unit;
use App\Models\UnitStatusLog;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Maintenance-ticket lifecycle (mirrors the HousekeepingService pattern). Guarded status transitions —
 * `open → in_progress → resolved`, plus `open|in_progress → cancelled` — with illegal jumps rejected.
 * Emits post-commit `maintenance_ticket.*` domain events; these are ONE-DIRECTIONAL and are NOT in the
 * availability-recompute trigger set, so a plain ticket never touches `sold`/availability.
 *
 * OUT OF SERVICE ↔ AVAILABILITY (Step 2): a `takes_out_of_service` ticket, when opened/started, transitions
 * its unit to `out_of_service` via `setUnitCondition` (a real condition_status transition + `unit_status_logs`
 * row), which emits `unit.condition_changed` over the room-type's published window. The EXISTING
 * `RecomputeAvailabilityOnInventoryChange` listener consumes that and recomputes — `out_of_service` is
 * already counted into `out_of_order`, so the unit is no longer offered. AvailabilityService and the
 * AssignmentService locking core are NOT modified; `sold` is never touched. Resolving/cancelling returns
 * the unit to service ONLY when no OTHER active `takes_out_of_service` ticket still holds it. An existing
 * booking on a now-OOS unit is NEVER auto-cancelled (flagged for manual handling).
 *
 * COST → OWNER EXPENSE (Step 3): resolving a costed ticket on an OWNER-owned unit posts a deductible
 * `owner_expense` via `OwnerExpenseService::recordExpense(category:'maintenance', source:$ticket)` — the
 * CERTIFIED path (owner-from-unit + deductibility from the agreement + the `owner_ledger` deduction line +
 * period-lock). NEVER a direct owner_ledger write. An operator-owned unit's cost is recorded on the ticket
 * ONLY (no owner_expense — explicit, not dropped). A cost correction reverses the expense append-only
 * (`voidExpense`) and re-posts — never an in-place ledger edit.
 */
class MaintenanceService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
        private OwnerExpenseService $expenses,
    ) {}

    /**
     * Open a new ticket (status=open). Org is auto-filled by BelongsToOrganization; `reported_by`
     * defaults to the acting user.
     *
     * @param  array{unit_id: string, title: string, description?: string|null, category?: string, priority?: string, takes_out_of_service?: bool, reported_by?: string|null, assigned_to?: string|null}  $data
     */
    public function open(array $data): MaintenanceTicket
    {
        $category = $data['category'] ?? 'other';

        // #89 — AUTO-ASSIGNMENT: with no explicit assignee, route the ticket to the category's configured
        // technician when auto-assign is on. This is what makes the settings map genuinely take effect (it was
        // dead config before). A missing map / disabled toggle leaves the ticket unassigned (honest).
        $assignedTo = $data['assigned_to'] ?? $this->autoAssignee((string) $category);

        $ticket = DB::transaction(fn (): MaintenanceTicket => MaintenanceTicket::create([
            'unit_id' => $data['unit_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $category,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'takes_out_of_service' => $data['takes_out_of_service'] ?? false,
            'reported_by' => $data['reported_by'] ?? Auth::id(),
            'assigned_to' => $assignedTo,
            'opened_at' => now(),
        ]));

        $this->domainEvents->record('maintenance_ticket.opened', $ticket, ['unit_id' => (string) $ticket->unit_id]);
        $this->audit->record('maintenance_ticket.opened', $ticket, null, ['status' => 'open']);

        $this->takeUnitOutIfNeeded($ticket);   // OOS ticket → unit out_of_service (Step 2)

        return $ticket->refresh();
    }

    /** Assign (or reassign) the ticket to a user. Allowed while active; idempotent for the same assignee. */
    public function assign(MaintenanceTicket $ticket, ?string $userId): MaintenanceTicket
    {
        $this->assertActive($ticket, 'assign');

        if ((string) $ticket->assigned_to === (string) $userId) {
            return $ticket;   // idempotent
        }

        $from = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $userId]);

        $this->domainEvents->record('maintenance_ticket.assigned', $ticket, ['assigned_to' => $userId]);
        $this->audit->record('maintenance_ticket.assigned', $ticket, ['assigned_to' => $from], ['assigned_to' => $userId]);

        return $ticket->refresh();
    }

    /** Start work: open → in_progress. Idempotent if already in_progress; illegal from a terminal state. */
    public function start(MaintenanceTicket $ticket): MaintenanceTicket
    {
        if ($ticket->status === 'in_progress') {
            return $ticket;   // idempotent
        }

        $this->assertTransition($ticket, ['open'], 'start');
        $ticket->update(['status' => 'in_progress']);

        $this->domainEvents->record('maintenance_ticket.started', $ticket, []);
        $this->audit->record('maintenance_ticket.started', $ticket, ['status' => 'open'], ['status' => 'in_progress']);

        $this->takeUnitOutIfNeeded($ticket->refresh());   // idempotent — no-op if already out of service

        return $ticket->refresh();
    }

    /**
     * Resolve the ticket (open|in_progress → resolved), optionally recording a cost in minor units.
     * A resolved ticket is terminal — resolving again throws (a cost correction is an append-only
     * reversal, added in Step 3, never an in-place edit).
     */
    public function resolve(MaintenanceTicket $ticket, ?Money $cost = null, ?Money $labor = null, ?Money $parts = null): MaintenanceTicket
    {
        $this->assertTransition($ticket, ['open', 'in_progress'], 'resolve');
        $from = (string) $ticket->status;

        // #89 — the REAL labor/parts split: when either is given, the TOTAL is their sum (labor + parts ===
        // cost_minor, #17), and that total is what posts to the owner expense (the tie is unchanged). A plain
        // legacy `$cost` (no split) records as all-labour so labor + parts still equals the total.
        $split = $this->costSplit($cost, $labor, $parts);
        $total = $split['total'];   // ?Money — the figure the owner_expense uses

        DB::transaction(function () use ($ticket, $split): void {
            $ticket->update([
                'status' => 'resolved',
                'cost_minor' => $split['cost_minor'],
                'labor_minor' => $split['labor_minor'],
                'parts_minor' => $split['parts_minor'],
                'currency' => $split['currency'],
                // The work is done → the part (if any) has been fitted; clear the awaiting-parts state.
                'awaiting_parts' => false,
                'part_name' => null,
                'part_eta' => null,
                'resolved_at' => now(),
            ]);
        });

        $this->domainEvents->record('maintenance_ticket.resolved', $ticket, [
            'unit_id' => (string) $ticket->unit_id,
            'cost_minor' => $split['cost_minor'],
        ]);
        $this->audit->record('maintenance_ticket.resolved', $ticket, ['status' => $from], ['status' => 'resolved']);

        if ($total !== null) {
            $this->postExpenseIfOwned($ticket->refresh(), $total);   // certified owner-expense path (owned units only)
        }
        $this->releaseUnitIfClear($ticket->refresh());   // back in service iff no other active OOS ticket

        return $ticket->refresh();
    }

    /**
     * Flag / clear the "waiting on a part" state on an ACTIVE ticket (#89 — the Parts-on-order panel). Real
     * state, never a fabricated parts row: an operator records the part they've ordered + its ETA.
     */
    public function setAwaitingParts(MaintenanceTicket $ticket, bool $awaiting, ?string $partName = null, ?string $partEta = null): MaintenanceTicket
    {
        $this->assertActive($ticket, 'update parts on');

        $ticket->update([
            'awaiting_parts' => $awaiting,
            'part_name' => $awaiting ? $partName : null,
            'part_eta' => $awaiting ? $partEta : null,
        ]);

        $this->domainEvents->record('maintenance_ticket.parts_updated', $ticket, ['awaiting_parts' => $awaiting, 'part_name' => $partName]);
        $this->audit->record('maintenance_ticket.parts_updated', $ticket, null, ['awaiting_parts' => $awaiting]);

        return $ticket->refresh();
    }

    /**
     * The org's configured technician for a category when auto-assign is on — else null. One row per org
     * ({@see MaintenanceSetting}), tenant-scoped; the assignee was validated as an org member on write.
     */
    private function autoAssignee(string $category): ?string
    {
        $setting = MaintenanceSetting::query()->first();   // tenant-scoped, one per org
        if ($setting === null || ! $setting->auto_assign_enabled) {
            return null;
        }

        $id = $setting->{'assignee_'.$category.'_id'} ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * Reconcile a resolve into cost_minor + labor_minor + parts_minor with labor + parts === cost_minor.
     *
     * @return array{cost_minor: ?int, labor_minor: ?int, parts_minor: ?int, currency: ?string, total: ?Money}
     */
    private function costSplit(?Money $cost, ?Money $labor, ?Money $parts): array
    {
        if ($labor !== null || $parts !== null) {
            $currency = ($labor ?? $parts)->currency();
            $laborMinor = $labor?->amountMinor() ?? 0;
            $partsMinor = $parts?->amountMinor() ?? 0;
            $costMinor = $laborMinor + $partsMinor;

            return ['cost_minor' => $costMinor, 'labor_minor' => $laborMinor, 'parts_minor' => $partsMinor, 'currency' => $currency, 'total' => Money::of($costMinor, $currency)];
        }

        if ($cost !== null) {
            // Legacy single-total resolve → recorded as all-labour so the split still sums to the total.
            return ['cost_minor' => $cost->amountMinor(), 'labor_minor' => $cost->amountMinor(), 'parts_minor' => 0, 'currency' => $cost->currency(), 'total' => $cost];
        }

        return ['cost_minor' => null, 'labor_minor' => null, 'parts_minor' => null, 'currency' => null, 'total' => null];
    }

    /**
     * Correct (or void, when `$newCost` is null) the cost of a RESOLVED ticket. Append-only: any existing
     * owner_expense from this ticket is reversed via `OwnerExpenseService::voidExpense` (a new reversing
     * ledger line — the original is never edited), then a fresh expense is posted for the new value on an
     * owned unit. The ticket's recorded cost is updated; the money history lives in the append-only ledger.
     */
    public function correctCost(MaintenanceTicket $ticket, ?Money $newCost): MaintenanceTicket
    {
        $this->assertTransition($ticket, ['resolved'], 'correct the cost of');

        // Reverse the prior expense from this ticket (append-only) before re-posting.
        $existing = OwnerExpense::query()
            ->where('source_type', $ticket->getMorphClass())
            ->where('source_id', $ticket->getKey())
            ->whereNull('voided_at')
            ->first();
        if ($existing !== null) {
            $this->expenses->voidExpense($existing);
        }

        // Keep the split consistent with the corrected total (recorded as all-labour when no split is given).
        $ticket->update([
            'cost_minor' => $newCost?->amountMinor(),
            'labor_minor' => $newCost?->amountMinor(),
            'parts_minor' => $newCost !== null ? 0 : null,
            'currency' => $newCost?->currency(),
        ]);

        if ($newCost !== null) {
            $this->postExpenseIfOwned($ticket->refresh(), $newCost);
        }

        $this->domainEvents->record('maintenance_ticket.cost_corrected', $ticket, ['cost_minor' => $newCost?->amountMinor()]);
        $this->audit->record('maintenance_ticket.cost_corrected', $ticket, null, ['cost_minor' => $newCost?->amountMinor()]);

        return $ticket->refresh();
    }

    /**
     * Post the maintenance cost as an owner expense — ONLY when the unit is owner-owned. An operator-owned
     * unit (`owner_id === null`) records the cost on the ticket alone; NO owner_expense is created (the
     * operator's cost, explicit). Goes entirely through the certified OwnerExpenseService (owner-from-unit,
     * deductibility from the agreement, the owner_ledger line, period-lock) — never a direct ledger write.
     */
    private function postExpenseIfOwned(MaintenanceTicket $ticket, Money $cost): void
    {
        $unit = Unit::query()->find($ticket->unit_id);
        if ($unit === null || $unit->owner_id === null) {
            return;
        }

        $this->expenses->recordExpense([
            'unit_id' => (string) $ticket->unit_id,
            'category' => 'maintenance',
            'description' => (string) $ticket->title,
            'amount_minor' => $cost->amountMinor(),
            'currency' => $cost->currency(),
            'expense_date' => ($ticket->resolved_at ?? CarbonImmutable::now())->toDateString(),
            'source' => $ticket,
            'created_by' => Auth::id(),
            // Wave C · GATE 2 — an operator-RESOLVED maintenance ticket carries a real, actioned cost (the work is
            // done), so it charges DIRECTLY (approved) rather than entering the owner-approval queue. The owner-
            // approval gate governs general owner-facing expenses (recordExpense's default is pending).
            'approve_now' => true,
        ]);
    }

    /** Cancel the ticket (open|in_progress → cancelled). Terminal; illegal from a terminal state. */
    public function cancel(MaintenanceTicket $ticket): MaintenanceTicket
    {
        $this->assertTransition($ticket, ['open', 'in_progress'], 'cancel');
        $from = (string) $ticket->status;

        $ticket->update(['status' => 'cancelled']);

        $this->domainEvents->record('maintenance_ticket.cancelled', $ticket, ['unit_id' => (string) $ticket->unit_id]);
        $this->audit->record('maintenance_ticket.cancelled', $ticket, ['status' => $from], ['status' => 'cancelled']);

        $this->releaseUnitIfClear($ticket->refresh());   // back in service iff no other active OOS ticket

        return $ticket->refresh();
    }

    /**
     * Transition a unit's condition_status and record it in `unit_status_logs` (type=condition). Emits
     * `unit.condition_changed` (with the room-type's published window) — the EXISTING availability-recompute
     * listener consumes it. Idempotent: a no-op when already at the target status. NEVER mutates `sold`.
     */
    public function setUnitCondition(Unit $unit, string $toStatus, ?User $by = null): ?UnitStatusLog
    {
        $fromStatus = (string) $unit->condition_status;
        if ($fromStatus === $toStatus) {
            return null;   // idempotent
        }

        $log = DB::transaction(function () use ($unit, $fromStatus, $toStatus, $by): UnitStatusLog {
            $unit->update(['condition_status' => $toStatus]);

            return UnitStatusLog::create([
                'unit_id' => $unit->getKey(),
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'type' => 'condition',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'created_at' => now(),
            ]);
        });

        // Inventory-STRUCTURE event the availability listener already consumes → recompute
        // total/out_of_order/blocked (out_of_service is counted into out_of_order). This never touches sold.
        [$start, $end] = $this->publishedWindow((string) $unit->room_type_id);
        $this->domainEvents->record('unit.condition_changed', $unit, [
            'room_type_id' => (string) $unit->room_type_id,
            'start_date' => $start,
            'end_date' => $end,
            'from' => $fromStatus,
            'to' => $toStatus,
        ]);
        $this->audit->record('unit.condition_changed', $unit, ['condition_status' => $fromStatus], ['condition_status' => $toStatus]);

        return $log;
    }

    /** Take an IN-SERVICE unit out of service for an OOS ticket. Never clobbers an existing out_of_order state. */
    private function takeUnitOutIfNeeded(MaintenanceTicket $ticket): void
    {
        if (! $ticket->takes_out_of_service) {
            return;
        }

        $unit = Unit::query()->find($ticket->unit_id);
        if ($unit !== null && (string) $unit->condition_status === 'in_service') {
            $this->setUnitCondition($unit, 'out_of_service');
        }
    }

    /**
     * Return the unit to service ONLY if this closed ticket was an OOS ticket AND no OTHER active
     * (open/in_progress) OOS ticket still holds the unit (the two-tickets case). Only ever clears an
     * `out_of_service` set by maintenance — never an unrelated out_of_order.
     */
    private function releaseUnitIfClear(MaintenanceTicket $ticket): void
    {
        if (! $ticket->takes_out_of_service) {
            return;
        }

        $stillHeld = MaintenanceTicket::query()
            ->where('unit_id', $ticket->unit_id)
            ->where('id', '!=', $ticket->getKey())
            ->where('takes_out_of_service', true)
            ->whereIn('status', ['open', 'in_progress'])
            ->exists();

        if ($stillHeld) {
            return;   // another active OOS ticket still holds the unit
        }

        $unit = Unit::query()->find($ticket->unit_id);
        if ($unit !== null && (string) $unit->condition_status === 'out_of_service') {
            $this->setUnitCondition($unit, 'in_service');
        }
    }

    /**
     * The `[start, end)` window covering the room-type's currently-published availability (min..max of
     * `availability.date`), so the recompute updates every offered night. A minimal window if none published.
     *
     * @return array{0: string, 1: string}
     */
    private function publishedWindow(string $roomTypeId): array
    {
        $min = Availability::query()->where('room_type_id', $roomTypeId)->min('date');
        $max = Availability::query()->where('room_type_id', $roomTypeId)->max('date');

        if (! is_string($min) || ! is_string($max)) {
            $today = CarbonImmutable::now()->toDateString();

            return [$today, CarbonImmutable::now()->addDay()->toDateString()];
        }

        return [
            CarbonImmutable::parse($min)->toDateString(),
            CarbonImmutable::parse($max)->addDay()->toDateString(),   // half-open, inclusive of the max night
        ];
    }

    /** @param list<string> $allowedFrom */
    private function assertTransition(MaintenanceTicket $ticket, array $allowedFrom, string $action): void
    {
        if (! in_array($ticket->status, $allowedFrom, true)) {
            throw new RuntimeException("Cannot {$action} a {$ticket->status} maintenance ticket.");
        }
    }

    private function assertActive(MaintenanceTicket $ticket, string $action): void
    {
        if (! $ticket->isActive()) {
            throw new RuntimeException("Cannot {$action} a {$ticket->status} maintenance ticket.");
        }
    }
}
