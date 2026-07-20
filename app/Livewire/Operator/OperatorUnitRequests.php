<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\UnitRequest;
use App\Services\OwnerUnitRequestService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Unit Requests (Phase 2 · Sub-gate 6) — the operator-visibility side of the owner add-unit request.
 * Read-only: the operator SEES an owner's pending requests here and creates the real unit as their own action
 * (that creation is deliberately NOT built — it stays the operator's manual step). Tenant-scoped: on /app the
 * owner scope is inactive, so {@see UnitRequest} lists every owner's request in the org (never cross-tenant).
 */
#[Layout('components.layouts.operator')]
class OperatorUnitRequests extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $requests = UnitRequest::query()->with('owner')->orderByDesc('created_at')->get();

        return view('livewire.operator.unit-requests', [
            'viewer' => $this->viewer(),
            'pending' => $requests->where('status', 'pending')->count(),
            'requests' => $requests->map(fn (UnitRequest $r): array => [
                'owner' => (string) ($r->owner->name ?? '—'),
                'name' => (string) $r->requested_name,
                'kind' => OwnerUnitRequestService::KINDS[$r->requested_kind],
                'notes' => (string) ($r->notes ?? ''),
                'status' => (string) $r->status,
                'when' => $r->created_at?->format('j M Y'),
            ])->all(),
        ]);
    }
}
