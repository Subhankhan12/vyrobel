<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\RateActionController;
use App\Http\Controllers\Operator\ReservationCreateController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AddOn;
use App\Models\Fee;
use App\Models\Guest;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Services\AssignmentService;
use App\Services\UnitAvailabilityService;
use App\Support\Folio\StayEstimator;
use App\Support\Guest\GuestHistory;
use App\Support\Money\MoneyFormatter;
use App\Support\Pricing\NightlyRate;
use App\Support\Property\PropertyContext;
use App\Support\QrSvg;
use App\Support\Reservations\ReservationExtras;
use App\Support\StayTime;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator NEW RESERVATION screen (Wave 4 · Sub-gate 2) — create one direct booking, on the shared operator
 * design system via <x-operator.shell>. This component READS ONLY (availability + a guest name-search);
 * the booking itself is a guarded POST → {@see ReservationCreateController}
 * → {@see AssignmentService::createBooking()} (invariant #20 — scope-changing reads ride GET
 * links, the write rides a POST form, never a wire-action that bypasses the tenant middleware).
 *
 * The three guards (docs/WAVE4-INVENTORY.md — the design is "SAFE as drawn: ❌"):
 *   • GUARD 1 — HONEST AVAILABILITY. The unit list comes from {@see UnitAvailabilityService}, which offers a
 *     unit ONLY when createBooking would accept it (room-type Level A + physical-unit Level B) AND the room
 *     type is priced. An occupied/blocked unit is never shown; an unpriced room type is shown without its
 *     units. The controller re-derives this server-side, and createBooking's row lock is the actual
 *     guarantee — this screen just never offers the unbookable.
 *   • GUARD 2 — PII. The existing-guest search shows the guest NAME only. `guests.email`/`phone` are
 *     encrypted casts and are neither rendered nor queried here (encrypted text is not indexed by value — a
 *     LIKE would match nothing; the standing blind-index deferral). A NEW guest's contact details are
 *     collected into those encrypted columns by the controller, never echoed back — not even after a refusal.
 *   • GUARD 3 — NO RAW PAN (PCI). There is NO card-number field. Payment is an optional MANUAL folio payment
 *     (cash / bank), and only for a user who holds `payments.record`; card capture is an honest "Soon".
 *
 * ⚠️⚠️ 2026-10-04 — THE FOUNDER: "it is not possible to do reservation". 🔬 Read on production: his first property has no
 * rooms, and the other has rooms with no price on any of the next 60 nights. The wizard said "No units are free" for the
 * first, offered only a link that threw the booking away for the second, and — being a focused screen without the app's
 * shell — hid the property switcher, so it silently booked into whichever property came first. Now: a booking asks which
 * property while "All properties" is selected ({@see PropertyContext::isAll()} is a READ roll-up; a write needs one), says
 * when a property has no rooms, and lets a person who manages rates set the stay's missing price right here through the
 * Rates write's own rules ({@see RateActionController}) and come back ready to book.
 *
 * TENANT-scoped throughout (Property/RoomType/Unit/Guest are BelongsToOrganization); `/app` is behind
 * EnsureBackOfficeAccess (#26) and the route carries `can:reservations.create` (#30). Proven by
 * OperatorNewReservationTest and NewReservationWizardTest.
 */
#[Layout('components.layouts.operator')]
class NewReservation extends Component
{
    use FormatsOperatorData;
    use ResolvesCurrentProperty;

    private const MAX_QUERY = 100;

    /** Guest name-search results are bounded — a picker, not a directory dump. */
    private const GUEST_LIMIT = 8;

    /** The booking sources an operator may choose here — the controller accepts exactly these. */
    private const SOURCES = [
        ['value' => 'direct', 'label' => 'Direct'],
        ['value' => 'walk_in', 'label' => 'Walk-in'],
        ['value' => 'corporate', 'label' => 'Corporate'],
        ['value' => 'agent', 'label' => 'Travel agent'],
    ];

    public function render(UnitAvailabilityService $availability): View
    {
        $done = $this->done();
        $properties = $this->accessibleProperties();
        // ⚠️ A booking belongs to ONE property. "All properties" is a roll-up for reading; with more than one to choose
        //    from, the wizard asks rather than quietly booking into the first.
        $chooseProperty = $done === null && $this->viewingAllProperties && count($properties) > 1;
        $property = $chooseProperty ? null : $this->currentProperty();

        $checkIn = $this->validDate(request()->query('check_in'));
        $checkOut = $this->validDate(request()->query('check_out'));
        $datesValid = $checkIn !== null && $checkOut !== null && $checkOut > $checkIn;

        $roomTypes = [];
        $nights = $datesValid ? (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut)) : 0;
        // ⚠️ The availability read answers nothing past its limit — which the page used to show as "nothing is free".
        $tooLong = $nights > UnitAvailabilityService::MAX_NIGHTS;
        if ($property !== null && $datesValid && ! $tooLong) {
            $roomTypes = array_map(
                fn (array $rt): array => $this->presentRoomType($rt, $checkIn, $checkOut),
                $availability->availableRoomTypes((string) $property->getKey(), $checkIn, $checkOut),
            );
            // what can be booked now first; a room type still waiting for a price after it (each list stays by name)
            usort($roomTypes, fn (array $a, array $b): int => [! $a['priced'], $a['name']] <=> [! $b['priced'], $b['name']]);
        }

        $currency = $property !== null ? strtoupper((string) ($property->currency ?: 'EUR')) : 'EUR';
        $query = $this->resolveQuery();
        $guestMatches = $query !== '' && $property !== null ? $this->searchGuests($query) : [];
        $extras = $property !== null ? $this->extrasFor((string) $property->getKey()) : [];
        // the currencies a stay here can be in — the property's, and each bookable room type's plan
        $currencies = array_values(array_unique(array_merge([$currency], array_column($roomTypes, 'currency'))));
        $taxes = $property !== null ? $this->taxesFor((string) $property->getKey(), $currencies) : [];
        $fees = $property !== null ? $this->feesFor((string) $property->getKey(), $currencies) : [];
        $restored = $property !== null ? $this->restored($roomTypes, $extras) : null;
        $canPay = (bool) auth()->user()?->can('payments.record');

        return view('livewire.operator.new-reservation', [
            'viewer' => $this->viewer(),
            'subtitle' => $property !== null ? (string) $property->name : '',
            'hasProperty' => $property !== null,
            'propertyId' => $property !== null ? (string) $property->getKey() : '',
            'properties' => $properties,
            'chooseProperty' => $chooseProperty,
            'rooms' => $property !== null ? $this->roomsAt((string) $property->getKey()) : ['all' => 0, 'bookable' => 0],
            'currency' => $currency,
            'symbol' => MoneyFormatter::symbol($currency),
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'lastNight' => $datesValid ? CarbonImmutable::parse($checkOut)->subDay()->toDateString() : null,
            'datesValid' => $datesValid,
            'datesLabel' => $datesValid ? $this->datesLabel($checkIn, $checkOut, $nights) : '',
            'nights' => $nights,
            'tooLong' => $tooLong,
            'maxNights' => UnitAvailabilityService::MAX_NIGHTS,
            'roomTypes' => $roomTypes,
            'canSetPrice' => (bool) auth()->user()?->can('rates.manage'),
            'query' => $query,
            'guestMatches' => $guestMatches,
            'canPay' => $canPay,
            'sources' => self::SOURCES,
            // #90 — REAL extras (org-owned AddOns) and REAL house-rules config.
            'extras' => $extras,
            'houseRules' => $property !== null ? $this->houseRules($property) : [],
            'restored' => $restored,
            'initialStep' => $restored['step'] ?? 1,
            'restrictionLines' => $this->restrictionLines(),
            // Everything the wizard's script reads, as data — one JSON block, never figures parsed back out of the page.
            'boot' => [
                'propertyId' => $property !== null ? (string) $property->getKey() : '',
                'checkIn' => (string) $checkIn,
                'checkOut' => (string) $checkOut,
                'datesValid' => $datesValid,
                'nights' => $nights,
                'currency' => $currency,
                'symbol' => MoneyFormatter::symbol($currency),
                'createUrl' => route('app.reservations.create'),
                'searched' => $query !== '',
                'canPay' => $canPay,
                'contactCleared' => (bool) session('new_reservation_contact_cleared'),
                'roomTypes' => $this->bootRoomTypes($roomTypes),
                'extras' => array_map(fn (array $e): array => ['id' => $e['id'], 'price' => $e['price_minor'], 'currency' => $e['currency'],
                    'taxable' => $e['taxable'], 'basis' => $e['basis']], $extras),
                'taxes' => $taxes,
                'fees' => $fees,
                'restore' => $restored,
            ],
            // GATE 1 · Part B — the in-wizard DONE screen after a successful create (PRG → ?created=<id>).
            'done' => $done,
        ]);
    }

    /**
     * The post-create DONE payload (the wireframe's success card), or null during the normal wizard. Loads the
     * just-created reservation TENANT-scoped (the global org scope guarantees isolation — a foreign/bogus id
     * returns null → the normal wizard). The self-check-in QR encodes the REAL, guard-protected guest-portal
     * check-in URL: a convenience deep-link (the guest still authenticates), never a fabricated code or a
     * check-in that bypasses the guest guard.
     *
     * @return array{ref: string, guest: string, qr: string, check_in_time: string|null, show_url: string, create_url: string, property: string}|null
     */
    private function done(): ?array
    {
        $id = request()->query('created');
        if (! is_string($id) || $id === '') {
            return null;
        }

        // ⚠️ Inside the person's properties only (the independent review): any id in the organisation showed its guest's
        //    name, reference and check-in code to staff limited to another property.
        $context = app(PropertyContext::class);
        $reservation = Reservation::query()->with(['property', 'leadGuest'])
            ->when($context->active(), fn (Builder $q) => $q->whereIn('property_id', $context->accessibleIds()))
            ->find($id);
        if ($reservation === null) {
            return null;
        }

        $property = $reservation->property;
        // ⚠️ A TIME IS A FACT OR NOTHING (D8-2). This fabricated '15:00' whenever the property had published
        // no check-in time — right for the demo properties (both 15:00) and silently wrong for any property
        // that opens at a different hour, which is the kind of invention that survives review (#39).
        // StayTime is the ONE derivation (§N.8f) and returns NULL when unset, so the panel OMITS the clause
        // rather than inventing an hour.
        $checkInTime = StayTime::display($property?->check_in_time, 'H:i');
        $guest = $reservation->leadGuest;
        $guestName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';

        return [
            'ref' => (string) $reservation->reference,
            'guest' => $guestName !== '' ? $guestName : 'the guest',   // NAME only (#28)
            'qr' => QrSvg::of(route('guest.checkin', $reservation->getKey()), 190),
            'check_in_time' => $checkInTime,
            'show_url' => route('app.reservations.show', $reservation->getKey()),
            'create_url' => route('app.reservations.create'),
            'property' => (string) ($property->name ?? ''),
        ];
    }

    /**
     * The properties this person may book into (the property_user allow-list, or the whole organisation) — the wizard's
     * own picker, because this focused screen has no app shell and so no top-bar switcher.
     *
     * With each one's bookable units, so a property with no rooms shows it before it is chosen.
     *
     * @return list<array{id: string, name: string, rooms: int, rooms_all: int}>
     */
    private function accessibleProperties(): array
    {
        $ids = app(PropertyContext::class)->accessibleIds();
        if ($ids === []) {
            return [];
        }

        $count = fn (Builder $q) => $q->whereIn('property_id', $ids)->selectRaw('property_id, COUNT(*) AS n')->groupBy('property_id')->pluck('n', 'property_id');
        $all = $count(Unit::query());
        $open = $count(Unit::query()->bookable());

        return Property::query()->whereIn('id', $ids)->orderBy('name')->get(['id', 'name'])
            ->map(fn (Property $p): array => ['id' => (string) $p->getKey(), 'name' => (string) $p->name,
                'rooms' => (int) ($open[(string) $p->getKey()] ?? 0), 'rooms_all' => (int) ($all[(string) $p->getKey()] ?? 0)])
            ->all();
    }

    /**
     * How many units the property has, and how many can take a booking — so an empty list can say WHY it is empty.
     *
     * @return array{all: int, bookable: int}
     */
    private function roomsAt(string $propertyId): array
    {
        return [
            'all' => Unit::query()->where('property_id', $propertyId)->count(),
            'bookable' => Unit::query()->bookable()->where('property_id', $propertyId)->count(),
        ];
    }

    /**
     * Present one available room type for the view — its real price over the stay, a night, and its free units.
     *
     * ⚠️ A UNIT'S OWN PRICE IS SHOWN WHERE ONE IS SET. The booking write charges a unit its per-unit price
     * ({@see NightlyRate::stayTotalForUnit()}) when it has one; the wizard used to show only the room type's price, so the
     * summary could state one figure and the folio charge another. The same function answers here, per plan.
     *
     * @param  array<string, mixed>  $rt
     * @return array<string, mixed>
     */
    private function presentRoomType(array $rt, string $checkIn, string $checkOut): array
    {
        $currency = (string) $rt['currency'];
        $planId = (string) ($rt['rate_plan_id'] ?? '');
        $nights = (int) $rt['nights'];
        $plans = array_map(fn (array $p): array => [
            'id' => (string) $p['id'],
            'name' => (string) $p['name'],
            'price' => $this->money((int) $p['price_minor'], (string) $p['currency']),
            'price_minor' => (int) $p['price_minor'],
        ], $rt['plans'] ?? []);

        $units = $rt['units'];
        $unitPrices = $rt['priced'] ? $this->unitPrices(array_column($plans, 'id'), array_column($units, 'id'), $checkIn, $checkOut) : [];
        foreach ($units as $i => $unit) {
            $own = $unitPrices[$planId][$unit['id']] ?? null;
            $units[$i]['own_price'] = $own !== null ? MoneyFormatter::display($own, $currency) : null;
        }

        $nightly = $rt['priced'] && $planId !== '' ? $this->nightlyRange($planId, $checkIn, $checkOut) : null;

        return [
            'room_type_id' => (string) $rt['room_type_id'],
            'name' => (string) $rt['room_type_name'],
            'priced' => (bool) $rt['priced'],
            'has_plan' => $planId !== '',
            // the plan a price set from here lands on — the room type's main plan, exactly as the Rates editor picks it
            'plan_name' => $planId !== '' ? (string) RatePlan::query()->whereKey($planId)->value('name') : '',
            'currency' => $currency,
            'symbol' => MoneyFormatter::symbol($currency),
            'price' => $rt['priced'] ? MoneyFormatter::display((int) $rt['price_minor'], $currency) : null,
            'price_minor' => $rt['priced'] ? (int) $rt['price_minor'] : 0,
            'nightly' => $nightly === null ? null
                : ($nightly[0] === $nightly[1]
                    ? MoneyFormatter::display($nightly[0], $currency).' a night'
                    : MoneyFormatter::display($nightly[0], $currency).'–'.MoneyFormatter::display($nightly[1], $currency).' a night'),
            'unpriced_nights' => ! $rt['priced'] && $planId !== '' ? $this->unpricedNights($planId, $checkIn, $checkOut, $nights) : $nights,
            'rate_plan_id' => $planId,
            // REAL active + priced rate plans for the selector (name + server-derived price). Never fabricated.
            'plans' => $plans,
            'unit_prices' => $unitPrices,
            'units' => $units,
            'unit_count' => count($units),
        ];
    }

    /**
     * Per-unit stay prices, keyed plan → unit, for the pairs that carry any per-unit price in the stay — one query to find
     * them, then the booking write's own function for each. A pair without one is absent: it costs the plan's price.
     *
     * @param  list<string>  $planIds
     * @param  list<string>  $unitIds
     * @return array<string, array<string, int>>
     */
    private function unitPrices(array $planIds, array $unitIds, string $checkIn, string $checkOut): array
    {
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

    /**
     * The lowest and highest nightly price of the plan over the stay — "€120.00 a night" when they agree, a range when
     * the nights differ. Read from the same rows the stay total sums.
     *
     * @return array{0: int, 1: int}|null
     */
    private function nightlyRange(string $planId, string $checkIn, string $checkOut): ?array
    {
        $row = RateCalendar::query()
            ->where('rate_plan_id', $planId)
            ->whereDate('date', '>=', $checkIn)
            ->whereDate('date', '<', $checkOut)
            ->selectRaw('MIN(price_minor) AS low, MAX(price_minor) AS high')
            ->first();

        if ($row === null || $row->getAttribute('low') === null) {
            return null;
        }

        return [(int) $row->getAttribute('low'), (int) $row->getAttribute('high')];
    }

    /** How many nights of the stay the plan has no price for. */
    private function unpricedNights(string $planId, string $checkIn, string $checkOut, int $nights): int
    {
        $priced = RateCalendar::query()
            ->where('rate_plan_id', $planId)
            ->whereDate('date', '>=', $checkIn)
            ->whereDate('date', '<', $checkOut)
            ->count();

        return max(0, $nights - $priced);
    }

    /**
     * The wizard as the operator left it when a booking came back refused — read from the flashed input, each value
     * checked against what this page offers NOW: a unit no longer free is dropped (and the wizard reopens at step 1), an
     * extra or plan that is not real here is dropped. ⚠️ Never a new guest's email or phone: the controller does not
     * flash them (#28) — {@see ReservationCreateController::back()}.
     *
     * @param  list<array<string, mixed>>  $roomTypes
     * @param  list<array<string, mixed>>  $extras
     * @return array<string, mixed>|null
     */
    private function restored(array $roomTypes, array $extras): ?array
    {
        $old = session()->getOldInput();
        if (! is_array($old) || $old === []) {
            return null;
        }

        $text = fn (string $key, int $max): string => is_string($old[$key] ?? null) ? mb_substr(trim($old[$key]), 0, $max) : '';
        $step = max(1, min(7, (int) session('new_reservation_step', 1)));

        $roomTypeId = $text('room_type_id', 40);
        $unitId = $text('unit_id', 40);
        $rt = collect($roomTypes)->firstWhere('room_type_id', $roomTypeId);
        if (! is_array($rt) || ! $rt['priced'] || ! collect($rt['units'])->contains('id', $unitId)) {
            [$roomTypeId, $unitId, $rt, $step] = ['', '', null, 1];
        }
        $planId = $text('rate_plan_id', 40);
        if ($rt === null || ! collect($rt['plans'])->contains('id', $planId)) {
            $planId = $rt !== null ? (string) $rt['rate_plan_id'] : '';
        }

        $existingId = $text('existing_guest_id', 40);
        $existingName = '';
        if ($existingId !== '') {
            $guest = Guest::query()->find($existingId);   // tenant-scoped
            $existingName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
            $existingId = $guest !== null ? $existingId : '';
        }

        $extraIds = array_column($extras, 'id');
        $method = $text('payment_method', 10);
        $send = $old['send_confirmation'] ?? '1';

        return [
            'step' => $step,
            'adults' => max(1, min(30, (int) ($old['adults'] ?? 2))),
            'children' => max(0, min(30, (int) ($old['children'] ?? 0))),
            'roomTypeId' => $roomTypeId,
            'unitId' => $unitId,
            'ratePlanId' => $planId,
            'guestMode' => $existingId !== '' ? 'existing' : 'new',
            'existingGuestId' => $existingId,
            'existingGuestName' => $existingName,
            'newFirst' => $text('guest_first_name', 80),
            'newLast' => $text('guest_last_name', 80),
            'country' => strtoupper($text('guest_country', 2)),
            'guestNote' => $text('guest_note', 2000),
            'vip' => ! empty($old['guest_vip']),
            'consent' => ! empty($old['guest_marketing_consent']),
            'extras' => array_values(array_intersect(array_filter((array) ($old['extras'] ?? []), 'is_string'), $extraIds)),
            'transferTrip' => ($old['transfer_trip'] ?? '') === 'return' ? 'return' : 'one',
            'applyCredit' => ! empty($old['apply_credit']),
            'arrivalWindow' => $text('arrival_window', 60),
            'rulesAgreed' => ! empty($old['rules_agreed']),
            'source' => in_array($old['source'] ?? '', array_column(self::SOURCES, 'value'), true) ? (string) $old['source'] : 'direct',
            'teamNote' => $text('team_note', 2000),
            'sendConfirmation' => $send !== '0',
            'payMethod' => in_array($method, ['cash', 'bank'], true) ? $method : 'later',
            'payAmount' => $text('payment_amount', 20),
        ];
    }

    private function datesLabel(string $checkIn, string $checkOut, int $nights): string
    {
        $in = CarbonImmutable::parse($checkIn);
        $out = CarbonImmutable::parse($checkOut);
        $span = $in->isSameYear($out) ? $in->format('j M').' → '.$out->format('j M Y') : $in->format('j M Y').' → '.$out->format('j M Y');

        return $span.' · '.$nights.' '.($nights === 1 ? 'night' : 'nights');
    }

    private function resolveQuery(): string
    {
        $param = request()->query('q');

        return is_string($param) ? mb_substr(trim($param), 0, self::MAX_QUERY) : '';
    }

    /**
     * The property's REAL active add-on extras (#90). The wizard renders these as toggles; a per-night / per-guest
     * / per-leg BASIS drives the client estimate, and the controller posts the real reconciling line.
     *
     * @return list<array{id: string, name: string, price_minor: int, currency: string, taxable: bool, basis: string, basis_label: string, is_transfer: bool}>
     */
    private function extrasFor(string $propertyId): array
    {
        return AddOn::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (AddOn $a): array => [
                'id' => (string) $a->getKey(),
                'name' => (string) $a->name,
                'price_minor' => (int) $a->price_minor,
                'currency' => (string) $a->currency,
                'taxable' => (bool) $a->taxable,
                'basis' => ReservationExtras::basisFor($a),
                'basis_label' => ReservationExtras::basisLabel($a),
                'is_transfer' => ReservationExtras::basisFor($a) === ReservationExtras::BASIS_PER_LEG,
            ])
            ->all();
    }

    /**
     * The property's REAL taxes (#90) for each currency a stay here can be in, filtered by the folio's own rule
     * ({@see StayEstimator::taxApplies()}): a tax in another currency, or a percentage already included in the price, is not
     * added. ⚠️ The estimate used to add every tax — an included VAT was counted twice — and leave out fees entirely
     * ({@see feesFor()}). ⚠️ Keyed by the STAY's currency (the plan's), which is what the folio posts by — not the property's.
     *
     * @param  list<string>  $currencies
     * @return array<string, list<array{name: string, mode: string, rate: float, amount: int}>>
     */
    private function taxesFor(string $propertyId, array $currencies): array
    {
        $taxes = Tax::query()->where('property_id', $propertyId)->get();
        $out = [];
        foreach ($currencies as $currency) {
            $out[$currency] = $taxes->filter(fn (Tax $t): bool => StayEstimator::taxApplies($t, $currency))
                ->map(fn (Tax $t): array => ['name' => (string) $t->name, 'mode' => (string) $t->mode, 'rate' => (float) $t->rate,
                    'amount' => (int) $t->amount_minor])
                ->values()->all();
        }

        return $out;
    }

    /**
     * The property's fees charged on every booking (a cleaning fee), per stay currency — the same filter the folio posts with
     * ({@see StayEstimator::feeApplies()}): an optional fee or one in another currency is not added.
     *
     * @param  list<string>  $currencies
     * @return array<string, list<array{name: string, mode: string, rate: float, amount: int}>>
     */
    private function feesFor(string $propertyId, array $currencies): array
    {
        $fees = Fee::query()->where('property_id', $propertyId)->get();
        $out = [];
        foreach ($currencies as $currency) {
            $out[$currency] = $fees->filter(fn (Fee $f): bool => StayEstimator::feeApplies($f, $currency))
                ->map(fn (Fee $f): array => ['name' => (string) $f->name, 'mode' => (string) $f->mode, 'rate' => (float) $f->rate,
                    'amount' => (int) $f->amount_minor])
                ->values()->all();
        }

        return $out;
    }

    /**
     * The bookable room types as the wizard's script reads them — keyed by id, units keyed by id, prices in minor units.
     *
     * @param  list<array<string, mixed>>  $roomTypes
     * @return array<string, array<string, mixed>>
     */
    private function bootRoomTypes(array $roomTypes): array
    {
        $out = [];
        foreach ($roomTypes as $rt) {
            if (! $rt['priced']) {
                continue;   // nothing to book until it has a price
            }
            $units = [];
            foreach ($rt['units'] as $unit) {
                $units[(string) $unit['id']] = ['code' => (string) $unit['code'], 'sleeps' => (int) $unit['sleeps']];
            }
            $out[(string) $rt['room_type_id']] = [
                'name' => (string) $rt['name'],
                'currency' => (string) $rt['currency'],
                'symbol' => (string) $rt['symbol'],
                'defaultPlan' => (string) $rt['rate_plan_id'],
                'price' => (int) $rt['price_minor'],
                'plans' => array_map(fn (array $p): array => ['id' => $p['id'], 'name' => $p['name'], 'price' => $p['price_minor']], $rt['plans']),
                'unitPrices' => (object) $rt['unit_prices'],
                'units' => (object) $units,
            ];
        }

        return $out;
    }

    /**
     * A refused booking's restrictions in plain words — "No arrivals on Wed 2 Sep", never `closed_to_arrival on 2026-09-02`.
     *
     * @return list<string>
     */
    private function restrictionLines(): array
    {
        $breaches = session('restriction_breaches');
        if (! is_array($breaches)) {
            return [];
        }

        $lines = [];
        foreach ($breaches as $b) {
            if (! is_array($b) || ! is_string($b['date'] ?? null)) {
                continue;
            }
            $date = CarbonImmutable::parse($b['date'])->format('D j M');
            $value = is_numeric($b['value'] ?? null) ? (int) $b['value'] : null;
            $nightsWord = $value === 1 ? 'night' : 'nights';
            $lines[] = match ((string) ($b['restriction'] ?? '')) {
                'min_stay' => 'Arriving '.$date.', a stay must be at least '.$value.' '.$nightsWord,
                'max_stay' => 'Arriving '.$date.', a stay can be at most '.$value.' '.$nightsWord,
                'closed_to_arrival' => 'No arrivals on '.$date,
                'closed_to_departure' => 'No departures on '.$date,
                'stop_sell' => $date.' is closed to new bookings',
                default => ucfirst(str_replace('_', ' ', (string) ($b['restriction'] ?? 'restriction'))).' on '.$date,
            };
        }

        return $lines;
    }

    /**
     * The property's REAL house rules (#90) — check-in/out times + the first policy's cancellation / pet /
     * smoking / max-stay. Honest defaults where the property hasn't configured a value (never invented).
     *
     * @return array<string, string|null>
     */
    private function houseRules(Property $property): array
    {
        $policy = $property->policies()->first();
        $cancellation = data_get($policy, 'cancellation');
        $cancellationLabel = is_array($cancellation)
            ? (string) ($cancellation['label'] ?? $cancellation['policy'] ?? 'As per booking terms')
            : 'As per booking terms';
        $pet = data_get($policy, 'pet');
        $smoking = data_get($policy, 'smoking');
        $minStay = data_get($policy, 'min_stay');

        return [
            // ⚠️ THE SAME INVENTION, TWICE OVER — the logged '15:00' and, one line below it, an identical
            // '11:00' for check-out. Both are now the real published time or NULL; the view renders the
            // panel's own established absence idiom ('—', already used for min_stay) rather than an hour
            // nobody published. ⚠️ StayTime also parses properly: `substr()` on a cast attribute is not a
            // safe way to read a time.
            'check_in' => StayTime::display($property->check_in_time, 'H:i'),
            'check_out' => StayTime::display($property->check_out_time, 'H:i'),
            'pet' => is_string($pet) && $pet !== '' ? $pet : 'Not permitted',
            'smoking' => is_string($smoking) && $smoking !== '' ? $smoking : 'Not permitted',
            'cancellation' => $cancellationLabel,
            'min_stay' => is_numeric($minStay) ? (int) $minStay.' night min' : '—',
        ];
    }

    /**
     * Existing guests matching the typed name — NAME ONLY (GUARD 2). Searches the two plaintext name columns
     * via CONCAT_WS (so "Ada", "Lovelace" and "Ada Lovelace" all match); email/phone are encrypted and are
     * neither searched nor returned. Anonymized guests (names nulled) simply never match.
     *
     * ⚠️ With the guest's stay history ({@see GuestHistory} — the CRM's own rule), never a contact detail: two guests
     * of the same name were otherwise identical rows with nothing to choose between them.
     *
     * @return list<array{id: string, name: string, initials: string, history: string}>
     */
    private function searchGuests(string $query): array
    {
        $term = '%'.addcslashes($query, '%_\\').'%';

        return Guest::query()
            ->where(fn (Builder $q) => $q->whereRaw("CONCAT_WS(' ', first_name, last_name) LIKE ?", [$term]))
            ->with(['reservations' => fn ($q) => $q->select(['id', 'lead_guest_id', 'status', 'check_in', 'check_out'])])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::GUEST_LIMIT)
            ->get()
            ->map(function (Guest $guest): array {
                $name = trim(((string) $guest->first_name).' '.((string) $guest->last_name));
                $name = $name !== '' ? $name : 'Guest';

                return ['id' => (string) $guest->getKey(), 'name' => $name, 'initials' => $this->initials($name),
                    'history' => GuestHistory::for($guest->reservations)['stat'] ?? 'No stays yet'];
            })
            ->all();
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y) ? $value : null;
    }
}
