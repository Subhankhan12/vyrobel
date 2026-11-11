<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\RateActionController;
use App\Http\Controllers\Operator\ReservationCreateController;
use App\Http\Controllers\Operator\SeasonActionController;
use App\Livewire\Concerns\ResolvesCurrentProperty;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\AddOn;
use App\Models\Availability;
use App\Models\Fee;
use App\Models\Guest;
use App\Models\Property;
use App\Models\PropertyPolicy;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitRateOverride;
use App\Services\AssignmentService;
use App\Services\SeasonalRateService;
use App\Services\UnitAvailabilityService;
use App\Support\Countries;
use App\Support\Email\EmailTemplateResolver;
use App\Support\Folio\StayEstimator;
use App\Support\Guest\ArrivalPack;
use App\Support\Guest\GuestHistory;
use App\Support\I18n\LocalDate;
use App\Support\Mail\MailSafetyGuard;
use App\Support\Money\MoneyFormatter;
use App\Support\Pricing\NightlyRate;
use App\Support\Property\BookableProperties;
use App\Support\Property\PropertyContext;
use App\Support\QrSvg;
use App\Support\Rates\SavedSeasonOffer;
use App\Support\Rates\SeasonApplyFacts;
use App\Support\Reservations\CancellationPolicy;
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

    /** The steps, by number — the ones a refused booking reopens at ({@see ReservationCreateController::back()}). */
    public const STEP_STAY = 1;

    public const STEP_GUEST = 2;

    public const STEP_EXTRAS = 3;

    public const STEP_CONFIRM = 4;

    /**
     * ⚠️ 2026-10-04 (his second report: "more … minimal and operational") — SEVEN steps became FOUR. Two of the seven were nearly
     * empty for most properties (a plan line and "no extras set up"; one optional arrival box): the plan now sits with the unit,
     * the arrival time with the guest, the house rules with the confirmation. Extras is asked only where the property has some.
     *
     * @return array<int, string> step number => its name
     */
    private static function steps(bool $hasExtras): array
    {
        // translated here, at render time (steps() is called from render()); Extras only where the property has some
        $steps = [self::STEP_STAY => __('php/livewire/operator/new_reservation.step_stay'), self::STEP_GUEST => __('php/livewire/operator/new_reservation.step_guest')];
        if ($hasExtras) {
            $steps[self::STEP_EXTRAS] = __('php/livewire/operator/new_reservation.step_extras');
        }
        $steps[self::STEP_CONFIRM] = __('php/livewire/operator/new_reservation.step_confirm');

        return $steps;
    }

    /** The booking sources an operator may choose here — the controller accepts exactly these (their words: {@see sources()}). */
    private const SOURCES = ['direct', 'walk_in', 'corporate', 'agent'];

    /**
     * The booking sources with their words, translated at render time.
     *
     * @return list<array{value: string, label: string}>
     */
    private function sources(): array
    {
        return array_map(fn (string $value): array => ['value' => $value, 'label' => match ($value) {
            'direct' => __('php/livewire/operator/new_reservation.source_direct'),
            'walk_in' => __('php/livewire/operator/new_reservation.source_walk_in'),
            'corporate' => __('php/livewire/operator/new_reservation.source_corporate'),
            'agent' => __('php/livewire/operator/new_reservation.source_agent'),
        }], self::SOURCES);
    }

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
        // The property's own today — the calendar's first bookable night and its "today" (guest-facing day logic, its timezone).
        $today = CarbonImmutable::now((string) ($property?->timezone ?: config('app.timezone')))->toDateString();
        $steps = self::steps($extras !== []);
        // a refused booking reopens at its step — at Confirm when that step is one this property does not ask (Extras)
        $initialStep = (int) ($restored['step'] ?? self::STEP_STAY);
        $initialStep = isset($steps[$initialStep]) ? $initialStep : self::STEP_CONFIRM;
        $rooms = $property !== null ? $this->roomsAt((string) $property->getKey()) : ['all' => 0, 'bookable' => 0];
        // ⚠️ Read only where the steps render — not on the done screen, nor on a property with no rooms (the review, M5).
        $nightly = $property !== null && $done === null && $rooms['bookable'] > 0 ? $this->nightlyCalendar($property, $today) : (object) [];
        $mailSends = MailSafetyGuard::sendingNow();   // the one question (shared with the Guests page since 2026-10-05)
        $canSetPrice = (bool) auth()->user()?->can('rates.manage');

        return view('livewire.operator.new-reservation', [
            'viewer' => $this->viewer(),
            'subtitle' => $property !== null ? (string) $property->name : '',
            'hasProperty' => $property !== null,
            'propertyId' => $property !== null ? (string) $property->getKey() : '',
            'properties' => $properties,
            // ⚠️⚠️ 2026-10-04 (his second report) — a property with no rooms named only "choose it at the top", a small picker
            //    he did not see: the page was a dead end while his other property had rooms. The ones that can take a booking
            //    are offered as buttons in the card itself.
            //    (The card shows only when THIS property has no bookable room, so it is never among them.)
            'bookableElsewhere' => array_values(array_filter($properties, fn (array $p): bool => $p['rooms'] > 0)),
            'chooseProperty' => $chooseProperty,
            'rooms' => $rooms,
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
            'canSetPrice' => $canSetPrice,
            'query' => $query,
            'guestMatches' => $guestMatches,
            'canPay' => $canPay,
            'sources' => $this->sources(),
            // #90 — REAL extras (org-owned AddOns) and REAL house-rules config.
            'extras' => $extras,
            'houseRules' => $property !== null ? $this->houseRules($property, $datesValid ? $checkIn : null) : [],
            'canEditRules' => (bool) auth()->user()?->can('properties.manage'),
            'arrivalNote' => $this->arrivalNote($property, $datesValid ? $checkIn : null, $mailSends),
            // ⚠️ whether email reaches a real inbox from here at all — on production it does not yet (the mailer is `log`)
            'mailSends' => $mailSends,
            'countries' => Countries::everywhere(),
            'steps' => $steps,
            'restored' => $restored,
            'initialStep' => $initialStep,
            'hasNightly' => is_array($nightly) && $nightly !== [],
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
                // D-NR2-NEXT-NOPRICE: room types are on offer but NONE has a price for these dates — Next must say so, not "pick a unit"
                'waitingForPrice' => $roomTypes !== [] && ! collect($roomTypes)->contains('priced', true),
                'createUrl' => route('app.reservations.create'),
                'guestMatchUrl' => route('app.reservations.guest-match'),   // DEC-r: names only, a POST
                'searched' => $query !== '',
                'canPay' => $canPay,
                'mailSends' => $mailSends,
                'contactCleared' => (bool) session('new_reservation_contact_cleared'),
                'roomTypes' => $this->bootRoomTypes($roomTypes),
                'extras' => array_map(fn (array $e): array => ['id' => $e['id'], 'price' => $e['price_minor'], 'currency' => $e['currency'],
                    'taxable' => $e['taxable'], 'basis' => $e['basis']], $extras),
                'taxes' => $taxes,
                'fees' => $fees,
                'restore' => $restored,
                // the steps this property asks (Extras only where it has some) — the script walks these, in order
                'steps' => array_keys($steps),
                'today' => $today,
                'nightly' => $nightly,
                // whether each listed returning guest has an email — never the address (#28). ⚠️ And the one a refused booking
                //    brings back, which is not in a search (the review, H1: a restored returning guest lost the confirmation).
                'guestEmails' => (object) (array_column($guestMatches, 'has_email', 'id')
                    + (($restored['existingGuestId'] ?? '') !== '' ? [$restored['existingGuestId'] => (bool) ($restored['existingGuestHasEmail'] ?? false)] : [])),
                // the "Apply this season?" dialog's facts, for whoever may apply one (SeasonApplyFacts)
                'seasons' => (object) ($canSetPrice ? collect($roomTypes)->flatMap(fn (array $rt): array => $rt['seasons'])->keyBy('id')->all() : []),
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
     * @return array{ref: string, guest: string, qr: string|null, can_sign_in: bool, guest_url: string|null, check_in_time: string|null, show_url: string, create_url: string, property: string}|null
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

        // ⚠️ 2026-10-04 — the QR opens the guest portal's check-in, and the guest portal is claimed BY EMAIL
        //    ({@see \App\Http\Controllers\Guest\GuestClaimController}): for a guest with no address the code led nowhere. Only
        //    whether there is one is read here — the address itself is never put on the page (#28).
        $canSignIn = $guest !== null && trim((string) $guest->email) !== '';

        return [
            'ref' => (string) $reservation->reference,
            'guest' => $guestName !== '' ? $guestName : __('php/livewire/operator/new_reservation.the_guest'),   // NAME only (#28)
            'qr' => $canSignIn ? QrSvg::of(route('guest.checkin', $reservation->getKey()), 190) : null,
            'can_sign_in' => $canSignIn,
            // where an email is added — the guest's profile (contact details live only on the Guests pages, #28), for whoever may open it
            'guest_url' => ! $canSignIn && $guest !== null && (bool) auth()->user()?->can('guests.manage') ? route('app.guests.show', $guest->getKey()) : null,
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
        return BookableProperties::all();   // ONE list, shared with the Calendar's dialog and its empty state
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
                    ? __('php/livewire/operator/new_reservation.a_night', ['amount' => MoneyFormatter::display($nightly[0], $currency)])
                    : __('php/livewire/operator/new_reservation.a_night_range', ['low' => MoneyFormatter::display($nightly[0], $currency), 'high' => MoneyFormatter::display($nightly[1], $currency)])),
            'unpriced_nights' => ! $rt['priced'] && $planId !== '' ? $this->unpricedNights($planId, $checkIn, $checkOut, $nights) : $nights,
            // ⚠️⚠️ 2026-10-04 — a SAVED season that was never applied: on production his €200 "Winter" sat on exactly this plan
            //    while the page said "No price for these nights yet" (a season writes nothing until Apply).
            'seasons' => ! $rt['priced'] && $planId !== '' ? $this->savedSeasons($planId, $checkIn, $checkOut) : [],
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
     * The plan's SAVED, NOT-YET-APPLIED seasons that would price at least one of the stay's nights that has no price — each
     * with everything the Rates page's "Apply this season?" dialog says ({@see SeasonApplyFacts}, straight from
     * {@see SeasonalRateService::preview()}): applying goes through that same dialog here, and the same Apply
     * ({@see SeasonActionController::apply()} — `can:rates.manage`, the property limit, the paused guard, R-SEASON's
     * precedence order; none of it repeated here). An applied season needs no offer: its nights already carry the price.
     *
     * ⚠️ The independent review (2026-10-04): a season that only overlapped the stay was offered with "so these nights have
     * no price" even when the nights it covers were priced — applying it would change a price and fix nothing. Only a season
     * that covers a night still WITHOUT a price is offered, saying how many of them it would price.
     *
     * @return list<array<string, mixed>>
     */
    private function savedSeasons(string $planId, string $checkIn, string $checkOut): array
    {
        return SavedSeasonOffer::forStay($planId, $checkIn, $checkOut);   // ONE rule, shared with the Calendar's dialog
    }

    /**
     * The calendar's nights, from the property's today for a year: the lowest price a night across the room types that still
     * have a room to sell (`p`, minor units, NULL when none of them has a price that night) and how many rooms are left (`f`).
     * ⚠️ The same two sources a booking is checked against — the availability count (Level A) and each room type's MAIN plan
     * ({@see RoomType::primaryRatePlan()}, the one the stay is priced on). A night with no availability row at all is left out:
     * nothing is claimed about it. Plans in another currency than the property's are not mixed into its figure.
     *
     * @return array<string, array{p: int|null, f: int}>|object
     */
    private function nightlyCalendar(Property $property, string $today): array|object
    {
        $until = CarbonImmutable::parse($today)->addYear()->toDateString();
        $currency = strtoupper((string) ($property->currency ?: 'EUR'));
        $roomTypes = RoomType::query()->where('property_id', $property->getKey())
            ->with(['defaultRatePlan', 'ratePlans' => fn ($q) => $q->where('is_active', true)->orderBy('created_at')])->get();
        if ($roomTypes->isEmpty()) {
            return (object) [];
        }

        $planOf = [];
        foreach ($roomTypes as $rt) {
            $plan = $rt->primaryRatePlan();
            if ($plan !== null && strtoupper((string) $plan->currency) === $currency) {
                $planOf[(string) $rt->getKey()] = (string) $plan->getKey();
            }
        }

        // ⚠️ Plain rows and a plain date range (the review, M5): `whereDate()` wraps the column, so the (room type, date) index
        //    could not range-scan and every row of those room types was read, history included; and a model per row cost
        //    ~260 ms at 20 room types. Rooms left is still the ONE formula ({@see Availability::left()}).
        $prices = [];
        if ($planOf !== []) {
            foreach (RateCalendar::query()->whereIn('rate_plan_id', array_values($planOf))->where('date', '>=', $today)
                ->where('date', '<', $until)->toBase()->get(['rate_plan_id', 'date', 'price_minor']) as $row) {
                $prices[(string) $row->rate_plan_id][substr((string) $row->date, 0, 10)] = (int) $row->price_minor;
            }
        }

        $nights = [];
        foreach (Availability::query()->whereIn('room_type_id', $roomTypes->modelKeys())->where('date', '>=', $today)
            ->where('date', '<', $until)->toBase()->get(['room_type_id', 'date', 'total', 'sold', 'out_of_order', 'blocked']) as $row) {
            $date = substr((string) $row->date, 0, 10);
            $left = Availability::left((int) $row->total, (int) $row->sold, (int) $row->out_of_order, (int) $row->blocked);
            $nights[$date] ??= ['p' => null, 'f' => 0];
            $nights[$date]['f'] += $left;
            $price = $prices[$planOf[(string) $row->room_type_id] ?? ''][$date] ?? null;
            if ($left > 0 && $price !== null && ($nights[$date]['p'] === null || $price < $nights[$date]['p'])) {
                $nights[$date]['p'] = $price;
            }
        }
        ksort($nights);

        return $nights !== [] ? $nights : (object) [];
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
        $step = max(self::STEP_STAY, min(self::STEP_CONFIRM, (int) session('new_reservation_step', self::STEP_STAY)));

        $roomTypeId = $text('room_type_id', 40);
        $unitId = $text('unit_id', 40);
        $rt = collect($roomTypes)->firstWhere('room_type_id', $roomTypeId);
        if (! is_array($rt) || ! $rt['priced'] || ! collect($rt['units'])->contains('id', $unitId)) {
            [$roomTypeId, $unitId, $rt, $step] = ['', '', null, self::STEP_STAY];
        }
        $planId = $text('rate_plan_id', 40);
        if ($rt === null || ! collect($rt['plans'])->contains('id', $planId)) {
            $planId = $rt !== null ? (string) $rt['rate_plan_id'] : '';
        }

        $existingId = $text('existing_guest_id', 40);
        $existingName = '';
        $existingHasEmail = false;
        if ($existingId !== '') {
            $guest = Guest::query()->find($existingId);   // tenant-scoped
            $existingName = $guest !== null ? trim(((string) $guest->first_name).' '.((string) $guest->last_name)) : '';
            $existingHasEmail = $guest !== null && trim((string) $guest->email) !== '';   // a yes or no — never the address (#28)
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
            'source' => in_array($old['source'] ?? '', self::SOURCES, true) ? (string) $old['source'] : 'direct',
            'teamNote' => $text('team_note', 2000),
            // ⚠️ A "0" sent while the box could not be ticked (no address yet) was not a choice — it starts ticked again once
            //    there is one (the review). A new guest's typed contact is never echoed (#28); the flag says one was typed.
            'sendConfirmation' => $send !== '0' || ! ($existingId !== '' ? $existingHasEmail : (bool) session('new_reservation_contact_cleared')),
            'existingGuestHasEmail' => $existingHasEmail,
            'payMethod' => in_array($method, ['cash', 'bank'], true) ? $method : 'later',
            'payAmount' => $text('payment_amount', 20),
        ];
    }

    private function datesLabel(string $checkIn, string $checkOut, int $nights): string
    {
        $in = CarbonImmutable::parse($checkIn);
        $out = CarbonImmutable::parse($checkOut);
        $from = $in->isSameYear($out) ? $in->translatedFormat(LocalDate::pattern('j M')) : $in->translatedFormat(LocalDate::pattern('j M Y'));

        return trans_choice('php/livewire/operator/new_reservation.dates_label', $nights, ['from' => $from, 'to' => $out->translatedFormat(LocalDate::pattern('j M Y')), 'count' => $nights]);
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
            $date = CarbonImmutable::parse($b['date'])->translatedFormat(LocalDate::pattern('D j M'));
            $value = is_numeric($b['value'] ?? null) ? (int) $b['value'] : null;
            // a missing value printed as nothing before ("at least  nights") — still so
            $count = ['date' => $date, 'count' => $value ?? ''];
            $lines[] = match ((string) ($b['restriction'] ?? '')) {
                'min_stay' => trans_choice('php/livewire/operator/new_reservation.restrict_min_stay', $value ?? 0, $count),
                'max_stay' => trans_choice('php/livewire/operator/new_reservation.restrict_max_stay', $value ?? 0, $count),
                'closed_to_arrival' => __('php/livewire/operator/new_reservation.restrict_no_arrivals', ['date' => $date]),
                'closed_to_departure' => __('php/livewire/operator/new_reservation.restrict_no_departures', ['date' => $date]),
                'stop_sell' => __('php/livewire/operator/new_reservation.restrict_stop_sell', ['date' => $date]),
                default => __('php/livewire/operator/new_reservation.restrict_other', ['restriction' => ucfirst(str_replace('_', ' ', (string) ($b['restriction'] ?? 'restriction'))), 'date' => $date]),
            };
        }

        return $lines;
    }

    /**
     * How the guest gets directions and the door code — said from the real conditions (the review, M3): the pre-arrival email
     * (the `emails:send-pre-arrival` command) goes {@see ArrivalPack::LEAD_DAYS} days before check-in, on the
     * app's day, only to a guest with an address, only while the organisation has it switched on (#86), and only if email
     * reaches anyone at all — on production it does not yet. ⚠️ It used to say "The confirmation includes directions and
     * parking"; the confirmation says they come later.
     */
    private function arrivalNote(?Property $property, ?string $checkIn, bool $mailSends): string
    {
        $lead = ArrivalPack::LEAD_DAYS;

        return match (true) {
            ! $mailSends => __('php/livewire/operator/new_reservation.arrival_mail_off'),
            $property !== null && ! EmailTemplateResolver::enabled((string) $property->organization_id, 'pre-arrival') => __('php/livewire/operator/new_reservation.arrival_pre_arrival_off'),
            $checkIn !== null && $checkIn < CarbonImmutable::today()->addDays($lead)->toDateString() => trans_choice('php/livewire/operator/new_reservation.arrival_too_soon', $lead, ['count' => $lead]),
            default => trans_choice('php/livewire/operator/new_reservation.arrival_by_email', $lead, ['count' => $lead]),
        };
    }

    /**
     * The property's REAL house rules (#90) in words — check-in/out times and its policy's pets, smoking, minimum stay and
     * cancellation. NULL where the property has set nothing: the view says "Not set", never a rule nobody chose.
     *
     * ⚠️⚠️ 2026-10-04 (the second pass) — "Not permitted" was printed for pets and smoking on a property that had set NEITHER
     * (his production property has no policy at all), the same invention this method's times had already been cured of; on a
     * property that HAD set them, the stored codes themselves ("on_request", "not_allowed"); and "As per booking terms" over a
     * real cancellation policy, whose shape (`type`, `free_until_days`) it never read. The words are the Settings form's own
     * ({@see PropertyPolicy::ruleLabel()}); the free-cancellation date is the ONE reading every booking surface uses
     * ({@see CancellationPolicy::freeUntilForCheckIn()}).
     *
     * @return array<string, string|null>
     */
    private function houseRules(Property $property, ?string $checkIn): array
    {
        $policy = $property->policies()->first();
        $cancellation = data_get($policy, 'cancellation');
        $freeDays = is_array($cancellation) && is_numeric($cancellation['free_until_days'] ?? null) ? (int) $cancellation['free_until_days'] : null;
        $freeUntil = $checkIn !== null ? CancellationPolicy::freeUntilForCheckIn((string) $property->getKey(), $checkIn) : null;
        $minStay = data_get($policy, 'min_stay');

        return [
            // ⚠️ THE SAME INVENTION, TWICE OVER — the logged '15:00' and, one line below it, an identical
            // '11:00' for check-out. Both are now the real published time or NULL. ⚠️ StayTime also parses
            // properly: `substr()` on a cast attribute is not a safe way to read a time.
            'check_in' => StayTime::display($property->check_in_time, 'H:i'),
            'check_out' => StayTime::display($property->check_out_time, 'H:i'),
            'pet' => PropertyPolicy::ruleLabel(data_get($policy, 'pet')),
            'smoking' => PropertyPolicy::ruleLabel(data_get($policy, 'smoking')),
            'cancellation' => match (true) {
                // ⚠️ the review: "Free until 30 Sep" was shown on 4 Oct for a stay that close, and "until 0 days before"
                $freeUntil !== null && $freeUntil->toDateString() < CarbonImmutable::now((string) ($property->timezone ?: config('app.timezone')))->toDateString() => __('php/livewire/operator/new_reservation.cancel_not_free', ['date' => $freeUntil->translatedFormat(LocalDate::pattern('j M'))]),
                $freeDays === 0 => __('php/livewire/operator/new_reservation.cancel_free_until_check_in'),
                $freeUntil !== null => __('php/livewire/operator/new_reservation.cancel_free_until_date', ['date' => $freeUntil->translatedFormat(LocalDate::pattern('j M'))]),
                $freeDays !== null => trans_choice('php/livewire/operator/new_reservation.cancel_free_until_days', $freeDays, ['count' => $freeDays]),
                is_array($cancellation) && is_string($cancellation['type'] ?? null) && $cancellation['type'] !== '' => ucfirst($cancellation['type']),
                default => null,
            },
            'min_stay' => match (true) {
                ! is_numeric($minStay) => null,
                (int) $minStay <= 1 => __('php/livewire/operator/new_reservation.min_stay_none'),
                default => trans_choice('php/livewire/operator/new_reservation.min_stay_nights', (int) $minStay, ['count' => (int) $minStay]),
            },
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
     * @return list<array{id: string, name: string, initials: string, history: string, has_email: bool}>
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
                $name = $name !== '' ? $name : __('php/livewire/operator/new_reservation.guest_no_name');

                return ['id' => (string) $guest->getKey(), 'name' => $name, 'initials' => $this->initials($name),
                    'history' => GuestHistory::for($guest->reservations)['stat'] ?? __('php/livewire/operator/new_reservation.no_stays_yet'),
                    // whether a confirmation can go to them — never the address (#28)
                    'has_email' => trim((string) $guest->email) !== ''];
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
