<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\InventoryBlock;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Unit;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\UnitAvailabilityService;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Schedule\BoardDate;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator Calendar — the reservation rack / timeline grid (design pass · Operator Screen 1). Styled
 * from the catalogued "Operator Calendar" design onto the SAME formalized Vyrobel design system as the
 * owner portal (frosted glass, terracotta, Sora/Inter), via a dedicated operator layout + the shared
 * <x-operator.shell>. Presentation + a thin read only: NO change to AssignmentService / AvailabilityService
 * / schema / any booking guarantee — this component only READS.
 *
 * TENANT-scoped (operator surface), NOT owner-scoped: every model is BelongsToOrganization, so the
 * SetTenantForWebRequest middleware's active org auto-scopes reads — one org never sees another's
 * reservations. Window navigation is via plain GET links (`?start=YYYY-MM-DD`), never Livewire
 * wire-actions, so each request re-runs the tenant middleware (the RMB/scope rule proven on the owner
 * portal). No route-model-bound props.
 *
 * Honesty triage (rule locked in DECISIONS.md 2026-07-11):
 *   • REAL      — the first tenant property; room-type rows; the date window; per-night AVAILABILITY
 *                 (AvailabilityService, never fabricated); BOOKING BARS from active reservation_unit
 *                 lines with the real guest name, real reservation status, real source, real dates.
 *   • DERIVED   — the occupancy summary is computed from the real availability figures only.
 *   • DEFERRED  — drag-to-reschedule (would mutate inventory via AssignmentService — out of a
 *                 presentation pass) is shown as a labelled "not yet available" note, never wired.
 *                 Overbooked lines (no unit / no inventory) are intentionally excluded from the grid.
 *   • NO BACKEND — New reservation, filters, channel filter, export: disabled "Soon" affordances.
 *   • Guest PII  — an OPERATOR surface shows guest NAMES (as Front Desk does); encrypted email/phone
 *                 are never rendered.
 *
 * The view receives:
 *   viewer: array{name: string, initials: string}
 *   property: array{id: string, name: string}|null
 *   window: array{start: string, end: string, label: string, days: int, prev: string, next: string, today: string}
 *   dates: list<array{date: string, dow: string, day: string, mon: string, weekend: bool}>
 *   rows: list<array{
 *     room_type_id: string, room_type: string, kind: string, units: int,
 *     availability: array<string, int>,
 *     unit_rows: list<array{unit_id: string, code: string, name: string, state: string, state_label: string,
 *       bars: list<array<string, mixed>>,
 *       ooo: list<array{offset: int, span: int, reason: string, label: string, clip_start: bool, clip_end: bool}>}>,
 *     unassigned: list<list<array<string, mixed>>>,
 *   }>
 */
