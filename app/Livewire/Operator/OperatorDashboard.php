<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Channel;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceTicket;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\FrontDeskService;
use App\Support\Analytics\PerformanceMetrics;
use App\Support\Property\PropertyOccupancy;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
    use ResolvesCurrentProperty;

    public function render(): View
    {
        $desk = app(FrontDeskService::class);
        $date = $this->resolveDate();
        $day = CarbonImmutable::parse($date);

        // Phase 6a · multi-property: the dashboard aggregates over the READ set — a single selected
        // property, or every accessible property when "all" is chosen. The set is bounded (and locked)
        // by the property context, so this is never an arbitrary ->first() and never a foreign property.
        /** @var Collection<int, Property> $properties */
        $properties = $this->currentReadIds === []
            ? new Collection
            : Property::query()->whereIn('id', $this->currentReadIds)->orderBy('name')->get();
        $propertyIds = $properties->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $primary = $properties->first();

        $occupancy = PropertyOccupancy::forProperties($propertyIds, $date);
        $arrivals = $this->deskAcross($desk, 'arrivals', $propertyIds, $date);
        $departures = $this->deskAcross($desk, 'departures', $propertyIds, $date);
        $inHouse = $this->deskAcross($desk, 'inHouse', $propertyIds, $date);
        $housekeeping = $this->housekeeping($propertyIds);

        $currency = (string) ($primary->currency ?? 'EUR');

        // ── Operator Dashboard exact-match: the previously-missing REAL sections ────────────────────
        $workOrders = $this->workOrders($propertyIds);
        $maintenance = $this->maintenance($propertyIds, $currency, $date);
        $occupancyWeek = $this->occupancyWeek($propertyIds, $date);
        $adr = $this->adr($propertyIds, $date, $currency);
        // Occupancy delta vs the previous night (real derived; null → no baseline, honest-omit).
        $yesterday = PropertyOccupancy::forProperties($propertyIds, $day->subDay()->toDateString());
        $occDelta = $occupancy['total'] > 0 && $yesterday['total'] > 0 ? $occupancy['pct'] - $yesterday['pct'] : null;
        $propertyLabel = $this->viewingAllProperties
            ? ['id' => 'all', 'name' => 'All properties']
            : ($primary !== null ? ['id' => (string) $primary->id, 'name' => (string) $primary->name] : null);

        return view('livewire.operator.dashboard', [
            'viewer' => $this->viewer(),
            'property' => $propertyLabel,
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
                'occupancy_delta' => $occDelta,
                'rooms_sold' => $occupancy['sold'],
                'rooms_total' => $occupancy['total'],
                'rooms_available' => $occupancy['available'],
                'arrivals' => $arrivals->count(),
                'departures' => $departures->count(),
                'in_house' => $inHouse->count(),
                'payments' => $this->money($this->paymentsOn($date), $currency),
                'work_orders' => $workOrders['open'],
                'work_orders_urgent' => $workOrders['urgent'],
                'adr' => $adr,
            ],
            'housekeeping' => $housekeeping,
            'maintenance' => $maintenance,
            'occupancyWeek' => $occupancyWeek,
            'tasks' => $this->tasks($workOrders, $housekeeping, $arrivals, $date),
            // F-OP-2 — the Channels card is a Pro-only feature (the page itself 403s on Essential). Show it ONLY
            // when the org's plan grants channels, so the dashboard never surfaces a channels widget the org
            // can't reach. Null → the view omits the card entirely.
            'channels' => $this->channelsAllowed() ? $this->channels($propertyIds) : null,
            'arrivals' => $this->arrivalsList($arrivals),
        ]);
    }

    /**
     * Open work orders (active maintenance tickets) + how many are urgent — real counts, tenant + read-set
     * scoped.
     *
     * @param  list<string>  $propertyIds
     * @return array{open: int, urgent: int}
     */
    private function workOrders(array $propertyIds): array
    {
        if ($propertyIds === []) {
            return ['open' => 0, 'urgent' => 0];
        }

        $base = MaintenanceTicket::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('unit', fn ($q) => $q->whereIn('property_id', $propertyIds));

        return [
            'open' => (int) (clone $base)->count(),
            'urgent' => (int) (clone $base)->where('priority', 'urgent')->count(),
        ];
    }

    /**
     * Maintenance snapshot: units genuinely OUT OF ORDER (distinct units on active out-of-service tickets)
     * + the real nightly rate on hold (Σ tonight's rack rate of those units' room types). No pre-computed
     * hold exists, so it is DERIVED from the real RateCalendar — omitted (null) when no rate is set, never
     * fabricated.
     *
     * @param  list<string>  $propertyIds
     * @return array{out_of_order: int, awaiting_parts: int, on_hold: ?string}
     */
    private function maintenance(array $propertyIds, string $currency, string $date): array
    {
        if ($propertyIds === []) {
            return ['out_of_order' => 0, 'awaiting_parts' => 0, 'on_hold' => null];
        }

        // Units taken out of service by an ACTIVE ticket — the real "out of order" set (distinct units).
        $unitIds = MaintenanceTicket::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->where('takes_out_of_service', true)
            ->whereHas('unit', fn ($q) => $q->whereIn('property_id', $propertyIds))
            ->pluck('unit_id')
            ->unique()
            ->values();

        // Active tickets genuinely waiting on a part (the real `awaiting_parts` flag) — the per-status rail row.
        $awaitingParts = (int) MaintenanceTicket::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->where('awaiting_parts', true)
            ->whereHas('unit', fn ($q) => $q->whereIn('property_id', $propertyIds))
            ->count();

        $onHoldMinor = $this->onHoldMinor($unitIds->all(), $date);

        return [
            'out_of_order' => $unitIds->count(),
            'awaiting_parts' => $awaitingParts,
            'on_hold' => $onHoldMinor > 0 ? $this->money($onHoldMinor, $currency) : null,
        ];
    }

    /**
     * The real nightly revenue on hold = Σ, over the out-of-order units, of tonight's rack rate for the
     * unit's room type (via the room type's default rate plan → RateCalendar). A unit whose room type has no
     * rate set contributes 0 (honest — never invented).
     *
     * @param  list<string>  $unitIds
     */
    private function onHoldMinor(array $unitIds, string $date): int
    {
        if ($unitIds === []) {
            return 0;
        }

        // room_type_id per out-of-order unit (a room type may repeat — each idle unit holds its own rate).
        $roomTypeIds = Unit::query()->whereIn('id', $unitIds)->pluck('room_type_id')->filter()->values();
        if ($roomTypeIds->isEmpty()) {
            return 0;
        }

        // Tonight's price per (default) rate plan for those room types.
        $planPrice = [];
        $plans = RoomType::query()->whereIn('id', $roomTypeIds->unique()->all())->pluck('default_rate_plan_id', 'id');
        $planIds = collect($plans)->filter()->unique()->values();
        if ($planIds->isNotEmpty()) {
            $planPrice = RateCalendar::query()
                ->whereIn('rate_plan_id', $planIds->all())
                ->whereDate('date', $date)
                ->pluck('price_minor', 'rate_plan_id')
                ->all();
        }

        $total = 0;
        foreach ($roomTypeIds as $rtId) {
            $planId = $plans[$rtId] ?? null;
            $total += $planId !== null ? (int) ($planPrice[$planId] ?? 0) : 0;
        }

        return $total;
    }

    /**
     * Occupancy for the next 7 days — a real per-night % from the certified spine (never a fabricated curve).
     *
     * @param  list<string>  $propertyIds
     * @return list<array{day: string, date: string, pct: int}>
     */
    private function occupancyWeek(array $propertyIds, string $date): array
    {
        $start = CarbonImmutable::parse($date);
        $out = [];

        for ($i = 0; $i < 7; $i++) {
            $d = $start->addDays($i);
            $occ = $propertyIds === [] ? ['pct' => 0] : PropertyOccupancy::forProperties($propertyIds, $d->toDateString());
            $out[] = ['day' => $d->format('D'), 'date' => $d->toDateString(), 'pct' => (int) $occ['pct']];
        }

        return $out;
    }

    /** Tonight's ADR from the certified metric layer — null (honest) when there's no sold room-night yet. */
    private function adr(array $propertyIds, string $date, string $currency): ?string
    {
        if ($propertyIds === []) {
            return null;
        }

        $start = CarbonImmutable::parse($date);
        $metrics = PerformanceMetrics::forProperties($propertyIds, $start, $start->addDay(), $currency);

        return $metrics->isEmpty() || $metrics->adrMinor() <= 0 ? null : $this->money($metrics->adrMinor(), $currency);
    }

    /**
     * Today's tasks — DERIVED from real operational state (urgent/active work orders, rooms to clean,
     * arrivals still to check in). Honest-empty ("all caught up") when there is genuinely nothing.
     *
     * Each item is ACTIONABLE — it links to the page where the operator resolves it (maintenance /
     * housekeeping / front desk); the item auto-clears when its REAL count hits 0, so there is no
     * fabricated "done" flag (the prototype's localStorage checkmark is refused in favour of real state, #39).
     *
     * @param  array{open: int, urgent: int}  $workOrders
     * @param  array{to_clean: int, pending: int, in_progress: int, clean: int, inspected: int}  $housekeeping
     * @param  Collection<int, Reservation>  $arrivals
     * @return array{groups: list<array{label: string, count: int}>, items: list<array{label: string, meta: string, priority: string, link: string}>}
     */
    private function tasks(array $workOrders, array $housekeeping, Collection $arrivals, string $date): array
    {
        $maintenanceUrl = route('app.maintenance');
        $items = [];

        if ($workOrders['urgent'] > 0) {
            $items[] = ['label' => 'Resolve urgent work orders', 'meta' => $workOrders['urgent'].' urgent', 'priority' => 'urgent', 'link' => $maintenanceUrl.'?filter=urgent'];
        }
        if ($workOrders['open'] - $workOrders['urgent'] > 0) {
            $items[] = ['label' => 'Work through open work orders', 'meta' => ($workOrders['open'] - $workOrders['urgent']).' open', 'priority' => 'high', 'link' => $maintenanceUrl];
        }
        if ($housekeeping['to_clean'] > 0) {
            $items[] = ['label' => 'Rooms to clean', 'meta' => $housekeeping['to_clean'].' to clean', 'priority' => 'high', 'link' => route('app.housekeeping')];
        }

        // Arrivals still to check in = those not yet checked_in (the real status enum has no 'in_house').
        $toArrive = $arrivals->reject(fn (Reservation $r): bool => (string) $r->status === 'checked_in')->count();
        if ($toArrive > 0) {
            $items[] = ['label' => 'Check in arrivals', 'meta' => $toArrive.' expected', 'priority' => 'normal', 'link' => route('app.front-desk').'?date='.$date];
        }

        $groups = [
            ['label' => 'Work orders', 'count' => $workOrders['open']],
            ['label' => 'Housekeeping', 'count' => $housekeeping['to_clean']],
            ['label' => 'Arrivals', 'count' => $toArrive],
        ];

        return ['groups' => $groups, 'items' => $items];
    }

    /**
     * Channel connection status — HONEST. Reads the real `channels.status` + `last_synced_at`; a channel is
     * "Synced" ONLY when genuinely connected. When the org has no connected channel (the norm — the OTA sync
     * is account-gated), every row is "Not connected" — NEVER a fabricated "Synced" (#39).
     *
     * @param  list<string>  $propertyIds
     * @return list<array{name: string, connected: bool, last_sync: ?string}>
     */
    /**
     * Whether the current org's plan grants the Channels feature (Pro-only) — the SAME gate the /app/channels
     * route + the nav item use (Organization::planAllows), so the dashboard card can't drift from access (F-OP-2).
     */
    private function channelsAllowed(): bool
    {
        return Organization::query()->find(app(TenantContext::class)->id())?->planAllows('channels') ?? false;
    }

    private function channels(array $propertyIds): array
    {
        $channels = $propertyIds === []
            ? new Collection
            : Channel::query()->whereIn('property_id', $propertyIds)->orderBy('name')->get();

        if ($channels->isEmpty()) {
            // Honest defaults — the common OTAs shown as NOT connected (never a fake "Synced").
            return array_map(
                static fn (string $name): array => ['name' => $name, 'connected' => false, 'last_sync' => null],
                ['Booking.com', 'Airbnb', 'Expedia'],
            );
        }

        return $channels->map(function (Channel $c): array {
            $connected = (string) $c->status === 'connected';

            return [
                'name' => (string) $c->name,
                'connected' => $connected,
                'last_sync' => $connected && $c->last_synced_at !== null ? CarbonImmutable::parse($c->last_synced_at)->diffForHumans() : null,
            ];
        })->all();
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
     * Merge a FrontDeskService list across every property in the read set (a single property, or all
     * accessible when "all"). Each call is property-scoped; the merge is the multi-property roll-up.
     *
     * @param  list<string>  $propertyIds
     * @return Collection<int, Reservation>
     */
    private function deskAcross(FrontDeskService $desk, string $method, array $propertyIds, string $date): Collection
    {
        /** @var Collection<int, Reservation> $merged */
        $merged = new Collection;

        foreach ($propertyIds as $propertyId) {
            /** @var Collection<int, Reservation> $rows */
            $rows = $desk->{$method}($propertyId, $date);
            $merged = $merged->merge($rows);
        }

        return $merged;
    }

    /**
     * Housekeeping status counts across the read set (real enum), + "to clean" = pending + in_progress.
     *
     * @param  list<string>  $propertyIds
     * @return array{pending: int, in_progress: int, clean: int, inspected: int, to_clean: int}
     */
    private function housekeeping(array $propertyIds): array
    {
        $counts = $propertyIds === []
            ? collect()
            : HousekeepingTask::query()
                ->whereIn('property_id', $propertyIds)
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
        // #104 GATE 3c — surface the wireframe's per-arrival detail (nights + assigned unit). Eager-load the active
        // unit lines once (name-only operational data, not PII — the #28 perimeter is unaffected).
        $rows = EloquentCollection::make($arrivals->take(8)->all());
        $rows->loadMissing(['reservationUnits' => fn ($q) => $q->where('status', 'active'), 'reservationUnits.unit']);

        return $rows->map(function (Reservation $reservation): array {
            $guest = $reservation->leadGuest;
            $name = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
            $meta = $this->reservationStatusMeta((string) $reservation->status);

            $nights = (int) CarbonImmutable::parse($reservation->check_in)->diffInDays(CarbonImmutable::parse($reservation->check_out));
            $unit = $reservation->reservationUnits
                ->map(fn ($line): string => (string) ($line->unit->code ?? ''))
                ->filter()->unique()->values()->implode(', ');

            return [
                'id' => $reservation->id,
                'reference' => (string) $reservation->reference,
                'guest' => $name !== '' ? $name : (string) $reservation->reference,
                'status_label' => $meta['label'],
                'tone' => $meta['tone'],
                'nights' => $nights,
                'unit' => $unit !== '' ? $unit : null,
            ];
        })->all();
    }
}
