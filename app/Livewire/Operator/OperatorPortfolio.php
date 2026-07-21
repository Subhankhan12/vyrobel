<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Services\FrontDeskService;
use App\Support\Property\PropertyContext;
use App\Support\Property\PropertyOccupancy;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Operator PORTFOLIO (Phase 6a · Step 2) — the multi-property overview: an all-properties KPI strip + a
 * card per property (real unit count, derived status, real occupancy tonight), and the "Add property"
 * entry point. The overview always spans the user's FULL accessible set (the property_user allow-list, or
 * the whole org when unpivoted) — independent of the topbar switcher's current selection.
 *
 * DESIGN vs REALITY (honesty rule): the prototype's side-by-side ADR / RevPAR / revenue comparison table
 * is ANALYTICS — deferred honestly to Phase 6b, never fabricated. Portfolio v1 shows only REAL/DERIVED
 * figures: unit counts, occupancy from the Availability spine, arrivals from the front-desk read, and a
 * status DERIVED from real inventory ("Finish setup to go live" when a property has no units yet — its
 * genuine pre-operational state, not a faked status).
 *
 * TENANT-scoped (Property is BelongsToOrganization); `/app` behind EnsureBackOfficeAccess (#26); the route
 * carries `can:properties.view`. The Add/Edit affordances render only for `properties.manage` so the UI
 * never offers a write the route would 403.
 */
#[Layout('components.layouts.operator')]
class OperatorPortfolio extends Component
{
    use FormatsOperatorData;

    /** The user's accessible property ids (pivot-or-all), captured at mount — the overview's scope. */
    #[Locked]
    public array $accessibleIds = [];

    #[Locked]
    public string $currentPropertyId = '';

    public function mount(): void
    {
        $ctx = app(PropertyContext::class);
        $this->accessibleIds = $ctx->accessibleIds();
        $this->currentPropertyId = (string) ($ctx->id() ?? '');
    }

    public function render(FrontDeskService $desk): View
    {
        $today = CarbonImmutable::today()->toDateString();

        /** @var Collection<int, Property> $properties */
        $properties = $this->accessibleIds === []
            ? collect()
            : Property::query()
                ->whereIn('id', $this->accessibleIds)
                ->withCount(['units', 'roomTypes'])
                ->orderBy('name')
                ->get();

        $cards = $properties->map(function (Property $property) use ($desk, $today): array {
            $occ = PropertyOccupancy::forProperties([(string) $property->id], $today);
            $unitCount = (int) $property->units_count;
            $roomTypeCount = (int) $property->room_types_count;

            return [
                'id' => (string) $property->id,
                'name' => (string) $property->name,
                'initial' => mb_strtoupper(mb_substr((string) $property->name, 0, 1)),
                'location' => $this->locationLabel($property),
                'type_label' => $this->typeLabel((string) $property->type),
                'units' => $unitCount,
                'room_types' => $roomTypeCount,
                'occupancy_pct' => $occ['pct'],
                'rooms_sold' => $occ['sold'],
                'rooms_total' => $occ['total'],
                'arrivals' => $desk->arrivals((string) $property->id, $today)->count(),
                'status' => $this->statusFor($property, $unitCount, $roomTypeCount),
                'is_current' => (string) $property->id === $this->currentPropertyId,
                'timezone' => (string) $property->timezone,
                'type' => (string) $property->type,
            ];
        })->all();

        $totalUnits = (int) $properties->sum('units_count');
        $aggregate = PropertyOccupancy::forProperties($this->accessibleIds, $today);
        $arrivalsToday = collect($cards)->sum('arrivals');

        return view('livewire.operator.portfolio', [
            'viewer' => $this->viewer(),
            'canManage' => (bool) auth()->user()?->can('properties.manage'),
            'cards' => $cards,
            'summary' => [
                'properties' => $properties->count(),
                'units' => $totalUnits,
                'occupancy_pct' => $aggregate['pct'],
                'rooms_sold' => $aggregate['sold'],
                'rooms_total' => $aggregate['total'],
                'arrivals' => $arrivalsToday,
            ],
        ]);
    }

    /**
     * The card's status — DERIVED from real state, never a faked label:
     *   • inactive property                → "Inactive"
     *   • active but no room types/units    → "Finish setup to go live" (not yet operational)
     *   • active with inventory             → "Active"
     *
     * @return array{label: string, tone: string, setup: bool}
     */
    private function statusFor(Property $property, int $unitCount, int $roomTypeCount): array
    {
        if ((string) $property->status === 'inactive') {
            return ['label' => 'Inactive', 'tone' => 'muted', 'setup' => false];
        }

        if ($roomTypeCount === 0 || $unitCount === 0) {
            return ['label' => 'Finish setup to go live', 'tone' => 'warn', 'setup' => true];
        }

        return ['label' => 'Active', 'tone' => 'ok', 'setup' => false];
    }

    /** A human label for the real `properties.type` enum (warm, no invented value). */
    private function typeLabel(string $type): string
    {
        return match ($type) {
            'hotel' => 'Hotel',
            'aparthotel' => 'Aparthotel',
            'serviced_apartment' => 'Serviced apartment',
            'apartment_building' => 'Apartment building',
            'apartment' => 'Apartment',
            'villa' => 'Villas & apartments',
            'hostel' => 'Hostel',
            'guest_house' => 'Guesthouse / lodge',
            'resort' => 'Resort',
            'mixed' => 'Mixed',
            'portfolio' => 'Portfolio',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private function locationLabel(Property $property): string
    {
        $address = $property->address;
        $city = is_array($address) && isset($address['city']) ? trim((string) $address['city']) : '';

        return $city !== '' ? $city : (string) $property->timezone;
    }
}