#[Layout('components.layouts.operator')]
class ReservationRack extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    /** How many nights the rack shows at once. */
    public int $days = 14;

    public function render(): View
    {
        $availability = app(AvailabilityService::class);

        $property = $this->currentProperty();

        /** @var Collection<int, RoomType> $allRoomTypes */
        $allRoomTypes = $property !== null
            ? RoomType::query()->where('property_id', $property->id)->orderBy('name')->get()
            : new Collection;

        // Room-type FILTER (real GET param) — the toolbar's "Filters" is now a real room-type narrow.
        $filterRt = trim((string) request()->query('rt', ''));
        $roomTypes = $filterRt !== ''
            ? $allRoomTypes->filter(fn (RoomType $rt): bool => (string) $rt->id === $filterRt)->values()
            : $allRoomTypes;

        // #104 GATE 3d — the view-range switcher (the wireframe's 7/14/30-night control), a real ?days= GET param
        // constrained to the allowed set (default 14). Navigation is GET-based, so re-resolving each request is safe.
        $this->days = $this->resolveDays();

        $start = $this->resolveStart($roomTypes->isEmpty() ? $allRoomTypes : $roomTypes);
        $end = $start->addDays(max(1, $this->days));

        $dates = $this->buildDates($start, $end);
        $roomTypeIds = $roomTypes->pluck('id')->all();
        $barsByType = $this->loadBars($roomTypeIds, $start, $end);
        $blocksByUnit = $this->loadBlocks($roomTypeIds, $start, $end);   // OOO / owner / manual blocks per unit

        $rows = [];
        foreach ($roomTypes as $roomType) {
            $rtLines = $barsByType->get($roomType->id, new Collection);
            $linesByUnit = $rtLines->groupBy('unit_id');

            /** @var Collection<int, Unit> $activeUnits */
            $activeUnits = $roomType->units()->where('is_active', true)->orderBy('code')->get();
            $activeIds = $activeUnits->map(fn (Unit $u): string => (string) $u->getKey())->all();

            // A booking is NEVER dropped: an owner-PAUSED unit (is_active=false) keeps its existing stays
            // (#7/#69), so any active in-window line on a non-active unit still needs a row. Pull those units
            // in (paused, but shown) — otherwise their real guest silently vanishes from the primary calendar.
            $orphanUnitIds = $rtLines
                ->filter(fn (ReservationUnit $l): bool => $l->unit_id !== null && ! in_array((string) $l->unit_id, $activeIds, true))
                ->pluck('unit_id')->unique()->values();
            $pausedUnits = $orphanUnitIds->isEmpty()
                ? new Collection
                : $roomType->units()->whereIn('id', $orphanUnitIds->all())->orderBy('code')->get();

            // GATE 5.1 — one row PER PHYSICAL UNIT (the wireframe's axis): each unit's own bookings + its
            // out-of-order / block periods (hatched). A unit never double-books, so its bars don't need lanes.
            // A non-bookable STATE (paused / out-of-service) is a DATELESS condition — carried as a flag and
            // rendered as a full-window band, NEVER a fabricated date-ranged bar (#39). It makes the row
            // reconcile with the header availability count (which already subtracts these units).
            $unitRows = [];
            foreach ($activeUnits->concat($pausedUnits) as $unit) {
                $state = match (true) {
                    ! (bool) $unit->is_active => 'paused',
                    in_array((string) $unit->condition_status, ['out_of_order', 'out_of_service'], true) => 'out_of_service',
                    default => 'active',
                };
                $unitRows[] = [
                    'unit_id' => (string) $unit->getKey(),
                    'code' => (string) $unit->code,
                    'name' => (string) ($unit->name ?? $unit->code),
                    'state' => $state,
                    'state_label' => match ($state) {
                        'paused' => 'Paused',
                        'out_of_service' => 'Out of service',
                        default => '',
                    },
                    'bars' => $this->buildUnitBars($linesByUnit->get($unit->getKey(), new Collection), $start),
                    'ooo' => $this->buildOooBars($blocksByUnit->get((string) $unit->getKey(), new Collection), $start),
                ];
            }

            $rows[] = [
                'room_type_id' => $roomType->id,
                'room_type' => $roomType->name,
                'kind' => (string) $roomType->kind,
                'units' => $activeUnits->count(),
                'availability' => $availability->availabilityRange($roomType->id, $start, $end),
                'unit_rows' => $unitRows,
                // Reservations with no unit yet (they consume room-type inventory but no unit row) — a real
                // "Unassigned" track so the operator can see + seat them; lane-packed (they can overlap).
                // Filtered explicitly (not groupBy(null), whose empty-string key is ambiguous).
                'unassigned' => $this->packLanes(
                    $rtLines->filter(fn (ReservationUnit $line): bool => $line->unit_id === null)->values(),
                    $start,
                ),
            ];
        }

        return view('livewire.reservation-rack', [
            'viewer' => $this->viewer(),
            'property' => $property !== null ? ['id' => $property->id, 'name' => $property->name] : null,
            'window' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $start->format('d M').' – '.$end->subDay()->format('d M Y'),
                'days' => count($dates),
                'selected_days' => $this->days,   // the chosen 7/14/30 (for the range switcher + to preserve across nav)
                'prev' => $start->subDays(max(1, $this->days))->toDateString(),
                'next' => $end->toDateString(),
                'today' => CarbonImmutable::today()->toDateString(),
            ],
            'dates' => $dates,
            'rows' => $rows,
            // Operator Calendar exact-match: the room-type filter options, the read-only flag, and the inline
            // new-reservation payload (real availability for the entered dates).
            'roomTypeOptions' => $allRoomTypes->map(fn (RoomType $rt): array => ['id' => (string) $rt->id, 'name' => (string) $rt->name])->all(),
            'filterRt' => $filterRt,
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
            'newRes' => $this->newReservation($property?->id, $start),
        ]);
    }

    /**
     * The NEW-RESERVATION MODAL payload — a two-phase, RMB-safe flow (invariant #20): the operator enters
     * guest + dates (a GET "Check rack"); when the dates are valid we compute the REAL bookable units via the
     * certified {@see UnitAvailabilityService} (the SAME read the full form + the write path use), the operator
     * picks one, and Confirm is a POST to the certified {@see ReservationCreateController}
     * → {@see AssignmentService::createBooking} (row-lock non-overlap). Nothing here writes; the modal never
     * sends a price (the server re-derives the stay total).
     *
     * @return array<string, mixed>
     */
    private function newReservation(?string $propertyId, CarbonImmutable $windowStart): array
    {
        $q = request();
        $open = $q->query('new') === '1';

        $form = [
            'guest_first' => trim((string) $q->query('gf', '')),
            'guest_last' => trim((string) $q->query('gl', '')),
            'adults' => max(1, min(12, (int) $q->query('adults', '2'))),
            'check_in' => $this->validDate($q->query('ci')) ?? $windowStart->toDateString(),
            'check_out' => $this->validDate($q->query('co')) ?? $windowStart->addDay()->toDateString(),
            'source' => in_array($q->query('source'), ['direct', 'walk_in', 'corporate', 'agent'], true) ? (string) $q->query('source') : 'direct',
        ];

        $nights = 0;
        $units = [];
        if ($open && $propertyId !== null && $form['check_out'] > $form['check_in']) {
            $nights = (int) CarbonImmutable::parse($form['check_in'])->diffInDays(CarbonImmutable::parse($form['check_out']));

            // Flatten to a single radio list of bookable UNITS (the modal's room list), each carrying the REAL
            // server-derived stay total (price_minor is the sum over the nights — never a client-supplied price).
            foreach (app(UnitAvailabilityService::class)->availableRoomTypes($propertyId, $form['check_in'], $form['check_out']) as $rt) {
                if ($rt['priced'] !== true || $rt['units'] === []) {
                    continue;   // only offer genuinely bookable + priced room types
                }
                $totalMinor = (int) $rt['price_minor'];
                $currency = (string) $rt['currency'];
                foreach ($rt['units'] as $u) {
                    $units[] = [
                        'room_type_id' => (string) $rt['room_type_id'],
                        'room_type_name' => (string) $rt['room_type_name'],
                        'unit_id' => (string) $u['id'],
                        'unit_label' => (string) $u['code'],
                        'total_minor' => $totalMinor,
                        'total_display' => MoneyFormatter::display($totalMinor, $currency),
                    ];
                }
            }
        }

        return [
            'open' => $open,
            'form' => $form,
            'nights' => $nights,
            'units' => $units,
            'checked' => $open && $form['check_out'] > $form['check_in'],
            'sources' => ['direct' => 'Direct', 'walk_in' => 'Walk-in', 'corporate' => 'Corporate', 'agent' => 'Agent'],
        ];
    }

    private function validDate(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            ? CarbonImmutable::parse($value)->toDateString()
            : null;
    }

    /**
     * The window's first night: `?start=YYYY-MM-DD` if valid, else TODAY when there's current/upcoming
     * occupancy, else the most recent PAST arrival for a stale dataset — never the furthest-future booking
     * (F-OP-1; shared with the Front Desk via {@see BoardDate}).
     *
     * @param  Collection<int, RoomType>  $roomTypes
     */
    /** The view-range from ?days=, constrained to the allowed set (7/14/30); default 14. */
    private function resolveDays(): int
    {
        $days = (int) request()->query('days', '14');

        return in_array($days, [7, 14, 30], true) ? $days : 14;
    }

    private function resolveStart(Collection $roomTypes): CarbonImmutable
    {
        $param = request()->query('start');
        $roomTypeIds = $roomTypes->pluck('id')->all();

        if ($roomTypeIds === []) {
            return BoardDate::resolve($param, false, null);
        }

        $today = CarbonImmutable::today()->toDateString();
        $base = ReservationUnit::query()->whereIn('room_type_id', $roomTypeIds)->where('status', 'active');

        $hasCurrentOrFuture = (clone $base)->where('check_out', '>', $today)->exists();
        $latestOnOrBefore = (clone $base)->where('check_in', '<=', $today)->max('check_in');

        // Shared rule with the Front Desk (F-OP-1): today when there's current/upcoming occupancy, else the most
        // recent PAST arrival for a stale dataset, NEVER the furthest-future booking. {@see BoardDate}
        return BoardDate::resolve($param, $hasCurrentOrFuture, $latestOnOrBefore !== null ? (string) $latestOnOrBefore : null);
    }

    /**
     * @return list<array{date: string, dow: string, day: string, mon: string, weekend: bool}>
     */
    private function buildDates(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];
        for ($date = $start; $date->lessThan($end); $date = $date->addDay()) {
            $dates[] = [
                'date' => $date->toDateString(),
                'dow' => $date->format('D'),
                'day' => $date->format('j'),
                'mon' => $date->format('M'),
                'weekend' => $date->isWeekend(),
            ];
        }

        return $dates;
    }

    /**
     * Active per-unit lines overlapping the window, grouped by room_type. Read-only; overbooked lines
     * (no unit, no inventory consumed) are excluded from the occupancy grid on purpose.
     *
     * @param  list<string>  $roomTypeIds
     * @return Collection<array-key, EloquentCollection<int, ReservationUnit>>
     */
    private function loadBars(array $roomTypeIds, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        if ($roomTypeIds === []) {
            return new Collection;
        }

        return ReservationUnit::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->where('status', 'active')
            ->whereDate('check_in', '<', $end->toDateString())
            ->whereDate('check_out', '>', $start->toDateString())
            ->with(['reservation.leadGuest'])
            ->orderBy('check_in')
            ->get()
            ->groupBy('room_type_id');
    }

    /**
     * Pack a room type's lines into non-overlapping horizontal lanes (first-fit) so two units booked
     * on the same nights render on separate rows instead of colliding. Each bar is clamped to the
     * visible window.
     *
     * @param  Collection<int, ReservationUnit>  $lines
     * @return list<list<array<string, mixed>>>
     */
    private function packLanes(Collection $lines, CarbonImmutable $start): array
    {
        /** @var list<list<array{end: int, bar: array<string, mixed>}>> $lanes */
        $lanes = [];

        foreach ($lines as $line) {
            $bar = $this->buildBar($line, $start);
            if ($bar === null) {
                continue;
            }
            $barStart = $bar['offset'];
            $barEnd = $bar['offset'] + $bar['span'];

            $placed = false;
            foreach ($lanes as $i => $lane) {
                $last = $lane[count($lane) - 1];
                if ($barStart >= $last['end']) {
                    $lanes[$i][] = ['end' => $barEnd, 'bar' => $bar];
                    $placed = true;
                    break;
                }
            }
            if (! $placed) {
                $lanes[] = [['end' => $barEnd, 'bar' => $bar]];
            }
        }

        // Strip the packing bookkeeping, keeping only the bar payloads per lane.
        return array_map(
            static fn (array $lane): array => array_map(static fn (array $slot): array => $slot['bar'], $lane),
            $lanes,
        );
    }

    /**
     * Geometry + real display fields for one booking bar, clamped to the window. Returns null if the
     * line falls entirely outside the visible nights.
     *
     * @return array<string, mixed>|null
     */
    private function buildBar(ReservationUnit $line, CarbonImmutable $windowStart): ?array
    {
        $reservation = $line->reservation;
        if ($reservation === null) {
            return null;
        }

        $checkIn = CarbonImmutable::parse($line->check_in)->startOfDay();
        $checkOut = CarbonImmutable::parse($line->check_out)->startOfDay();

        $barStart = $checkIn->greaterThan($windowStart) ? $checkIn : $windowStart;
        $windowEnd = $windowStart->addDays(max(1, $this->days));
        $barEnd = $checkOut->lessThan($windowEnd) ? $checkOut : $windowEnd;

        $offset = (int) $windowStart->diffInDays($barStart);
        $span = (int) $barStart->diffInDays($barEnd);
        if ($span <= 0) {
            return null;
        }

        $meta = $this->reservationStatusMeta((string) $reservation->status);
        $guest = $reservation->leadGuest;
        $name = $guest !== null
            ? trim(((string) $guest->first_name).' '.((string) $guest->last_name))
            : '';

        return [
            // #104 GATE 3d — the reservation id so the rack's booking bar links to its detail (the wireframe's
            // clickable bar). Name-only otherwise (#28).
            'id' => (string) $reservation->getKey(),
            // GATE 5.3 — the reservation_unit (line) id: what drag-to-reschedule moves (POST target is the line).
            'line_id' => (string) $line->getKey(),
            'ref' => (string) $reservation->reference,
            'guest' => $name !== '' ? $name : (string) $reservation->reference,
            'status' => (string) $reservation->status,
            'status_label' => $meta['label'],
            'tone' => $meta['tone'],
            'source' => $this->sourceLabel((string) $reservation->source),
            'nights' => (int) $checkIn->diffInDays($checkOut),
            'offset' => $offset,
            'span' => $span,
            'clip_start' => $checkIn->lessThan($windowStart),
            'clip_end' => $checkOut->greaterThan($windowEnd),
        ];
    }

    /**
     * GATE 5.1 — the REAL out-of-order / block periods that show as hatched bars on a unit's row: unit-level
     * inventory_blocks (unit_id set) overlapping the window, grouped by unit. Room-type-level blocks
     * (unit_id null) reduce the room-type availability count already and belong to no single unit row, so
     * they are not carried here. Read-only; never fabricated (an empty unit shows no hatch).
     *
     * @param  list<string>  $roomTypeIds
     * @return Collection<array-key, EloquentCollection<int, InventoryBlock>>
     */
    private function loadBlocks(array $roomTypeIds, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        if ($roomTypeIds === []) {
            return new Collection;
        }

        return InventoryBlock::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->whereNotNull('unit_id')
            ->whereDate('start_date', '<', $end->toDateString())
            ->whereDate('end_date', '>', $start->toDateString())
            ->orderBy('start_date')
            ->get()
            ->groupBy('unit_id');
    }

    /**
     * A single physical unit's booking bars, clamped to the window. A unit never double-books (the
     * no-overlap guarantee), so its active lines never collide — no lane packing, one bar per line.
     *
     * @param  Collection<int, ReservationUnit>  $lines
     * @return list<array<string, mixed>>
     */
    private function buildUnitBars(Collection $lines, CarbonImmutable $start): array
    {
        $bars = [];
        foreach ($lines as $line) {
            $bar = $this->buildBar($line, $start);
            if ($bar !== null) {
                $bars[] = $bar;
            }
        }

        return $bars;
    }

    /**
     * Geometry + a real reason label for each of a unit's block periods, clamped to the window. Half-open
     * `[start_date, end_date)`, same math as a booking bar. Returns [] when the unit has none.
     *
     * @param  Collection<int, InventoryBlock>  $blocks
     * @return list<array{offset: int, span: int, reason: string, label: string, clip_start: bool, clip_end: bool}>
     */
    private function buildOooBars(Collection $blocks, CarbonImmutable $windowStart): array
    {
        $windowEnd = $windowStart->addDays(max(1, $this->days));

        $bars = [];
        foreach ($blocks as $block) {
            $blockStart = CarbonImmutable::parse($block->start_date)->startOfDay();
            $blockEnd = CarbonImmutable::parse($block->end_date)->startOfDay();

            $barStart = $blockStart->greaterThan($windowStart) ? $blockStart : $windowStart;
            $barEnd = $blockEnd->lessThan($windowEnd) ? $blockEnd : $windowEnd;

            $span = (int) $barStart->diffInDays($barEnd);
            if ($span <= 0) {
                continue;
            }

            $reason = (string) $block->reason;
            $bars[] = [
                'offset' => (int) $windowStart->diffInDays($barStart),
                'span' => $span,
                'reason' => $reason,
                'label' => match ($reason) {
                    'maintenance' => 'Out of order',
                    'owner' => 'Owner block',
                    'overbooking_protection' => 'Held',
                    default => 'Blocked',   // 'manual' + any legacy value
                },
                'clip_start' => $blockStart->lessThan($windowStart),
                'clip_end' => $blockEnd->greaterThan($windowEnd),
            ];
        }

        return $bars;
    }
}
