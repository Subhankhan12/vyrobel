<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\UnitAvailabilityService;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\OperatorWorkspace;
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
 *     lanes: list<list<array{ref: string, guest: string, status: string, status_label: string,
 *       tone: string, source: string, nights: int, offset: int, span: int,
 *       clip_start: bool, clip_end: bool}>>,
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
        $barsByType = $this->loadBars($roomTypes->pluck('id')->all(), $start, $end);

        $rows = [];
        foreach ($roomTypes as $roomType) {
            $rows[] = [
                'room_type_id' => $roomType->id,
                'room_type' => $roomType->name,
                'kind' => (string) $roomType->kind,
                'units' => (int) $roomType->units()->where('is_active', true)->count(),
                'availability' => $availability->availabilityRange($roomType->id, $start, $end),
                'lanes' => $this->packLanes($barsByType->get($roomType->id, new Collection), $start),
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
     * The inline NEW-RESERVATION panel payload — a two-phase, RMB-safe flow (invariant #20): the operator
     * enters guest + dates (a GET "Check rack"); when the dates are valid we compute the REAL bookable units
     * via the certified {@see UnitAvailabilityService} (the SAME read the full form + the write path use), and
     * each unit's Confirm is a POST to the certified {@see ReservationCreateController}
     * → {@see AssignmentService::createBooking} (row-lock non-overlap). Nothing here writes.
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
        $options = [];
        if ($open && $propertyId !== null && $form['check_out'] > $form['check_in']) {
            $nights = (int) CarbonImmutable::parse($form['check_in'])->diffInDays(CarbonImmutable::parse($form['check_out']));

            foreach (app(UnitAvailabilityService::class)->availableRoomTypes($propertyId, $form['check_in'], $form['check_out']) as $rt) {
                if ($rt['priced'] !== true || $rt['units'] === []) {
                    continue;   // only offer genuinely bookable + priced room types
                }
                $options[] = [
                    'room_type_id' => (string) $rt['room_type_id'],
                    'room_type_name' => (string) $rt['room_type_name'],
                    'rate' => MoneyFormatter::display((int) $rt['price_minor'], (string) $rt['currency']),
                    'units' => array_map(static fn (array $u): array => [
                        'id' => (string) $u['id'],
                        'label' => (string) $u['code'],
                    ], $rt['units']),
                ];
            }
        }

        return [
            'open' => $open,
            'form' => $form,
            'nights' => $nights,
            'options' => $options,
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
     * The window's first night: `?start=YYYY-MM-DD` if valid, else the most recent active booking's
     * check-in (so the demo/live rack lands on real occupancy), else today.
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
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            return CarbonImmutable::parse($param)->startOfDay();
        }

        $roomTypeIds = $roomTypes->pluck('id')->all();
        $latest = $roomTypeIds === [] ? null : ReservationUnit::query()
            ->whereIn('room_type_id', $roomTypeIds)
            ->where('status', 'active')
            ->max('check_in');

        return $latest !== null
            ? CarbonImmutable::parse($latest)->startOfDay()
            : CarbonImmutable::today();
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
}
