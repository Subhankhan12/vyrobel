<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — Units list (unstyled; documents the data contract for the design pass).
 *
 * Tenant-scoped automatically (Unit uses the global org scope; the active org comes from the
 * request-scoped SetTenantForWebRequest middleware). Read-only.
 *
 * The view receives `units`: list<array{
 *   id: string, code: string, name: string|null, unit_kind: string, room_type: string,
 *   housekeeping_status: string, condition_status: string, is_active: bool,
 * }>
 */
#[Layout('components.layouts.app')]
class UnitsList extends Component
{
    public function render(): View
    {
        $units = Unit::query()
            ->with('roomType')
            ->orderBy('code')
            ->get()
            ->map(fn (Unit $unit): array => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'unit_kind' => $unit->unit_kind,
                'room_type' => $unit->roomType->name,
                'housekeeping_status' => $unit->housekeeping_status,
                'condition_status' => $unit->condition_status,
                'is_active' => (bool) $unit->is_active,
            ])
            ->all();

        return view('livewire.units-list', ['units' => $units]);
    }
}
