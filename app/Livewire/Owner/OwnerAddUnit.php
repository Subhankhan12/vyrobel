<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerUnitRequestController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\UnitRequest;
use App\Services\OwnerUnitRequestService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Add a Unit (Phase 2 · Sub-gate 6) — the owner REQUESTS a new unit; they do not create one. The design
 * itself frames it this way ("Pending review · once [operator] approves it"). This is the READ side (the request
 * form + the owner's own requests with status); the write goes POST → {@see OwnerUnitRequestController}.
 *
 * SELF-ONLY / owner-scoped: {@see UnitRequest} carries the owner scope, so the requests list is the authenticated
 * owner's own, and the write files against the owner resolved by user_id. A non-linked user fails closed.
 */
#[Layout('components.layouts.owner')]
class OwnerAddUnit extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $service = app(OwnerUnitRequestService::class);
        $user = auth()->user();

        try {
            $user === null ? throw new ModelNotFoundException : $service->owner($user);
        } catch (ModelNotFoundException) {
            return view('livewire.owner.add-unit', [
                'viewer' => $this->viewer(),
                'linked' => false,
                'kinds' => OwnerUnitRequestService::KINDS,
                'requests' => [],
            ]);
        }

        return view('livewire.owner.add-unit', [
            'viewer' => $this->viewer(),
            'linked' => true,
            'kinds' => OwnerUnitRequestService::KINDS,
            'requests' => $service->requests()->map(fn (UnitRequest $r): array => [
                'name' => (string) $r->requested_name,
                'kind' => OwnerUnitRequestService::KINDS[$r->requested_kind],
                'status_meta' => $this->requestStatusMeta((string) $r->status),
                'when' => $r->created_at?->format('j M Y'),
            ])->all(),
        ]);
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function requestStatusMeta(string $status): array
    {
        return match ($status) {
            'approved' => ['label' => 'Approved', 'tone' => 'paid'],
            'declined' => ['label' => 'Declined', 'tone' => 'draft'],
            default => ['label' => 'Pending review', 'tone' => 'pending'],
        };
    }
}
