<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\MaintenanceTicket;
use App\Models\OwnerExpense;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator maintenance ticket detail — UNSTYLED contract stub. READS only, TENANT-scoped.
 *
 * RMB rule: `{ticket}` is a plain string prop resolved in render() via a tenant-scoped `findOrFail`
 * (after SetTenantForWebRequest) — a cross-org id 404s. Never a route-model-bound prop.
 *
 * Data shape — `ticket`: array{id, title, description, category, priority, status, unit, unit_condition,
 *   out_of_service, cost, opened, resolved}; `expense`: array{id, amount, deductible, ledger_posted}|null
 *   (the maintenance cost's owner_expense on an owned unit, via the polymorphic source).
 */
#[Layout('components.layouts.app')]
class MaintenanceTicketDetail extends Component
{
    public string $ticketId;

    public function mount(string $ticket): void
    {
        $this->ticketId = $ticket;
    }

    public function render(): View
    {
        // Tenant-scoped: a cross-org (or unknown) id resolves to nothing → 404.
        $ticket = MaintenanceTicket::query()->with('unit')->findOrFail($this->ticketId);

        $expense = OwnerExpense::query()
            ->where('source_type', $ticket->getMorphClass())
            ->where('source_id', $ticket->getKey())
            ->whereNull('voided_at')
            ->first();

        return view('livewire.maintenance-ticket-detail', [
            'ticket' => [
                'id' => (string) $ticket->id,
                'title' => (string) $ticket->title,
                'description' => (string) $ticket->description,
                'category' => (string) $ticket->category,
                'priority' => (string) $ticket->priority,
                'status' => (string) $ticket->status,
                'unit' => (string) ($ticket->unit->code ?? '—'),
                'unit_condition' => (string) ($ticket->unit->condition_status ?? '—'),
                'out_of_service' => (bool) $ticket->takes_out_of_service,
                'cost' => $ticket->cost_minor !== null ? number_format($ticket->cost_minor / 100, 2).' '.$ticket->currency : null,
                'opened' => $ticket->opened_at->toDateString(),
                'resolved' => $ticket->resolved_at?->toDateString(),
            ],
            'expense' => $expense !== null ? [
                'id' => (string) $expense->id,
                'amount' => number_format($expense->amount_minor / 100, 2).' '.$expense->currency,
                'deductible' => (bool) $expense->deductible,
                'ledger_posted' => $expense->attributed_ledger_id !== null,
            ] : null,
        ]);
    }
}
