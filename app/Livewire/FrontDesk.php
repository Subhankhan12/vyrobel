<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\FrontDeskActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Property;
use App\Models\Reservation;
use App\Services\FrontDeskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Front Desk — today's arrivals / departures / in-house board (design pass · Operator
 * Screen 2). Styled from the catalogued "Operator Front Desk" design onto the SAME operator design
 * system as Screen 1 (the operator layout, <x-operator.shell>, FormatsOperatorData — reused, never
 * redefined). Presentation + a thin read: the board only READS; the check-in/out MUTATIONS run
 * through the real {@see FrontDeskService} via a POST route ({@see FrontDeskActionController}),
 * NOT from here — so no inventory/folio/schema/guarantee is touched by this component.
 *
 * TENANT-scoped (BelongsToOrganization) — one org never sees another's reservations. The day is
 * chosen via a plain `?date=` GET link (RMB-safe, re-runs the tenant middleware); no route-model-bound
 * props; the lists resolve in render().
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL       — arrivals/departures/in-house (FrontDeskService); per row the real guest name, real
 *                  reservation status, unit assignment or "Unassigned", source, nights, guests, and the
 *                  real folio balance (`balance_minor`, maintained by FolioService). Check-in/out are
 *                  REAL and persist (via the POST action).
 *   • DERIVED    — the count chips + outstanding-balance total, from the real figures only.
 *   • DEFERRED   — "Collect payment" (needs an amount + card-token + gateway flow — out of a
 *                  presentation pass) is shown real-looking but flagged "not yet available"; it never
 *                  fakes a payment. "Assign unit" links to the rack (the real assignment surface) —
 *                  never an invented assignment.
 *   • NO BACKEND — Walk-in, filters, print/export: disabled "Soon".
 *   • Guest PII  — names are shown (front-desk surface); encrypted email/phone are never rendered.
 *
 * The view receives, for each of arrivals/departures/inHouse:
 *   list<array{ id, reference, guest, status, status_label, tone, source, unit, unassigned,
 *     nights, guests, check_in, check_out, balance_minor, balance, has_balance,
 *     can_check_in, can_check_out }>
 */
#[Layout('components.layouts.operator')]
class FrontDesk extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $desk = app(FrontDeskService::class);
        $property = Property::query()->orderBy('name')->first();

        $date = $this->resolveDate($property);

        $arrivals = [];
        $departures = [];
        $inHouse = [];

        if ($property !== null) {
            $arrivals = $this->present($desk->arrivals($property->id, $date));
            $departures = $this->present($desk->departures($property->id, $date));
            $inHouse = $this->present($desk->inHouse($property->id, $date));
        }

        $day = CarbonImmutable::parse($date);

        return view('livewire.front-desk', [
            'viewer' => $this->viewer(),
            'property' => $property !== null ? ['id' => $property->id, 'name' => $property->name] : null,
            'day' => [
                'date' => $date,
                'label' => $day->format('l, j M Y'),
                'prev' => $day->subDay()->toDateString(),
                'next' => $day->addDay()->toDateString(),
                'today' => CarbonImmutable::today()->toDateString(),
                'is_today' => $date === CarbonImmutable::today()->toDateString(),
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
            'summary' => $this->summary($arrivals, $departures, $inHouse),
        ]);
    }

    /**
     * The board's day: `?date=YYYY-MM-DD` if valid, else the most recent arrival date for the
     * property (so the demo/live board lands on real activity), else today.
     */
    private function resolveDate(?Property $property): string
    {
        $param = request()->query('date');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            return CarbonImmutable::parse($param)->toDateString();
        }

        $latest = $property === null ? null : Reservation::query()
            ->where('property_id', $property->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->max('check_in');

        return $latest !== null
            ? CarbonImmutable::parse($latest)->toDateString()
            : CarbonImmutable::today()->toDateString();
    }

    /**
     * @param  Collection<int, Reservation>  $reservations
     * @return list<array<string, mixed>>
     */
    private function present(Collection $reservations): array
    {
        // Eager-load the active lines' units for the assignment display (presentation concern —
        // the service's own read query stays untouched). Wrap in an Eloquent collection locally so
        // the load happens without an N+1 and without mutating the service's contract.
        $rows = EloquentCollection::make($reservations->all());
        $rows->load(['reservationUnits' => fn ($q) => $q->where('status', 'active'), 'reservationUnits.unit']);

        return $rows->map(function (Reservation $reservation): array {
            $guest = $reservation->leadGuest;
            $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';

            $status = (string) $reservation->status;
            $meta = $this->reservationStatusMeta($status);

            $lines = $reservation->reservationUnits;
            $assignedCodes = $lines
                ->filter(fn ($l) => $l->unit_id !== null && $l->unit !== null)
                ->map(fn ($l) => (string) ($l->unit->name ?? $l->unit->code))
                ->unique()->values();
            $unassigned = $lines->isNotEmpty() && $lines->contains(fn ($l) => $l->unit_id === null);

            $checkIn = CarbonImmutable::parse($reservation->check_in)->startOfDay();
            $checkOut = CarbonImmutable::parse($reservation->check_out)->startOfDay();
            $balanceMinor = (int) $reservation->balance_minor;
            $currency = (string) ($reservation->currency ?? 'EUR');

            return [
                'id' => $reservation->id,
                'reference' => (string) $reservation->reference,
                'guest' => $name !== '' ? $name : (string) $reservation->reference,
                'status' => $status,
                'status_label' => $meta['label'],
                'tone' => $meta['tone'],
                'source' => $this->sourceLabel((string) $reservation->source),
                'unit' => $assignedCodes->isNotEmpty() ? $assignedCodes->implode(', ') : null,
                'unassigned' => $unassigned,
                'nights' => (int) $checkIn->diffInDays($checkOut),
                'guests' => (int) $reservation->adults + (int) $reservation->children,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'balance_minor' => $balanceMinor,
                'balance' => $this->money($balanceMinor, $currency),
                'has_balance' => $balanceMinor > 0,
                'can_check_in' => $status === 'confirmed',
                'can_check_out' => $status === 'checked_in',
            ];
        })->all();
    }

    /**
     * DERIVED count + outstanding-balance chips, from the real rows only.
     *
     * @param  list<array<string, mixed>>  $arrivals
     * @param  list<array<string, mixed>>  $departures
     * @param  list<array<string, mixed>>  $inHouse
     * @return array{arrivals: int, departures: int, in_house: int, due_count: int}
     */
    private function summary(array $arrivals, array $departures, array $inHouse): array
    {
        $dueCount = 0;
        foreach ([$arrivals, $departures, $inHouse] as $set) {
            foreach ($set as $row) {
                if (($row['has_balance'] ?? false) === true) {
                    $dueCount++;
                }
            }
        }

        return [
            'arrivals' => count($arrivals),
            'departures' => count($departures),
            'in_house' => count($inHouse),
            'due_count' => $dueCount,
        ];
    }
}
