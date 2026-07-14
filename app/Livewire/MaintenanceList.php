<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\MaintenanceActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\MaintenanceTicket;
use App\Models\Membership;
use App\Models\Unit;
use App\Models\User;
use App\Services\MaintenanceService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Maintenance — the work-order KANBAN board (design pass · Maintenance UI). Styled from the
 * catalogued "Operator Maintenance" design onto the SAME operator design system as the other operator
 * screens (the operator layout, <x-operator.shell>, FormatsOperatorData — reused, never redefined).
 * Presentation + a thin read: the board only READS; both MUTATIONS it offers run through the real
 * {@see MaintenanceService} via POST routes ({@see MaintenanceActionController}),
 * NOT from here — so no schema/guarantee/availability path is touched by this component.
 *
 * TENANT-scoped (MaintenanceTicket + Unit are BelongsToOrganization) — one org never sees another's
 * tickets, units, or staff.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL       — every ticket grouped by its real `status` (the 3 kanban lanes ARE the real enum
 *                  open/in_progress/resolved — no invented columns; cancelled tickets are terminal and
 *                  fall off the work board, never faked). Per card the real unit, category, priority,
 *                  and assignee (or "Unassigned"); resolved cards carry the REAL cost + resolved date.
 *                  The "Unit OOS" badge shows only when the ticket took the unit out of service AND the
 *                  unit is genuinely `condition_status = out_of_service`. "+ New ticket" (→ open()) and
 *                  "Assign" (→ assign()) are REAL and persist.
 *   • DERIVED    — the summary chips (Open / In progress / Resolved this month / Units out of service)
 *                  and the "Units out of service" rail, from the real rows only.
 *   • NO BACKEND — filters / export: disabled "Soon" (never faked).
 *   • PII        — the assignee/reporter STAFF name is shown (a staff user, not a guest); no encrypted
 *                  guest/owner contact is ever rendered.
 *
 * The view receives:
 *   viewer: array{name: string, initials: string}
 *   columns: list<array{key: string, label: string, tone: string, tickets: list<array<string, mixed>>}>
 *   summary: array{open: int, in_progress: int, resolved_month: int, out_of_service: int}
 *   oosUnits: list<array{code: string, name: string|null, ticket_id: string|null, ticket_title: string|null}>
 *   units: list<array{id: string, label: string}>          // own units, for the New-ticket picker
 *   staff: list<array{id: string, name: string}>           // org staff, for the Assign picker
 *   categories / priorities: list<array{value: string, label: string}>
 */
#[Layout('components.layouts.operator')]
class MaintenanceList extends Component
{
    use FormatsOperatorData;

    /** The kanban lanes, in workflow order — exactly the real active-status enum. */
    private const STATUSES = ['open', 'in_progress', 'resolved'];

    /** Real `category` enum, for the New-ticket picker (no invented categories). */
    private const CATEGORIES = ['plumbing', 'electrical', 'appliance', 'structural', 'other'];

