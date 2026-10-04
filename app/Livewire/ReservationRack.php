<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Fee;
use App\Models\InventoryBlock;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\RateSeason;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Services\UnitAvailabilityService;
use App\Support\Folio\StayEstimator;
use App\Support\Money\MoneyFormatter;
use App\Support\Operator\CalendarReturn;
use App\Support\Operator\OperatorWorkspace;
use App\Support\Pricing\NightlyRate;
use App\Support\Property\BookableProperties;
use App\Support\Rates\SavedSeasonOffer;
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
 *     unit_rows: list<array{unit_id: string, code: string, name: string, state: string, state_label: string, sleeps: ?int,
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

    /** The sources a booking from here may carry — named as the full form names them ({@see CalendarReturn}, the one list). */
    private const SOURCES = CalendarReturn::SOURCES;

    /** How many nights the rack shows at once. */
    public int $days = 14;

    public function render(): View
    {
        $availability = app(AvailabilityService::class);

        $property = $this->currentProperty();

        /** @var Collection<int, RoomType> $allRoomTypes */
        $allRoomTypes = $property !== null
            // with each one's MAIN plan — the plan a stay is priced on, which the grid reads for the nights with no price
            ? RoomType::query()->where('property_id', $property->id)
                ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])
                ->orderBy('name')->get()
            : new Collection;

        // Room-type FILTER (real GET param) — the toolbar's "Filters" is now a real room-type narrow.
        $rtParam = request()->query('rt');
        $filterRt = is_string($rtParam) ? trim($rtParam) : '';   // a hand-made `rt[]=` was a 500 ("Array to string")
        // ⚠️ 2026-10-04 — only a room type of THIS property. A stale filter, or another property's (it rides along when the property
        //    is switched), emptied the grid and said "No room types on … yet."; it now shows them all.
        if (! $allRoomTypes->contains(fn (RoomType $rt): bool => (string) $rt->id === $filterRt)) {
            $filterRt = '';
        }
        $roomTypes = $filterRt !== ''
            ? $allRoomTypes->filter(fn (RoomType $rt): bool => (string) $rt->id === $filterRt)->values()
            : $allRoomTypes;

        // #104 GATE 3d — the view-range switcher (the wireframe's 7/14/30-night control), a real ?days= GET param
        // constrained to the allowed set (default 14). Navigation is GET-based, so re-resolving each request is safe.
        $this->days = $this->resolveDays();

        $start = $this->resolveStart($roomTypes->isEmpty() ? $allRoomTypes : $roomTypes);
        $end = $start->addDays(max(1, $this->days));

        // The property's own today — the first night a new booking may start (guest-facing day logic, in its timezone).
        $bookFrom = CarbonImmutable::now((string) ($property?->timezone ?: config('app.timezone')))->toDateString();
        $dates = $this->buildDates($start, $end, $bookFrom);
        $roomTypeIds = $roomTypes->pluck('id')->all();
        // ⚠️ the click-a-night links and the dialog share this one rule (the toolbar button's): the permission its write needs
        $canBook = ! app(OperatorWorkspace::class)->isReadOnly() && (bool) auth()->user()?->can('reservations.create');
        // ⚠️ 2026-10-04 — drag-to-move only for whoever may move a booking. Its write needs `reservations.assign`, which seven of the
        //    eleven back-office roles do not hold — and the bars were draggable for all of them, the drop then refused (#30: gate
        //    the control too).
        $canMove = ! app(OperatorWorkspace::class)->isReadOnly() && (bool) auth()->user()?->can('reservations.assign');
        $pricedNights = $this->pricedNights($roomTypes, $start, $end);
        $noRooms = $property !== null ? $this->noRooms($property) : null;
        $barsByType = $this->loadBars($roomTypeIds, $start, $end);
        $blocksByUnit = $this->loadBlocks($roomTypeIds, $start, $end);   // OOO / owner / manual blocks per unit

        $rows = [];
        foreach ($roomTypes as $roomType) {
            $rtLines = $barsByType->get($roomType->id, new Collection);
            $rtAvailability = $availability->availabilityRange($roomType->id, $start, $end);
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
                    /*
                     * D2-1 — the unit's real sleeping capacity, so an operator can size a booking against the
                     * row without leaving the rack. The units were already loaded for this row; only the
                     * column was never carried through.
                     *
                     * ⚠️ 0 BECOMES NULL, AND THAT DISTINCTION IS THE POINT. `units.max_occupancy` is
                     * `int unsigned NOT NULL DEFAULT 2`, so it can never BE null — but 0 is storable, and 0
                     * does not mean "sleeps nobody", it means nobody said. Rendering "Sleeps 0" would assert
                     * a capacity the data never claimed. Null here makes the view omit the fact rather than
                     * state a false one.
                     *
                     * ⚠️ This is the UNIT's capacity, never a reservation's party size — a different field on
                     * a different table, and the one it would be easiest to confuse it with on this screen.
                     */
                    'sleeps' => ($cap = (int) $unit->max_occupancy) > 0 ? $cap : null,
                    'bars' => $this->buildUnitBars($linesByUnit->get($unit->getKey(), new Collection), $start),
                    'ooo' => $this->buildOooBars($blocksByUnit->get((string) $unit->getKey(), new Collection), $start),
                ];
                $last = count($unitRows) - 1;
                $unitRows[$last]['free_nights'] = $state === 'active'
                    ? $this->freeNights($dates, $bookFrom, array_merge($unitRows[$last]['bars'], $unitRows[$last]['ooo']), $rtAvailability)
                    : [];
            }

            // ⚠️⚠️ 2026-10-04 (his report: "it is not possible to do reservation") — a night with rooms free and NO PRICE on the
            //    room type's main plan cannot be booked, and the strip said "2" either way. Marked from the property's today on.
            $priced = $pricedNights[(string) $roomType->id] ?? [];
            $noPrice = [];
            foreach ($dates as $d) {
                if (! $d['past'] && ($rtAvailability[$d['date']] ?? 0) >= 1 && ! isset($priced[$d['date']])) {
                    $noPrice[$d['date']] = true;
                }
            }

            $rows[] = [
                'room_type_id' => $roomType->id,
                'room_type' => $roomType->name,
                'kind' => (string) $roomType->kind,
                'units' => $activeUnits->count(),
                'availability' => $rtAvailability,
                'no_price' => $noPrice,
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
                // ⚠️ the property's own today — the column marked "today" and the first night a booking can start agree
                'today' => $bookFrom,
                // the room-type filter, kept on every way round the page (2026-10-04: the arrows, Today, 7/14/30, New reservation
                // and a dragged booking all dropped it)
                'rt' => $filterRt,
                'rt_q' => $filterRt !== '' ? '&rt='.$filterRt : '',
                'ranges' => CalendarReturn::RANGES,
            ],
            'dates' => $dates,
            'rows' => $rows,
            // Operator Calendar exact-match: the room-type filter options, the read-only flag, and the inline
            // new-reservation payload (real availability for the entered dates).
            'roomTypeOptions' => $allRoomTypes->map(fn (RoomType $rt): array => ['id' => (string) $rt->id, 'name' => (string) $rt->name])->all(),
            'filterRt' => $filterRt,
            'readOnly' => app(OperatorWorkspace::class)->isReadOnly(),
            'canBook' => $canBook,
            'canMove' => $canMove,
            'canSetPrice' => (bool) auth()->user()?->can('rates.manage'),
            'priceNote' => $this->priceNote($rows, $roomTypes),
            // the way forward from a property with no room that can take a booking — none added, or all paused or out of service —
            // the ones that can (2026-10-04, his workspace; the review, L6: room types with no bookable unit had no offer)
            'noRooms' => $noRooms,
            'elsewhere' => $noRooms !== null ? BookableProperties::elsewhere((string) $property->getKey()) : [],
            'newRes' => $this->newReservation($property, $start, $bookFrom, $canBook),
            // what a write sent from the dialog said, when the dialog is not open to say it (a paused workspace closes it)
            'dialogOk' => is_string(session('calendar_dialog_ok')) ? session('calendar_dialog_ok') : null,
            'dialogError' => is_string(session('calendar_dialog_error')) ? session('calendar_dialog_error') : null,
            // a booking made from the dialog — its reference is in the notice, and this opens it
            'created' => is_string(session('calendar_created')) ? session('calendar_created') : null,
            'refused' => session('calendar_refused') === true,
        ]);
    }

    /**
     * The nights of the window a NEW booking could start on this unit's row: from the property's today, not under one of its
     * bookings or blocks. The dialog then checks the real availability and the price — this only decides where a click may
     * start one (a night that looks free here may still be held by an unassigned booking; the dialog says so).
     *
     * ⚠️ AND ONLY WHERE THE ROOM TYPE HAS A ROOM TO SELL (the independent review): an unassigned booking or an unpublished
     * night leaves this unit's row empty while the header says 0 — and the dialog, which checks the room type, then dropped
     * it without a word. Every link is a one-night stay, so the night's own count is the whole check.
     *
     * @param  list<array{date: string}>  $dates
     * @param  list<array<string, mixed>>  $spans  the row's bars and blocks, each with a window offset and span
     * @param  array<string, int>  $available  the room type's rooms to sell, per night (the header strip)
     * @return array<string, string> night => the next day (a one-night stay's check-out)
     */
    private function freeNights(array $dates, string $bookFrom, array $spans, array $available): array
    {
        $covered = [];
        foreach ($spans as $span) {
            for ($i = max(0, (int) $span['offset']); $i < (int) $span['offset'] + (int) $span['span']; $i++) {
                $covered[$i] = true;
            }
        }

        $free = [];
        foreach ($dates as $i => $d) {
            if (! isset($covered[$i]) && $d['date'] >= $bookFrom && ($available[$d['date']] ?? 0) >= 1) {
                $free[$d['date']] = CarbonImmutable::parse($d['date'])->addDay()->toDateString();
            }
        }

        return $free;
    }

    /**
     * The NEW-RESERVATION MODAL payload — a two-phase, RMB-safe flow (invariant #20): the operator enters
     * the dates and party (a GET "Check rack" — the guest's name stays in the tab); when the dates are valid we compute the
     * REAL bookable units via the certified {@see UnitAvailabilityService} (the SAME read the full form + the write path
     * use), the operator picks one, and Confirm is a POST to the certified {@see ReservationCreateController}
     * → {@see AssignmentService::createBooking} (row-lock non-overlap). Nothing here writes; the modal never
     * sends a price (the server re-derives the stay total). A night clicked on a unit's row opens it with that unit
     * picked (`unit`, honoured only when it is free for the dates).
     *
     * @return array<string, mixed>
     */
    private function newReservation(?Property $property, CarbonImmutable $windowStart, string $bookFrom, bool $canBook): array
    {
        $q = request();
        // Closed unless asked for by someone who may book — the dialog's reads (availability, prices, taxes) run only then.
        if ($q->query('new') !== '1' || ! $canBook || $property === null) {
            return ['open' => false];
        }
        $propertyId = (string) $property->getKey();

        // ⚠️⚠️ NO GUEST NAME IN THE ADDRESS (2026-10-04). "Check rack" is a GET, and it used to carry `gf`/`gl` — the guest's
        //    names in browser history and the server's access logs, which no erase reaches (#28). The names now stay in the
        //    browser tab (sessionStorage) and only travel in the booking's POST; `gf`/`gl` are not read at all.
        // ⚠️ Never a check-in in the past by default: the calendar can show an earlier week, and its first night was the default.
        $checkIn = $this->validDate($q->query('ci')) ?? max($windowStart->toDateString(), $bookFrom);
        $source = $q->query('source');
        $form = [
            'adults' => max(1, min(12, (int) $q->query('adults', '2'))),
            'check_in' => $checkIn,
            'check_out' => $this->validDate($q->query('co')) ?? CarbonImmutable::parse($checkIn)->addDay()->toDateString(),
            'source' => is_string($source) && array_key_exists($source, self::SOURCES) ? $source : 'direct',
        ];

        $nights = $form['check_out'] > $form['check_in']
            ? (int) CarbonImmutable::parse($form['check_in'])->diffInDays(CarbonImmutable::parse($form['check_out']))
            : 0;
        $dateProblem = match (true) {
            $nights === 0 => 'Check-out must be after check-in.',
            // ⚠️ `min` only guards the GET; Confirm posts its own form — so a past day is refused here, by name (the review)
            $form['check_in'] < $bookFrom => 'Check-in can’t be in the past — pick today or later.',
            $nights > UnitAvailabilityService::MAX_NIGHTS => 'A booking can be at most '.UnitAvailabilityService::MAX_NIGHTS.' nights — pick a shorter stay.',
            default => null,
        };

        $offered = $dateProblem === null
            ? app(UnitAvailabilityService::class)->availableRoomTypes($propertyId, $form['check_in'], $form['check_out'])
            : [];
        $priced = array_values(array_filter($offered, fn (array $rt): bool => $rt['priced'] === true && $rt['units'] !== []));
        $ownPrices = $this->unitPrices($priced, $form['check_in'], $form['check_out']);

        $units = [];
        foreach ($priced as $rt) {
            $planId = (string) $rt['rate_plan_id'];
            foreach ($rt['units'] as $u) {
                // ⚠️ the unit's own price where it has one — exactly what the booking write charges (D18-5)
                $totalMinor = $ownPrices[$planId][(string) $u['id']] ?? (int) $rt['price_minor'];
                $units[] = [
                    'room_type_id' => (string) $rt['room_type_id'],
                    'room_type_name' => (string) $rt['room_type_name'],
                    'unit_id' => (string) $u['id'],
                    'unit_label' => (string) $u['code'],
                    'sleeps' => (int) $u['sleeps'],
                    'currency' => (string) $rt['currency'],
                    'total_minor' => $totalMinor,
                    'total_display' => MoneyFormatter::display($totalMinor, (string) $rt['currency']),
                ];
            }
        }

        // ⚠️ Said, never skipped: a room type with free units and no price for these nights used to vanish, and the dialog said
        //    "No units are free" — the dead end the founder hit on the full form (2026-10-04). One with no rate plan at all is
        //    named as such: the full form cannot price it either (the review). ⚠️⚠️ And since his next report the same day ("it is
        //    not possible to do reservation" on the Calendar), it carries what fixes it from inside the dialog.
        $canSetPrice = (bool) auth()->user()?->can('rates.manage');
        $planNames = RatePlan::query()->whereIn('id', array_values(array_filter(array_column($offered, 'rate_plan_id'))))->pluck('name', 'id')->all();
        $waiting = [];
        foreach ($offered as $rt) {
            if ($rt['priced'] !== true && $rt['units'] !== []) {
                $waiting[] = $this->waitingRoomType($rt, $form['check_in'], $form['check_out'], $planNames, $canSetPrice);
            }
        }

        $unitParam = $q->query('unit');
        $preselect = is_string($unitParam) ? $unitParam : '';   // a hand-made `unit[]=` was a 500 ("Array to string")
        // the room type of the unit clicked on the grid first — it is the one being booked (its fix was below the fold)
        usort($waiting, fn (array $a, array $b): int => (int) in_array($preselect, $b['unit_ids'], true) <=> (int) in_array($preselect, $a['unit_ids'], true));
        $rooms = Unit::query()->bookable()->where('property_id', $propertyId)->count();

        // ⚠️ The total shown is the room's; say so when the folio will add taxes or fees — judged in the STAY's currency (each
        //    offered plan's), which is what the write posts by, not the property's.
        $currencies = $units !== [] ? array_values(array_unique(array_column($units, 'currency'))) : [strtoupper((string) ($property->currency ?: 'EUR'))];
        $taxes = Tax::query()->where('property_id', $propertyId)->get();
        $fees = Fee::query()->where('property_id', $propertyId)->get();
        $addsCharges = false;
        foreach ($currencies as $currency) {
            $addsCharges = $addsCharges
                || $taxes->contains(fn (Tax $t): bool => StayEstimator::taxApplies($t, $currency))
                || $fees->contains(fn (Fee $f): bool => StayEstimator::feeApplies($f, $currency));
        }

        return [
            'open' => true,
            'form' => $form,
            'today' => $bookFrom,
            'nights' => $nights,
            'date_problem' => $dateProblem,
            'units' => $units,
            'waiting' => $waiting,
            'preselect' => collect($units)->contains('unit_id', $preselect) ? $preselect : '',
            // the unit clicked on the grid while its room type still waits for a price: kept through Set price / Apply, so it
            // comes back picked
            'clicked' => collect($waiting)->contains(fn (array $w): bool => in_array($preselect, $w['unit_ids'], true)) ? $preselect : '',
            'rooms' => $rooms,
            'rooms_all' => Unit::query()->where('property_id', $propertyId)->count(),
            // ⚠️⚠️ his workspace (2026-10-04): a property with no rooms named only "Go to Units" while his other one had three
            'others' => $rooms === 0 ? BookableProperties::elsewhere($propertyId) : [],
            'last_night' => $nights > 0 ? CarbonImmutable::parse($form['check_out'])->subDay()->toDateString() : null,
            // the "Apply this season?" view's facts (SeasonApplyFacts), for whoever may apply one
            'seasons' => $canSetPrice ? collect($waiting)->flatMap(fn (array $w): array => $w['seasons'])->keyBy('id')->all() : [],
            // what a price set or a season applied from here said, back inside the dialog
            'ok' => is_string(session('calendar_dialog_ok')) ? session('calendar_dialog_ok') : null,
            'error' => is_string(session('calendar_dialog_error')) ? session('calendar_dialog_error') : null,
            'checked' => $dateProblem === null,
            'sources' => self::SOURCES,
            'can_set_price' => (bool) auth()->user()?->can('rates.manage'),
            'adds_charges' => $addsCharges,
            'full_form_url' => route('app.reservations.create', ['check_in' => $form['check_in'], 'check_out' => $form['check_out']]),
        ];
    }

    /**
     * A room type with free units and NO PRICE for these nights, and what fixes it from inside the dialog (2026-10-04, the founder:
     * "it is not possible to do reservation" — on production his rooms had never had a price): for whoever manages rates, a price
     * for exactly these nights on its main plan (the Rates write's own rules, then back into the dialog), or a saved season that
     * was never applied ({@see SavedSeasonOffer}, the New reservation page's own rule, behind the Rates page's disclosure).
     *
     * ⚠️ The season's "Apply this season?" facts (a preview each) only for whoever may apply it — everyone else is told its name,
     * price and dates (the review, L7).
     *
     * @param  array<string, mixed>  $rt  a room type from {@see UnitAvailabilityService::availableRoomTypes()}
     * @param  array<string, string>  $planNames  plan id → name, read once for the dialog
     * @return array{name: string, units: int, unit_ids: list<string>, has_plan: bool, room_type_id: string, plan_name: string, symbol: string, unpriced: int, seasons: list<array<string, mixed>>}
     */
    private function waitingRoomType(array $rt, string $checkIn, string $checkOut, array $planNames, bool $canSetPrice): array
    {
        $planId = $rt['rate_plan_id'] !== null ? (string) $rt['rate_plan_id'] : '';
        $nights = (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
        $priced = $planId !== '' ? RateCalendar::query()->where('rate_plan_id', $planId)->where('date', '>=', $checkIn)->where('date', '<', $checkOut)->count() : 0;

        return [
            'name' => (string) $rt['room_type_name'],
            'units' => count($rt['units']),
            'unit_ids' => array_values(array_map(fn (array $u): string => (string) $u['id'], $rt['units'])),
            'has_plan' => $planId !== '',
            'room_type_id' => (string) $rt['room_type_id'],
            'plan_name' => (string) ($planNames[$planId] ?? ''),
            'symbol' => MoneyFormatter::symbol((string) $rt['currency']),
            'unpriced' => max(0, $nights - $priced),
            'seasons' => $planId !== '' ? SavedSeasonOffer::forStay($planId, $checkIn, $checkOut, withFacts: $canSetPrice) : [],
        ];
    }

    /**
     * Whether this property has a unit that can take a booking — and if not, what the Calendar says instead of an empty grid of
     * "0 units" (2026-10-04, his workspace: a property with no rooms; the review, L6: room types whose units are all paused or out
     * of service, or with no units, had no way on). The same two sentences as the dialog's.
     *
     * @return array{title: string, line: string}|null null when at least one unit can take a booking
     */
    private function noRooms(Property $property): ?array
    {
        if (Unit::query()->bookable()->where('property_id', $property->getKey())->exists()) {
            return null;
        }

        return Unit::query()->where('property_id', $property->getKey())->exists()
            ? ['title' => 'None of '.$property->name.'’s units can take a booking', 'line' => 'They’re all paused or out of service — reopen one on the Units page to book it here.']
            : ['title' => $property->name.' has no rooms yet', 'line' => 'Its rooms show here once its units are added.'];
    }

    /**
     * The nights in view that carry a price on each room type's MAIN plan — the plan a stay is priced on
     * ({@see RoomType::primaryRatePlan()}; {@see UnitAvailabilityService} prices a stay only when every night has a rate there).
     * A room type with no plan has none. One read for the window.
     *
     * @param  Collection<int, RoomType>  $roomTypes
     * @return array<string, array<string, true>> room type id → date → true
     */
    private function pricedNights(Collection $roomTypes, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $out = [];
        $typeOf = [];
        foreach ($roomTypes as $roomType) {
            $out[(string) $roomType->id] = [];
            $plan = $roomType->primaryRatePlan();
            if ($plan !== null) {
                $typeOf[(string) $plan->getKey()] = (string) $roomType->id;
            }
        }
        if ($typeOf === []) {
            return $out;
        }

        foreach (RateCalendar::query()->whereIn('rate_plan_id', array_keys($typeOf))->where('date', '>=', $start->toDateString())
            ->where('date', '<', $end->toDateString())->toBase()->get(['rate_plan_id', 'date']) as $row) {
            $out[$typeOf[(string) $row->rate_plan_id]][CarbonImmutable::parse((string) $row->date)->toDateString()] = true;
        }

        return $out;
    }

    /**
     * What the grid says above itself when some nights in view cannot be booked for want of a price: which room types, and a
     * saved season that would price them but was never applied (his "Winter", 28 Sep → 31 Oct, on production). Null when every
     * night that has a room to sell has a price.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  Collection<int, RoomType>  $roomTypes
     * @return array{names: string, seasons: list<string>}|null
     */
    private function priceNote(array $rows, Collection $roomTypes): ?array
    {
        $marked = array_values(array_filter($rows, fn (array $r): bool => $r['no_price'] !== []));
        if ($marked === []) {
            return null;
        }

        // each marked room type's main plan → its marked nights; then ONE read of the unapplied seasons over them (the review, L7)
        $nightsOf = [];
        foreach ($marked as $row) {
            $plan = $roomTypes->first(fn (RoomType $rt): bool => (string) $rt->id === (string) $row['room_type_id'])?->primaryRatePlan();
            if ($plan !== null) {
                $nightsOf[(string) $plan->getKey()] = array_keys($row['no_price']);
            }
        }
        $all = array_merge(...array_values($nightsOf ?: [[]]));
        $candidates = $nightsOf === [] ? [] : RateSeason::query()->whereIn('rate_plan_id', array_keys($nightsOf))->whereNull('applied_at')
            ->where('starts_on', '<=', max($all))->where('ends_on', '>=', min($all))->orderBy('starts_on')->get();

        $seasons = [];
        foreach ($candidates as $season) {
            $nights = $nightsOf[(string) $season->rate_plan_id] ?? [];
            $from = $season->starts_on->toDateString();
            $to = $season->ends_on->toDateString();
            if (array_filter($nights, fn (string $d): bool => $d >= $from && $d <= $to) === []) {
                continue;   // it covers none of its room type's marked nights — not the reason they have no price
            }
            $seasons[(string) $season->getKey()] = $season->name.' ('.MoneyFormatter::display((int) $season->price_minor, (string) $season->currency)
                .' a night, '.$season->starts_on->format('j M').' → '.$season->ends_on->format('j M').')';
        }

        $names = array_map(fn (array $r): string => (string) $r['room_type'], $marked);
        $last = array_pop($names);

        return ['names' => $names === [] ? (string) $last : implode(', ', $names).' and '.$last, 'seasons' => array_values($seasons)];
    }

    /**
     * Per-unit stay prices for the offered room types' plans, keyed plan → unit — one query finds the pairs that carry a
     * per-unit price in the stay, then the booking write's own function prices each (the full form's way). A unit without
     * one is absent and costs its plan's price.
     *
     * @param  list<array<string, mixed>>  $roomTypes  priced room types from {@see UnitAvailabilityService::availableRoomTypes()}
     * @return array<string, array<string, int>>
     */
    private function unitPrices(array $roomTypes, string $checkIn, string $checkOut): array
    {
        $planIds = array_values(array_filter(array_map(fn (array $rt): string => (string) $rt['rate_plan_id'], $roomTypes)));
        $unitIds = [];
        foreach ($roomTypes as $rt) {
            foreach ($rt['units'] as $u) {
                $unitIds[] = (string) $u['id'];
            }
        }
        if ($planIds === [] || $unitIds === []) {
            return [];
        }

        $pairs = UnitRateOverride::query()
            ->whereIn('rate_plan_id', $planIds)
            ->whereIn('unit_id', $unitIds)
            ->whereDate('date', '>=', $checkIn)
            ->whereDate('date', '<', $checkOut)
            ->distinct()
            ->get(['rate_plan_id', 'unit_id']);

        $prices = [];
        foreach ($pairs as $pair) {
            $total = NightlyRate::stayTotalForUnit((string) $pair->rate_plan_id, (string) $pair->unit_id, $checkIn, $checkOut);
            if ($total !== null) {
                $prices[(string) $pair->rate_plan_id][(string) $pair->unit_id] = $total;
            }
        }

        return $prices;
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;   // "2026-02-31" is not a date, not 3 March
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

        return in_array($days, CalendarReturn::RANGES, true) ? $days : 14;   // the one list (the review, L8)
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
     * @return list<array{date: string, dow: string, day: string, mon: string, weekend: bool, past: bool}>
     */
    private function buildDates(CarbonImmutable $start, CarbonImmutable $end, string $today): array
    {
        $dates = [];
        for ($date = $start; $date->lessThan($end); $date = $date->addDay()) {
            $dates[] = [
                'date' => $date->toDateString(),
                'dow' => $date->format('D'),
                'day' => $date->format('j'),
                'mon' => $date->format('M'),
                'weekend' => $date->isWeekend(),
                // before the property's today — greyed, and never offered for a new booking (2026-10-04)
                'past' => $date->toDateString() < $today,
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
