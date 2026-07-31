<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AuditEvent;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\OwnerExpense;
use App\Models\ReservationUnit;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Maintenance ticket detail — the styled work order (design pass · Maintenance UI). Ported from
 * the catalogued "Operator Work Order" design onto the SAME operator design system as the board (operator
 * layout, <x-operator.shell>, FormatsOperatorData — reused, never redefined). Presentation + wiring of
 * EXISTING actions only; no schema/guarantee/availability/cost path is touched.
 *
 * RMB rule: `{ticket}` is a plain string prop resolved in render() via a tenant-scoped `findOrFail`
 * (after SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop. The lifecycle
 * MUTATIONS run through the real {@see MaintenanceService} via POST routes
 * ({@see MaintenanceActionController}), NOT from here.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL       — the ticket's real facts (category/priority/reporter/opened/assignee), description, and
 *                  an activity timeline built ONLY from the real audit_events for this ticket (opened /
 *                  assigned / started / resolved / cancelled — actor + timestamp; nothing fabricated).
 *                  Lifecycle actions (Start/Resolve/Assign/Cancel) persist through the service and are
 *                  offered ONLY when the real current state allows the transition.
 *   • INTEGRATION 1 (out of service) — when the ticket took its unit out of service AND the unit is
 *                  genuinely `condition_status = out_of_service`, the OOS callout shows, PLUS the REAL
 *                  count of physically-assigned bookings on that unit (active `reservation_unit` rows) as
 *                  the manual-move flag (absent when zero) — surfacing, never auto-cancelling.
 *   • INTEGRATION 2 (cost → owner expense) — when resolved with a cost, the destination is read from the
 *                  ACTUAL owner_expense (source = this ticket): present → "charged to {owner}"; absent
 *                  (operator-owned) → "operator cost". Never a fabricated charge.
 *   • PII        — staff names (reporter/assignee/actor) are shown; no encrypted guest/owner contact.
 */
#[Layout('components.layouts.operator')]
class MaintenanceTicketDetail extends Component
{
    use FormatsOperatorData;

    public string $ticketId;

    public function mount(string $ticket): void
    {
        $this->ticketId = $ticket;
    }

    public function render(): View
    {
        // Tenant-scoped: a cross-org (or unknown) id resolves to nothing → 404.
        $ticket = MaintenanceTicket::query()
            ->with(['unit.property', 'assignedTo', 'reportedBy'])
            ->findOrFail($this->ticketId);

        $unit = $ticket->unit;
        $status = (string) $ticket->status;
        $statusMeta = $this->maintenanceStatusMeta($status);
        $priorityMeta = $this->maintenancePriorityMeta((string) $ticket->priority);

        // The real active owner-expense for this ticket's cost (owned units only; absent = operator cost).
        $expense = OwnerExpense::query()
            ->with('owner')
            ->where('source_type', $ticket->getMorphClass())
            ->where('source_id', $ticket->getKey())
            ->whereNull('voided_at')
            ->first();

        // OOS integration — shown only when this ticket took the unit out AND it is genuinely OOS now.
        $isOos = $ticket->takes_out_of_service && $unit->condition_status === 'out_of_service';

        // Manual-move flag — REAL count of physically-assigned bookings on the (now-blocked) unit. These
        // are NOT auto-moved/cancelled; the operator moves them from the rack. Absent when zero.
        $bookingsToMove = $isOos
            ? ReservationUnit::query()->active()->where('unit_id', $unit->id)->count()
            : 0;

        $currency = (string) $unit->property->currency;

        // #104 GATE 0 — the maintenance lifecycle routes are now gated `maintenance.manage`; gate the CONTROLS
        // on the SAME permission so a role that can VIEW a ticket but not manage it never sees a 403-ing button.
        $canManage = (bool) auth()->user()?->can('maintenance.manage');

        return view('livewire.maintenance-ticket-detail', [
            'viewer' => $this->viewer(),
            'ticket' => [
                'id' => (string) $ticket->id,
                'title' => (string) $ticket->title,
                'description' => $ticket->description !== null ? (string) $ticket->description : null,
                'category' => $this->maintenanceCategoryLabel((string) $ticket->category),
                'priority_label' => $priorityMeta['label'],
                'priority_tone' => $priorityMeta['tone'],
                'status' => $status,
                'status_label' => $statusMeta['label'],
                'status_tone' => $statusMeta['tone'],
                'unit' => (string) $unit->code,
                'unit_name' => $unit->name !== null ? (string) $unit->name : null,
                'reporter' => $ticket->reportedBy instanceof User ? (string) $ticket->reportedBy->name : null,
                'assignee' => $ticket->assignedTo instanceof User ? (string) $ticket->assignedTo->name : null,
                'assignee_id' => $ticket->assigned_to !== null ? (string) $ticket->assigned_to : null,
                'opened' => $ticket->opened_at->format('j M Y · H:i'),
                'resolved' => $ticket->resolved_at?->format('j M Y · H:i'),
                'cost' => $ticket->cost_minor !== null ? $this->money((int) $ticket->cost_minor, (string) ($ticket->currency ?? $currency)) : null,
                // Real state → which transitions the service will accept (offer only what it allows).
                'can_start' => $canManage && $status === 'open',
                'can_resolve' => $canManage && $status === 'in_progress',
                'can_cancel' => $canManage && in_array($status, ['open', 'in_progress'], true),
                'can_assign' => $canManage && in_array($status, ['open', 'in_progress'], true),
            ],
            'currencySymbol' => $this->currencySymbol($currency),
            'timeline' => $this->timeline($ticket),
            'oos' => [
                'active' => $isOos,
                'bookings_to_move' => $bookingsToMove,
            ],
            'expense' => $expense !== null ? [
                'owner' => (string) ($expense->owner->name ?? 'the owner'),
                'amount' => $this->money((int) $expense->amount_minor, (string) $expense->currency),
                'deductible' => (bool) $expense->deductible,
            ] : null,
            'staff' => $this->orgStaff(),
        ]);
    }

    /**
     * The ticket's activity timeline — built ONLY from the real audit_events for this subject. Each entry
     * is a genuine recorded transition (opened/assigned/started/resolved/cancelled) with its actor +
     * timestamp; nothing is invented and no synthetic "expected next step" is shown.
     *
     * @return list<array{label: string, detail: string|null, actor: string, at: string}>
     */
    private function timeline(MaintenanceTicket $ticket): array
    {
        $events = AuditEvent::query()
            ->where('subject_type', $ticket->getMorphClass())
            ->where('subject_id', $ticket->getKey())
            ->where('action', 'like', 'maintenance_ticket.%')
            ->orderBy('created_at')
            ->get();

        // Resolve the actor + (for assignments) the target user names in one batch.
        $userIds = $events->pluck('actor_id')->filter()->all();
        foreach ($events as $event) {
            $after = is_array($event->after) ? $event->after : [];
            if (isset($after['assigned_to'])) {
                $userIds[] = (string) $after['assigned_to'];
            }
        }
        /** @var array<string, string> $names */
        $names = User::query()->whereIn('id', array_values(array_unique($userIds)))->pluck('name', 'id')->all();

        return $events->map(function (AuditEvent $event) use ($names): array {
            $action = (string) $event->action;
            $after = is_array($event->after) ? $event->after : [];

            $label = match ($action) {
                'maintenance_ticket.opened' => 'Ticket opened',
                'maintenance_ticket.assigned' => isset($after['assigned_to']) ? 'Assigned' : 'Unassigned',
                'maintenance_ticket.started' => 'Work started',
                'maintenance_ticket.resolved' => 'Resolved',
                'maintenance_ticket.cancelled' => 'Cancelled',
                default => ucwords(str_replace(['maintenance_ticket.', '_'], ['', ' '], $action)),
            };

            $detail = null;
            if ($action === 'maintenance_ticket.assigned' && isset($after['assigned_to'])) {
                $detail = $names[(string) $after['assigned_to']] ?? null;
            }

            $actor = $event->actor_id !== null
                ? ($names[(string) $event->actor_id] ?? 'A team member')
                : ($event->actor_type === 'system' ? 'System' : 'A team member');

            return [
                'label' => $label,
                'detail' => $detail,
                'actor' => $actor,
                'at' => $event->created_at?->format('j M · H:i') ?? '',
            ];
        })->all();
    }

    /**
     * The active staff of the current org, for the Reassign picker.
     *
     * @return list<array{id: string, name: string}>
     */
    private function orgStaff(): array
    {
        $orgId = app(TenantContext::class)->id();
        if ($orgId === null) {
            return [];
        }

        $memberIds = Membership::query()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->pluck('user_id');

        return User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => ['id' => (string) $u->id, 'name' => (string) $u->name])
            ->all();
    }
}