    /** Real `priority` enum, for the New-ticket picker (no invented priorities). */
    private const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    public function render(): View
    {
        $tickets = MaintenanceTicket::query()
            ->with(['unit', 'assignedTo'])
            ->whereIn('status', self::STATUSES)
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->orderByDesc('opened_at')
            ->get();

        /** @var array<string, list<array<string, mixed>>> $grouped */
        $grouped = array_fill_keys(self::STATUSES, []);
        $resolvedMonth = 0;
        $monthStart = now()->startOfMonth();

        foreach ($tickets as $ticket) {
            $status = (string) $ticket->status;
            if (! array_key_exists($status, $grouped)) {
                continue;   // ignore any out-of-lane row rather than invent a column
            }

            $grouped[$status][] = $this->card($ticket);

            if ($status === 'resolved' && $ticket->resolved_at !== null && $ticket->resolved_at->greaterThanOrEqualTo($monthStart)) {
                $resolvedMonth++;
            }
        }

        $columns = [];
        foreach (self::STATUSES as $status) {
            $meta = $this->maintenanceStatusMeta($status);
            $columns[] = [
                'key' => $status,
                'label' => $meta['label'],
                'tone' => $meta['tone'],
                'tickets' => $grouped[$status],
            ];
        }

        // Units genuinely out of service right now (the real condition_status, tenant-scoped) + the
        // active OOS ticket that governs each (for the "View" link on the rail).
        $oosModels = Unit::query()
            ->where('condition_status', 'out_of_service')
            ->orderBy('code')
            ->get();

        $activeOosTickets = MaintenanceTicket::query()
            ->whereIn('unit_id', $oosModels->pluck('id'))
            ->where('takes_out_of_service', true)
            ->whereIn('status', ['open', 'in_progress'])
            ->get()
            ->keyBy('unit_id');

        $oosUnits = $oosModels->map(function (Unit $unit) use ($activeOosTickets): array {
            $ticket = $activeOosTickets->get($unit->id);

            return [
                'code' => (string) $unit->code,
                'name' => $unit->name !== null ? (string) $unit->name : null,
                'ticket_id' => $ticket !== null ? (string) $ticket->id : null,
                'ticket_title' => $ticket !== null ? (string) $ticket->title : null,
            ];
        })->all();

        return view('livewire.maintenance-list', [
            'viewer' => $this->viewer(),
            'columns' => $columns,
            'summary' => [
                'open' => count($grouped['open']),
                'in_progress' => count($grouped['in_progress']),
                'resolved_month' => $resolvedMonth,
                'out_of_service' => $oosModels->count(),
            ],
            'oosUnits' => $oosUnits,
            'units' => $this->ownUnits(),
            'staff' => $this->orgStaff(),
            'categories' => array_map(fn (string $c): array => ['value' => $c, 'label' => $this->maintenanceCategoryLabel($c)], self::CATEGORIES),
            'priorities' => array_map(fn (string $p): array => ['value' => $p, 'label' => $this->maintenancePriorityMeta($p)['label']], self::PRIORITIES),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function card(MaintenanceTicket $ticket): array
    {
        $assignee = $ticket->assignedTo;
        $priority = $this->maintenancePriorityMeta((string) $ticket->priority);
        $unit = $ticket->unit;

        // "Unit OOS" only when this ticket took the unit out AND the unit is genuinely out of service now.
        $oos = $ticket->takes_out_of_service && $unit !== null && $unit->condition_status === 'out_of_service';

        return [
            'id' => (string) $ticket->id,
            'title' => (string) $ticket->title,
            'unit' => (string) ($unit->code ?? '—'),
            'category' => $this->maintenanceCategoryLabel((string) $ticket->category),
            'priority_label' => $priority['label'],
            'priority_tone' => $priority['tone'],
            'assignee' => $assignee instanceof User ? (string) $assignee->name : null,
            'assignee_id' => $ticket->assigned_to !== null ? (string) $ticket->assigned_to : null,
            'oos' => $oos,
            'cost' => $ticket->cost_minor !== null ? $this->money((int) $ticket->cost_minor, (string) ($ticket->currency ?? 'EUR')) : null,
            'resolved' => $ticket->resolved_at?->format('j M'),
        ];
    }

    /**
     * The org's OWN units for the New-ticket picker (tenant-scoped; a foreign unit id is impossible here
     * and is rejected again server-side by the service action).
     *
     * @return list<array{id: string, label: string}>
     */
    private function ownUnits(): array
    {
        return Unit::query()
            ->orderBy('code')
            ->get()
            ->map(fn (Unit $u): array => [
                'id' => (string) $u->id,
                'label' => $u->name !== null ? $u->code.' · '.$u->name : (string) $u->code,
            ])->all();
    }

    /**
     * The active staff of the current org, for the Assign picker (org membership, not tenant-scoped rows).
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
