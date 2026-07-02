<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Property;
use App\Models\Reservation;
use App\Services\FrontDeskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * CONTRACT STUB — front-desk board (unstyled, read-only). Shows today's arrivals / departures /
 * in-house via {@see FrontDeskService} (the real staff-action layer) — never fabricated. The
 * staff ACTIONS the styled surface will call are documented here as the contract; their backend
 * already exists and is tested (FrontDeskService): check-in / check-out (state transitions),
 * room move (FrontDeskService::moveRoom → AssignmentService::assignUnit), payment collection
 * (FrontDeskService::collectPayment → FolioService). Interactivity + offline sync are the design
 * pass — this documents the data shape only.
 *
 * The view receives, for each of arrivals/departures/inHouse:
 *   list<array{ id, reference, status, check_in, check_out, guest: string|null, balance_minor: int }>
 */
#[Layout('components.layouts.app')]
class FrontDesk extends Component
{
    public string $date = '2026-09-01';

    public function render(): View
    {
        $desk = app(FrontDeskService::class);
        $property = Property::query()->orderBy('name')->first();

        $arrivals = [];
        $departures = [];
        $inHouse = [];

        if ($property !== null) {
            $arrivals = $this->present($desk->arrivals($property->id, $this->date));
            $departures = $this->present($desk->departures($property->id, $this->date));
            $inHouse = $this->present($desk->inHouse($property->id, $this->date));
        }

        return view('livewire.front-desk', [
            'property' => $property !== null ? ['id' => $property->id, 'name' => $property->name] : null,
            'date' => $this->date,
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
        ]);
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return list<array<string, mixed>>
     */
    private function present($reservations): array
    {
        return $reservations->map(function (Reservation $reservation): array {
            $guest = $reservation->leadGuest;

            return [
                'id' => $reservation->id,
                'reference' => $reservation->reference,
                'status' => $reservation->status,
                'check_in' => CarbonImmutable::parse($reservation->check_in)->toDateString(),
                'check_out' => CarbonImmutable::parse($reservation->check_out)->toDateString(),
                'guest' => $guest !== null ? trim(($guest->first_name ?? '').' '.($guest->last_name ?? '')) : null,
                'balance_minor' => (int) $reservation->balance_minor,
            ];
        })->all();
    }
}
