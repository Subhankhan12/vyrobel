<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\ResolvesCurrentProperty;
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
    use ResolvesCurrentProperty;

    public function render(): View
    {
        // Phase 6a · multi-property: scope to the current property (or every accessible property when
        // "all"). Pre-6a this listed units org-wide, blending properties in a multi-property org.
        $models = Unit::query()
            ->whereIn('property_id', $this->currentReadIds)
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
                // D18-5 — the per-unit pricing form needs the TYPE the unit belongs to (an override is keyed
                // on the room type's rate plan), so it travels with the row rather than being looked up again.
                'room_type_id' => (string) $unit->room_type_id,
                'kind' => $this->unitKindLabel((string) $unit->unit_kind),
                'occupancy' => (int) $unit->max_occupancy,
                // ⚠️ F-6 — the row needs the REAL flag to label its control ("Take out of service" vs
                // "Return to service") and to prefill the edit dialog. `name` is nullable in the schema.
                'is_active' => (bool) $unit->is_active,
                'unit_name' => $unit->name,
                'hk_label' => $hk['label'],
                'hk_tone' => $hk['tone'],
                'condition_label' => $condition['label'],
                'condition_tone' => $condition['tone'],
                'active' => $unit->is_active !== false,
                'owner' => $unit->owner?->name !== null ? (string) $unit->owner->name : null,
                // Cover derives from the unit's room-type (the sellable entity); null → gradient fallback.
                'cover' => $unit->roomType->coverPhoto()?->url,
                // ⚠️ CLUSTER E — ARRIVAL INFO AS STATUS, NEVER AS VALUES. The row reports whether a door
                // code and a wifi password EXIST; it never carries them. Putting a credential to a physical
                // space in the list payload would place it in every response, cache and screenshot of this
                // screen. The SSID is different in kind — a router broadcasts it to the street — so it is
                // shown, which is also what makes the row useful at a glance.
                'arrival' => [
                    'has_code' => $unit->door_code !== null && (string) $unit->door_code !== '',
                    'ssid' => $unit->wifi_ssid !== null && (string) $unit->wifi_ssid !== '' ? (string) $unit->wifi_ssid : null,
                    'has_wifi' => $unit->wifi_password !== null && (string) $unit->wifi_password !== '',
                    'has_notes' => $unit->arrival_notes !== null && (string) $unit->arrival_notes !== '',
                ],
            ];
        })->all();

        // Room-types (with photos) for the operator photo manager — REAL inventory, scoped to the
        // current property (or every accessible property when "all").
        $roomTypes = RoomType::query()
            ->whereIn('property_id', $this->currentReadIds)
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
            // D18-5 — the ONE per-unit pricing form is opened per row, so it needs the same shape the rates
            // grid gives it: the room types and the units beneath them, labelled `name ?: code` (units carry
            // a code and a NULL name, and an unlabelled money form is the bulk pass's blank-rows defect).
            'unitPriceRoomTypes' => array_values(array_map(
                fn (array $rt): array => ['id' => (string) $rt['id'], 'name' => (string) $rt['name']],
                $roomTypes,
            )),
            'unitPriceUnits' => collect($units)->groupBy('room_type_id')
                ->map(fn ($g): array => $g->map(fn (array $u): array => [
                    'id' => (string) $u['id'],
                    'name' => (string) ($u['name'] !== null && $u['name'] !== '' ? $u['name'] : $u['code']),
                ])->values()->all())->all(),
            'canManageRates' => (bool) auth()->user()?->can('rates.manage'),
            // ⚠️ OPERATOR FIX 1 (audit Finding 6) — the room-type PHOTO controls post to
            // `room-types.photos.*`, which require `units.manage`, NOT `rates.manage`. They carried no
            // guard at all, so every back-office role rendered them. Computed the same way as the rates
            // flag above so the view gates each control on the permission its OWN route requires (#30).
            'canManageUnits' => (bool) auth()->user()?->can('units.manage'),
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
