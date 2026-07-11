<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Availability;
use App\Models\HousekeepingTask;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Services\FrontDeskService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Dashboard — the property's daily overview (design pass · Operator Screen 5, completing the
 * operator core). A NEW tenant-scoped page (there was no stub/route), styled from the catalogued
 * "Operator Dashboard" onto the SAME operator design system (operator layout, <x-operator.shell>,
 * FormatsOperatorData — reused, never redefined). Presentation + a thin read: it only READS; no
 * service/schema/guarantee is touched.
 *
 * TENANT-scoped (every model here is BelongsToOrganization) — an org's KPIs are built ONLY from its
 * own data. The "as of" day is a plain `?date=` GET link (RMB-safe, re-runs the tenant middleware);
 * scoped reads resolve in render(); no route-model-bound props.
 *
 * EACH KPI IS A TRUE ORG FIGURE — the metrics-screen risk is a plausible-but-wrong number (the
 * owner room-type-wide occupancy trap). Occupancy is summed org-wide from the real Availability spine
 * across the property's room types, never a mislabeled per-type figure.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL     — occupancy % + rooms sold/available (Availability spine, org-wide for the date);
 *                arrivals / departures / in-house counts (FrontDeskService); housekeeping status counts
 *                (real `housekeeping_tasks.status` enum); payments received on the date (settled
 *                payments − refunds, tenant-scoped); today's arrivals list (guest NAMES only).
 *   • DERIVED  — occupancy % (sold/total), "to clean" (pending+in_progress) — from real figures only.
 *   • DEFERRED — revenue trend / ADR / RevPAR: a series/aggregation this pass doesn't build → a flagged
 *                "not yet available" stub, never a fabricated chart or number.
 *   • NO BACKEND — export / custom range: disabled "Soon".
 *   • Guest PII — arrivals list is names only; encrypted email/phone are never rendered.
 *
 * The view receives: viewer, property, day, kpis, housekeeping, arrivals.
 */
#[Layout('components.layouts.operator')]
class OperatorDashboard extends Component
{
    use FormatsOperatorData;

    public function render(): View
    {
        $desk = app(FrontDeskService::class);
        $property = Property::query()->orderBy('name')->first();
        $date = $this->resolveDate();
        $day = CarbonImmutable::parse($date);

        $occupancy = $property !== null ? $this->occupancy($property, $date) : ['total' => 0, 'sold' => 0, 'available' => 0, 'pct' => 0];
        $arrivals = $property !== null ? $desk->arrivals($property->id, $date) : new Collection;
        $departures = $property !== null ? $desk->departures($property->id, $date) : new Collection;
        $inHouse = $property !== null ? $desk->inHouse($property->id, $date) : new Collection;
        $housekeeping = $property !== null ? $this->housekeeping($property) : ['pending' => 0, 'in_progress' => 0, 'clean' => 0, 'inspected' => 0, 'to_clean' => 0];

        $currency = (string) ($property->currency ?? 'EUR');

        return view('livewire.operator.dashboard', [
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
            'kpis' => [
                'occupancy_pct' => $occupancy['pct'],
                'rooms_sold' => $occupancy['sold'],
                'rooms_total' => $occupancy['total'],
                'rooms_available' => $occupancy['available'],
                'arrivals' => $arrivals->count(),
                'departures' => $departures->count(),
                'in_house' => $inHouse->count(),
                'payments' => $this->money($this->paymentsOn($date), $currency),
            ],
            'housekeeping' => $housekeeping,
            'arrivals' => $this->arrivalsList($arrivals),
        ]);
    }

    private function resolveDate(): string
    {
        $param = request()->query('date');
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            return CarbonImmutable::parse($param)->toDateString();
        }

        return CarbonImmutable::today()->toDateString();
    }

    /**
     * TRUE org-wide occupancy for the night: sum the real Availability spine (total/sold/available)
     * across the property's room types. Scoped by the property's tenant-scoped room types, so another
     * org's rows can never enter the sum. This is the fix for the room-type-wide mislabel trap.
     *
     * @return array{total: int, sold: int, available: int, pct: int}
     */
    private function occupancy(Property $property, string $date): array
    {
        $roomTypeIds = RoomType::query()->where('property_id', $property->id)->pluck('id')->all();
        if ($roomTypeIds === []) {
            return ['total' => 0, 'sold' => 0, 'available' => 0, 'pct' => 0];
        }

        /** @var Collection<int, Availability> $rows */
        $rows = Availability::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereDate('date', $date)
            ->get();

        $total = (int) $rows->sum('total');
        $sold = (int) $rows->sum('sold');
        $available = $rows->reduce(fn (int $carry, Availability $a): int => $carry + $a->available(), 0);

        return [
            'total' => $total,
            'sold' => $sold,
            'available' => $available,
            'pct' => $total > 0 ? (int) round($sold / $total * 100) : 0,
        ];
    }

    /**
     * Housekeeping status counts for the property (real enum), + "to clean" = pending + in_progress.
     *
     * @return array{pending: int, in_progress: int, clean: int, inspected: int, to_clean: int}
     */
    private function housekeeping(Property $property): array
    {
        $counts = HousekeepingTask::query()
            ->where('property_id', $property->id)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $pending = (int) ($counts['pending'] ?? 0);
        $inProgress = (int) ($counts['in_progress'] ?? 0);

        return [
            'pending' => $pending,
            'in_progress' => $inProgress,
            'clean' => (int) ($counts['clean'] ?? 0),
            'inspected' => (int) ($counts['inspected'] ?? 0),
            'to_clean' => $pending + $inProgress,
        ];
    }

    /**
     * Money actually received on the date: settled payments/captures − succeeded refunds, tenant-scoped.
     * A real figure from the payments ledger (not accrual).
     */
    private function paymentsOn(string $date): int
    {
        $received = (int) Payment::query()
            ->whereIn('type', ['payment', 'capture'])
            ->whereIn('status', ['succeeded', 'captured'])
            ->whereDate('processed_at', $date)
            ->sum('amount_minor');

        $refunded = (int) Payment::query()
            ->where('type', 'refund')
            ->where('status', 'succeeded')
            ->whereDate('processed_at', $date)
            ->sum('amount_minor');

        return $received - $refunded;
    }

    /**
     * Today's arrivals as a names-only list (guest PII kept to the name, per the front-desk precedent).
     *
     * @param  Collection<int, Reservation>  $arrivals
     * @return list<array{id: string, reference: string, guest: string, status_label: string, tone: string}>
     */
    private function arrivalsList(Collection $arrivals): array
    {
        return $arrivals->take(8)->map(function (Reservation $reservation): array {
            $guest = $reservation->leadGuest;
            $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
            $meta = $this->reservationStatusMeta((string) $reservation->status);

            return [
                'id' => $reservation->id,
                'reference' => (string) $reservation->reference,
                'guest' => $name !== '' ? $name : (string) $reservation->reference,
                'status_label' => $meta['label'],
                'tone' => $meta['tone'],
            ];
        })->all();
    }
}
