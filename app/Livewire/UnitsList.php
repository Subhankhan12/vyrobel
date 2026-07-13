<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\RoomType;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Units — the org's physical units (design pass · final operator stub). Styled onto the
 * shared operator design system (operator layout, <x-operator.shell>, FormatsOperatorData — reused,
 * never redefined). Presentation + a thin read: the component only READS; no service/schema/guarantee
 * is touched.
 *
 * TENANT-scoped automatically — `Unit` carries the `BelongsToOrganization` global scope and the active
 * org comes from `SetTenantForWebRequest`; one org never sees another's units. No route-model-bound
 * props; the read resolves in render().
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — each unit's real fields: code, name, room-type, kind, cleanliness + condition (real
 *                enums), active flag, max occupancy, and the owner LINK where `owner_id` is set.
 *   • DERIVED  — the summary counts (total / active / out-of-service / to-clean), from the real rows.
 *   • DEFERRED — per-unit photos: no real bytes → not shown (no fabricated gallery).
 *   • NO BACKEND — add/edit unit, filters, export: disabled "Soon" (never faked).
 *   • PII      — the owner NAME (a business party the operator manages, not a guest) is shown; no
 *                encrypted owner/guest email/phone is ever rendered.
 *
 * The view receives:
 *   viewer: array{name: string, initials: string}
 *   units: list<array<string, mixed>>
 *   summary: array{total: int, active: int, out_of_service: int, to_clean: int}
 */
#[Layout('components.layouts.operator')]
class UnitsList extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $models = Unit::query()
            ->with(['roomType.photos', 'owner'])
            ->orderBy('code')
            ->get();

        $units = $models->map(function (Unit $unit): array {
            $hk = $this->unitHousekeepingMeta((string) $unit->housekeeping_status);
            $condition = $this->unitConditionMeta((string) $unit->condition_status);

            return [
                'id' => $unit->id,
                'code' => (string) $unit->code,
                'name' => $unit->name !== null ? (string) $unit->name : null,
                'room_type' => (string) $unit->roomType->name,
                'kind' => $this->unitKindLabel((string) $unit->unit_kind),
                'occupancy' => (int) $unit->max_occupancy,
                'hk_label' => $hk['label'],
                'hk_tone' => $hk['tone'],
                'condition_label' => $condition['label'],
                'condition_tone' => $condition['tone'],
                'active' => $unit->is_active !== false,
                'owner' => $unit->owner?->name !== null ? (string) $unit->owner->name : null,
                // Cover derives from the unit's room-type (the sellable entity); null → gradient fallback.
                'cover' => $unit->roomType->coverPhoto()?->url,
            ];
        })->all();

        // Room-types (with photos) for the operator photo manager — REAL, tenant-scoped inventory.
        $roomTypes = RoomType::query()
            ->with('photos')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomType $rt): array => [
                'id' => (string) $rt->getKey(),
                'name' => (string) $rt->name,
                'photos' => $rt->photos->map(fn ($p): array => [
                    'id' => (string) $p->getKey(),
                    'url' => (string) $p->url,
                    'alt' => (string) $p->alt,
                    'is_cover' => (bool) $p->is_cover,
                ])->values()->all(),
            ])->all();

        return view('livewire.units-list', [
            'viewer' => $this->viewer(),
            'units' => $units,
            'roomTypes' => $roomTypes,
            'summary' => [
                'total' => $models->count(),
                'active' => $models->where('is_active', true)->count(),
                'out_of_service' => $models->whereIn('condition_status', ['out_of_order', 'out_of_service'])->count(),
                'to_clean' => $models->where('housekeeping_status', 'dirty')->count(),
            ],
        ]);
    }
}
