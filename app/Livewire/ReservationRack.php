<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Property;
use App\Models\RoomType;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — Reservation calendar / room rack (availability per room_type per night).
 *
 * Availability comes from AvailabilityService (the real spine) — never fabricated. Read-only.
 *
 * The view receives:
 *   property: array{id: string, name: string}|null
 *   dates: list<string>                       // Y-m-d, the visible window
 *   rows: list<array{
 *     room_type_id: string, room_type: string, kind: string,
 *     availability: array<string, int>,       // available count keyed by Y-m-d
 *   }>
 */
#[Layout('components.layouts.app')]
class ReservationRack extends Component
{
    public string $start = '2026-09-01';

    public int $days = 14;

    public function render(): View
    {
        $availability = app(AvailabilityService::class);
        $start = CarbonImmutable::parse($this->start)->startOfDay();
        $end = $start->addDays($this->days);

        $dates = [];
        for ($date = $start; $date->lessThan($end); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        $property = Property::query()->orderBy('name')->first();

        $rows = [];
        if ($property !== null) {
            foreach (RoomType::query()->where('property_id', $property->id)->orderBy('name')->get() as $roomType) {
                $rows[] = [
                    'room_type_id' => $roomType->id,
                    'room_type' => $roomType->name,
                    'kind' => $roomType->kind,
                    'availability' => $availability->availabilityRange($roomType->id, $start, $end),
                ];
            }
        }

        return view('livewire.reservation-rack', [
            'property' => $property !== null ? ['id' => $property->id, 'name' => $property->name] : null,
            'dates' => $dates,
            'rows' => $rows,
        ]);
    }
}
