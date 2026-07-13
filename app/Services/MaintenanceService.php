<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MaintenanceTicket;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Money\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Maintenance-ticket lifecycle (mirrors the HousekeepingService pattern). Guarded status transitions —
 * `open → in_progress → resolved`, plus `open|in_progress → cancelled` — with illegal jumps rejected.
 * Emits post-commit `maintenance_ticket.*` domain events; these are ONE-DIRECTIONAL and are NOT in the
 * availability-recompute trigger set, so a plain ticket never touches `sold`/availability.
 *
 * NOTE: the out-of-service ↔ availability wiring (Step 2) and the cost → owner-expense wiring (Step 3)
 * are layered on top of this service — this file is the ticket lifecycle ONLY.
 */
class MaintenanceService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Open a new ticket (status=open). Org is auto-filled by BelongsToOrganization; `reported_by`
     * defaults to the acting user.
     *
     * @param  array{unit_id: string, title: string, description?: string|null, category?: string, priority?: string, takes_out_of_service?: bool, reported_by?: string|null, assigned_to?: string|null}  $data
     */
    public function open(array $data): MaintenanceTicket
    {
        $ticket = DB::transaction(fn (): MaintenanceTicket => MaintenanceTicket::create([
            'unit_id' => $data['unit_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? 'other',
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'takes_out_of_service' => $data['takes_out_of_service'] ?? false,
            'reported_by' => $data['reported_by'] ?? Auth::id(),
            'assigned_to' => $data['assigned_to'] ?? null,
            'opened_at' => now(),
        ]));

        $this->domainEvents->record('maintenance_ticket.opened', $ticket, ['unit_id' => (string) $ticket->unit_id]);
        $this->audit->record('maintenance_ticket.opened', $ticket, null, ['status' => 'open']);

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

        return $ticket->refresh();
    }

    /**
     * Resolve the ticket (open|in_progress → resolved), optionally recording a cost in minor units.
     * A resolved ticket is terminal — resolving again throws (a cost correction is an append-only
     * reversal, added in Step 3, never an in-place edit).
     */
    public function resolve(MaintenanceTicket $ticket, ?Money $cost = null): MaintenanceTicket
    {
        $this->assertTransition($ticket, ['open', 'in_progress'], 'resolve');
        $from = (string) $ticket->status;

        DB::transaction(function () use ($ticket, $cost): void {
            $ticket->update([
                'status' => 'resolved',
                'cost_minor' => $cost?->amountMinor(),
                'currency' => $cost?->currency(),
                'resolved_at' => now(),
            ]);
        });

        $this->domainEvents->record('maintenance_ticket.resolved', $ticket, [
            'unit_id' => (string) $ticket->unit_id,
            'cost_minor' => $cost?->amountMinor(),
        ]);
        $this->audit->record('maintenance_ticket.resolved', $ticket, ['status' => $from], ['status' => 'resolved']);

        return $ticket->refresh();
    }

    /** Cancel the ticket (open|in_progress → cancelled). Terminal; illegal from a terminal state. */
    public function cancel(MaintenanceTicket $ticket): MaintenanceTicket
    {
        $this->assertTransition($ticket, ['open', 'in_progress'], 'cancel');
        $from = (string) $ticket->status;

        $ticket->update(['status' => 'cancelled']);

        $this->domainEvents->record('maintenance_ticket.cancelled', $ticket, ['unit_id' => (string) $ticket->unit_id]);
        $this->audit->record('maintenance_ticket.cancelled', $ticket, ['status' => $from], ['status' => 'cancelled']);

        return $ticket->refresh();
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
