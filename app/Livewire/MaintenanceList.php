<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\MaintenanceTicket;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator maintenance list — UNSTYLED contract stub (the styled screen is a later design pass, same as
 * the original Phase-1 Step-9 stubs). READS only, TENANT-scoped (MaintenanceTicket is BelongsToOrganization,
 * so an org sees only its own tickets). Documents the data shape the future styled screen will consume.
 *
 * Data shape — `tickets`: list<array{
 *   id: string, unit: string, title: string, category: string, priority: string, status: string,
 *   out_of_service: bool, cost: string|null, opened: string,
 * }>.
 */
#[Layout('components.layouts.app')]
class MaintenanceList extends Component
{
    public function render(): View
    {
        $tickets = MaintenanceTicket::query()
            ->with('unit')
            ->orderByDesc('opened_at')
            ->get()
            ->map(fn (MaintenanceTicket $t): array => [
                'id' => (string) $t->id,
                'unit' => (string) ($t->unit->code ?? '—'),
                'title' => (string) $t->title,
                'category' => (string) $t->category,
                'priority' => (string) $t->priority,
                'status' => (string) $t->status,
                'out_of_service' => (bool) $t->takes_out_of_service,
                'cost' => $t->cost_minor !== null ? number_format($t->cost_minor / 100, 2).' '.$t->currency : null,
                'opened' => $t->opened_at->toDateString(),
            ])->all();

        return view('livewire.maintenance-list', ['tickets' => $tickets]);
    }
}
