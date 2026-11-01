<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\ResolvePublicProperty;
use App\Models\BookingQuote;
use App\Models\Fee;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Tax;
use App\Payments\Stripe\StripeClientInterface;
use App\Payments\Stripe\StripeIdempotency;
use App\Services\BookingEngineService;
use App\Services\FolioService;
use App\Services\GuestWalletService;
use App\Services\OperatorConnectService;
use App\Services\UnitAvailabilityService;
use App\Support\Folio\FolioTotals;
use App\Support\Folio\StayEstimator;
use App\Support\I18n\LocalDate;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Pricing\NightlyRate;
use App\Support\Reservations\CancellationPolicy;
use App\Support\Reviews\ReviewList;
use App\Support\Reviews\ReviewRating;
use App\Support\Storefront\StorefrontSeo;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Public booking engine (Step 7) — JSON endpoints + one bare, UNSTYLED contract-stub landing.
 * The tenant/property context is already set by {@see ResolvePublicProperty}; every action re-loads
 * the property TENANT-SCOPED from the stashed id, so nothing here runs unscoped and property A can
 * never reach property B. Styled UI is the deferred design pass — this documents the data contract.
 */
class BookingEngineController extends Controller
{
    public function __construct(
        private readonly BookingEngineService $engine,
        private readonly FolioService $folios,
        // STOREFRONT SEO (D10) — presentation only. It reads the property, its room types and the certified
        // engine probe to build <head> output; it touches no quote, no availability and no reserve path.
        private readonly StorefrontSeo $seo,
    ) {}

    /**
     * PUBLIC storefront — Browse / Search + sellable-room results (design pass · Booking Screen 1).
     * Server-rendered, READ-ONLY, no auth. Context is already set by {@see ResolvePublicProperty}; the
     * property is re-loaded TENANT-SCOPED, and search runs property-scoped through the certified
     * {@see BookingEngineService::search} (availability via AvailabilityService, real prices). A visitor
     * for property X can only ever see property X's inventory — no other property, no operator data.
     * This screen NEVER writes inventory (booking/checkout is a later screen).
     */
    public function browse(Request $request): View
    {
        $property = $this->property($request);

        $adults = max(1, (int) $request->query('adults', '2'));
        $children = max(0, (int) $request->query('children', '0'));
        $checkIn = $this->validDate($request->query('check_in'));
        $checkOut = $this->validDate($request->query('check_out'));
        $searched = $checkIn !== null && $checkOut !== null && $checkOut > $checkIn;

        // Refine controls (real filters over the real offer set — never fabricated).
        $bedrooms = is_numeric($request->query('bedrooms')) ? (int) $request->query('bedrooms') : null;
        $maxPrice = is_numeric($request->query('max_price')) ? (int) $request->query('max_price') : null;
        $sort = in_array($request->query('sort'), ['rating', 'price_asc', 'price_desc'], true) ? (string) $request->query('sort') : 'recommended';
        $amenityFilter = array_values(array_filter(array_map('strval', (array) $request->query('amenities', [])), fn ($a): bool => $a !== ''));

        // All room types (+ photos + amenities) once — cards, feature chips, filter options, no N+1.
        $roomTypes = RoomType::query()
            ->where('property_id', $property->getKey())
            ->with(['photos', 'amenities'])
            ->get()
            ->keyBy(fn (RoomType $rt): string => (string) $rt->getKey());

        // The property's taxes + fees, once — for the RECONCILING grand total (#17: shown total incl. the
        // real cleaning fee === the folio total, never a hardcoded €45).
        $taxes = Tax::query()->where('property_id', $property->getKey())->get();
        $fees = Fee::query()->where('property_id', $property->getKey())->get();
        $persons = max(1, $adults + $children);

        $rooms = [];
        $nights = 0;
        if ($searched) {
            $nights = (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
            foreach ($this->engine->search($property, $checkIn, $checkOut, $adults, $children) as $offer) {
                /** @var RoomType|null $rt */
                $rt = $roomTypes->get((string) $offer['room_type_id']);
                $card = $this->roomOffer($offer);
                $card['cover'] = $rt?->coverPhoto()?->url;
                $card['rating'] = ReviewRating::forRoomType((string) $offer['room_type_id']);   // real derived rating
                $card['bedrooms'] = $rt?->bedrooms !== null ? (int) $rt->bedrooms : null;
                $card['features'] = $this->roomFeatures($rt);
                $card['amenities'] = $rt !== null ? $rt->amenities->pluck('name')->map('strval')->all() : [];
                [$card['grand_total'], $card['grand_total_minor'], $card['fees_note'], $card['nightly_note']] = $this->grandTotal($taxes, $fees, $offer, $persons);
                $rooms[] = $card;
            }
            $rooms = $this->applyRefine($rooms, $bedrooms, $amenityFilter, $maxPrice, $sort);
        }

        // Real filter options (present bedroom counts + amenities across the property's rooms — never invented).
        $bedroomOptions = $roomTypes->pluck('bedrooms')->filter(fn ($b): bool => $b !== null)->map(fn ($b): int => (int) $b)->unique()->sort()->values()->all();
        $amenityOptions = $roomTypes->flatMap(fn (RoomType $rt): array => $rt->amenities->pluck('name')->all())->map('strval')->unique()->sort()->values()->all();

        return view('booking.browse', [
            // STOREFRONT SEO (D10) — an additive presentation key. Nothing above it changed.
            'seo' => $this->seo->forProperty(
                $property,
                $roomTypes->map(fn (RoomType $rt): string => (string) $rt->name)->values()->all(),
                $this->firstPhotoUrl($roomTypes),
            ),
            // ⚠️ `currency_symbol` — the price-filter slider printed a hardcoded '€' (twice: once server-side and
            // once in its `oninput` JS), so a GBP property's storefront offered to filter by euros while every
            // room card beside it priced in pounds. The property's own currency is the authority.
            'property' => ['name' => (string) $property->name, 'type' => $this->propertyType((string) $property->type), 'slug' => (string) $property->slug, 'currency_symbol' => MoneyFormatter::symbol((string) $property->currency)],
            'search' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
                'searched' => $searched,
                'nights' => $nights,
            ],
            'refine' => ['bedrooms' => $bedrooms, 'max_price' => $maxPrice, 'sort' => $sort, 'amenities' => $amenityFilter],
            'filterOptions' => ['bedrooms' => $bedroomOptions, 'amenities' => $amenityOptions, 'max_price_ceiling' => $this->maxPriceCeiling($rooms)],
            'rooms' => $rooms,
            // Signed-in guest chrome (wishlist/account) is honest-gated — anonymous visitors get a sign-in affordance.
            'guest' => Auth::guard('guest')->check() ? ['name' => (string) (Auth::guard('guest')->user()->first_name ?? __('php/http/controllers/booking_engine_controller.guest_fallback_name'))] : null,
            // Teaser (no valid search yet): real room-type names only — NO availability/price claim.
            'teaser' => $searched ? [] : $this->teaserRooms($property, $roomTypes->mapWithKeys(fn (RoomType $rt): array => [(string) $rt->getKey() => $rt->coverPhoto()?->url])->all()),
        ]);
    }

    /**
     * The RECONCILING grand total for one offer = room + the property's real non-optional fees + charged
     * taxes, via the SAME {@see StayEstimator} the operator + the folio use (#17 to the cent). Also returns a
     * short "incl. …" enumeration of what the total folds in, and the nightly's basis cue (F3).
     *
     * @param  Collection<int, Tax>  $taxes
     * @param  Collection<int, Fee>  $fees
     * @param  array<string, mixed>  $offer
     * @return array{0: string, 1: int, 2: string, 3: string}
     */
    private function grandTotal(Collection $taxes, Collection $fees, array $offer, int $persons): array
    {
        $currency = (string) $offer['currency'];
        $room = Money::of((int) $offer['total_minor'], $currency);
        $nights = max(1, (int) $offer['nights']);

        $estimate = StayEstimator::estimate($taxes, $fees, $room, $nights, $persons, $currency);
        $totalMinor = $estimate['total']->amountMinor();

        // "incl. €58.65 VAT · €15.00 City tax · €45.00 Cleaning fee" — ENUMERATE what the total folds in (F2).
        //
        // ⚠️ THE OLD NOTE NAMED ONLY THE CLEANING FEE, and the guest deep-parity audit filed that: a reader
        // saw "€85.00 / night" beside "€373.65 total · incl. €45.00 cleaning" and could not account for the
        // remaining €73.65 (VAT + city tax). Nothing was hidden — the total was always correct and shown
        // before reserve — but the label under-stated what it was "incl." of.
        //
        // ⚠️ BUILT FROM THE ESTIMATE'S OWN LINES, NOT RE-SUMMED. These are the same lines the pre-reserve
        // checkout breakdown and the folio use, so the card cannot drift from them (§N.8f) — the previous
        // version recomputed the fees separately, which is precisely how two surfaces start to disagree.
        //
        // ⚠️ INFORMATIONAL LINES ARE SKIPPED, and that is load-bearing. A percentage tax marked `inclusive`
        // is NOT charged ({@see StayEstimator::taxApplies}) — it is already inside the room price and is not
        // part of this total. Listing it after "incl." would make the enumeration fail to sum to the total.
        $parts = [];
        foreach ($estimate['lines'] as $i => $line) {
            if ($i === 0 || $line['informational'] || $line['amount']->amountMinor() <= 0) {
                continue;   // [0] is the room itself; the total is what the rest is "incl." of
            }
            $parts[] = __('php/http/controllers/booking_engine_controller.total_part', ['amount' => $this->money($line['amount']->amountMinor(), $currency), 'name' => $line['label']]);
        }
        $note = $parts === [] ? '' : __('php/http/controllers/booking_engine_controller.total_includes', ['items' => implode(' · ', $parts)]);

        // ⚠️ THE NIGHTLY'S BASIS, SAID OUT LOUD (F3 — the founder's ratification, guest fix pass 2).
        //
        // The deep-parity audit found the room nightly (VAT-EXCLUSIVE) sitting beside the add-ons
        // (VAT-INCLUSIVE, captioned "Prices include VAT") with no cue that the two figures are on different
        // bases. Pass 1 established that CONVERGING them is a reprice or a 929-row rate migration, so the
        // bases were ratified and this cue closes the ambiguity instead — the per-night figure says it is
        // shown before tax, while `$note` above says what the TOTAL already contains.
        //
        // ⚠️ GATED ON THE TAX BEING GENUINELY CHARGED, not merely configured. {@see StayEstimator::taxApplies}
        // is false for an INCLUSIVE percentage tax — for such a property the nightly really is gross and this
        // cue would be a lie. Reusing that predicate (rather than writing the rule a third time) is also why
        // this cannot drift from what the total actually charges.
        //
        // ⚠️ THE TAX'S OWN NAME, never a hardcoded "VAT" — a property may levy IVA, MwSt or a sales tax, and
        // the enumeration above already names them from the same source.
        $nightlyNote = '';
        foreach ($taxes as $tax) {
            if ((string) $tax->mode === 'percentage' && StayEstimator::taxApplies($tax, $currency)) {
                $nightlyNote = __('php/http/controllers/booking_engine_controller.nightly_excludes', ['tax' => (string) $tax->name]);
                break;
            }
        }

        return [$this->money($totalMinor, $currency), $totalMinor, $note, $nightlyNote];
    }

    /**
     * Real, honest feature chips for a room card (sleeps / bedrooms / kind) — from real columns only.
     *
     * @return list<string>
     */
    private function roomFeatures(?RoomType $rt): array
    {
        if ($rt === null) {
            return [];
        }
        $features = [];
        $capacity = (int) $rt->max_adults + (int) $rt->max_children;
        if ($capacity > 0) {
            $features[] = trans_choice('php/http/controllers/booking_engine_controller.sleeps', $capacity, ['count' => $capacity]);
        }
        if ($rt->bedrooms !== null) {
            $features[] = (int) $rt->bedrooms === 0 ? __('php/http/controllers/booking_engine_controller.studio') : trans_choice('php/http/controllers/booking_engine_controller.bedrooms', (int) $rt->bedrooms, ['count' => (int) $rt->bedrooms]);
        }
        $features[] = $this->roomKind((string) $rt->kind);

        return $features;
    }

    /**
     * Apply the real refine controls to the already-real offer set. Client-safe filtering over server-real
     * data — bedrooms / amenities (all-of) / max grand-total price, then sort. Never fabricates a match.
     *
     * @param  list<array<string, mixed>>  $rooms
     * @param  list<string>  $amenityFilter
     * @return list<array<string, mixed>>
     */
    private function applyRefine(array $rooms, ?int $bedrooms, array $amenityFilter, ?int $maxPrice, string $sort): array
    {
        $rooms = array_values(array_filter($rooms, function (array $room) use ($bedrooms, $amenityFilter, $maxPrice): bool {
            if ($bedrooms !== null && (int) ($room['bedrooms'] ?? -1) !== $bedrooms) {
                return false;
            }
            if ($maxPrice !== null && (int) $room['grand_total_minor'] > $maxPrice * 100) {
                return false;
            }
            foreach ($amenityFilter as $needed) {
                if (! in_array($needed, $room['amenities'] ?? [], true)) {
                    return false;
                }
            }

            return true;
        }));

        usort($rooms, fn (array $a, array $b): int => match ($sort) {
            'price_asc' => $a['grand_total_minor'] <=> $b['grand_total_minor'],
            'price_desc' => $b['grand_total_minor'] <=> $a['grand_total_minor'],
            'rating' => (float) ($b['rating']['average'] ?? 0) <=> (float) ($a['rating']['average'] ?? 0),
            default => 0,   // recommended = the engine's own order (cheapest-plan sellable)
        });

        return $rooms;
    }

    /**
     * The max-price slider ceiling — the dearest real grand total (in whole currency units), or a sane default.
     *
     * @param  list<array<string, mixed>>  $rooms
     */
    private function maxPriceCeiling(array $rooms): int
    {
        $max = 0;
        foreach ($rooms as $room) {
            $max = max($max, (int) ceil(((int) $room['grand_total_minor']) / 100));
        }

        return $max > 0 ? $max : 500;
    }

    /**
     * PUBLIC room-type detail (design pass · Booking Screen 2). Server-rendered, READ-ONLY, no auth.
     * The room-type is resolved PROPERTY-SCOPED — a room of another property/org 404s on this
     * storefront. Real room specs (+ real availability/price for the searched dates via the certified
     * search path); no gallery/amenities are fabricated. Writes NOTHING (checkout is the next screen).
     */
    public function room(Request $request): View
    {
        $property = $this->property($request);
        $roomTypeId = (string) $request->route('roomType');

        // PROPERTY-scoped (and tenant-scoped) — cross-property / cross-org room 404s.
        $rt = RoomType::query()
            ->where('property_id', $property->getKey())
            ->findOrFail($roomTypeId);

        $adults = max(1, (int) $request->query('adults', '2'));
        $children = max(0, (int) $request->query('children', '0'));
        $checkIn = $this->validDate($request->query('check_in'));
        $checkOut = $this->validDate($request->query('check_out'));
        $searched = $checkIn !== null && $checkOut !== null && $checkOut > $checkIn;

        // REAL offer for THIS room on the searched dates (via the certified search path) — null if the
        // room isn't sellable for those dates (shown as an honest "not available", never faked).
        $offer = $searched
            ? $this->offerFor($property, (string) $rt->getKey(), $checkIn, $checkOut, $adults, $children)
            : null;

        return view('booking.room', [
            // STOREFRONT SEO (D10) — additive. `$offer` is the page's OWN real engine offer (null unless the
            // visitor searched real dates), so no price is claimed for the canonical dateless URL.
            'seo' => $this->seo->forRoom($property, $rt, $offer === null ? null : [
                // ⚠️ THE GRAND TOTAL, not the room subtotal — the all-in figure this page itself displays and
                // the folio reconciles to (#17). Advertising the VAT-exclusive room price would have quoted a
                // number lower than anything a guest can actually pay.
                'total_minor' => (int) $offer['grand_total_minor'],
                'currency' => (string) $property->currency,
                'check_in' => (string) $checkIn,
                'check_out' => (string) $checkOut,
            ], $rt->coverPhoto()?->url),
            'property' => ['name' => (string) $property->name, 'type' => $this->propertyType((string) $property->type), 'slug' => (string) $property->slug],
            'room' => [
                'id' => (string) $rt->getKey(),
                'name' => (string) $rt->name,
                'kind' => $this->roomKind((string) $rt->kind),
                'bedrooms' => $rt->bedrooms !== null ? (int) $rt->bedrooms : null,
                'std_occupancy' => $rt->std_occupancy !== null ? (int) $rt->std_occupancy : null,
                'max_adults' => (int) $rt->max_adults,
                'max_children' => (int) $rt->max_children,
                'capacity' => (int) $rt->max_adults + (int) $rt->max_children,
                // REAL photos (public disk) — empty array → the view keeps its gradient fallback.
                'cover' => $rt->coverPhoto()?->url,
                'photos' => $rt->photos->map(fn ($p): array => ['url' => (string) $p->url, 'alt' => (string) $p->alt])->values()->all(),
            ],
            // REAL amenities only (empty when none are catalogued — never fabricated).
            'amenities' => $rt->amenities()->orderBy('name')->pluck('name')->all(),
            'search' => [
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
                'searched' => $searched,
            ],
            'offer' => $offer,
            // Phase 5 — REAL published reviews + the DERIVED rating (via the shared ReviewRating/ReviewList);
            // never fabricated. A room with no published reviews → has_reviews false ("No reviews yet").
            'rating' => ReviewRating::forRoomType((string) $rt->getKey()),
            'reviews' => ReviewList::forRoomType((string) $rt->getKey()),
        ]);
    }

    /**
     * PUBLIC checkout — review + guest details (design pass · Booking Screen 3, step 1). READ-ONLY:
     * shows the REAL price for the chosen room/dates via the certified search path; writes NOTHING
     * here (the advisory hold + booking happen on the CSRF-protected POST). Property-scoped.
     */
    public function checkout(Request $request): View
    {
        $property = $this->property($request);

        $roomTypeId = (string) $request->query('room_type_id', '');
        $adults = max(1, (int) $request->query('adults', '2'));
        $children = max(0, (int) $request->query('children', '0'));
        $checkIn = $this->validDate($request->query('check_in'));
        $checkOut = $this->validDate($request->query('check_out'));

        $offer = ($checkIn !== null && $checkOut !== null && $checkOut > $checkIn && $roomTypeId !== '')
            ? $this->offerFor($property, $roomTypeId, $checkIn, $checkOut, $adults, $children)
            : null;

        // Real wallet credit (D3) — shown ONLY for a signed-in guest with a real spendable balance (never €0/fake).
        $guest = Auth::guard('guest')->user();
        $currency = (string) $property->currency;
        $walletMinor = $guest instanceof Guest ? app(GuestWalletService::class)->spendableMinor($guest) : 0;

        // Real free-cancellation deadline from the property's policy + the proposed check-in (honest fallback when
        // the property has no such policy — never a fabricated "14 days"); the SAME reading the booked surfaces use.
        $freeCancel = ($checkIn !== null)
            ? CancellationPolicy::freeUntilForCheckIn((string) $property->getKey(), $checkIn)?->translatedFormat(LocalDate::pattern('j M'))
            : null;

        // The wallet card shows the guest's REAL spendable balance + the amount that WOULD apply (capped at what's
        // owed) + the net total after — so the inline (no-Alpine) toggle preview reconciles with the server-side
        // deduction to the cent (#17). Only for a signed-in guest with a real balance AND a priced offer.
        $grandMinor = (int) ($offer['grand_total_minor'] ?? 0);
        $applyMinor = min($walletMinor, $grandMinor);
        $wallet = ($walletMinor > 0 && $offer !== null) ? [
            'minor' => $walletMinor,
            'display' => $this->money($walletMinor, $currency),
            'apply' => $applyMinor,
            'apply_display' => $this->money($applyMinor, $currency),
            'net_display' => $this->money(max(0, $grandMinor - $applyMinor), $currency),
        ] : null;

        // D18-5 phase 3 — the units the guest may actually choose. ⚠️ SHOWN = ENFORCED: this is the SAME
        // read `reserve()` re-validates against and the same one the operator path uses, so the page can
        // never offer a unit the lock would refuse. Each carries its RESOLVED price through the ONE resolver
        // (override → type), so what is offered is what is quoted is what is charged.
        $unitChoices = [];
        if ($offer !== null && $checkIn !== null && $checkOut !== null) {
            $planId = (string) ($offer['rate_plan_id'] ?? '');
            foreach (app(UnitAvailabilityService::class)->freeUnitsForRoomType((string) $property->getKey(), $roomTypeId, $checkIn, $checkOut) as $u) {
                $stay = $planId !== '' ? NightlyRate::stayTotalForUnit($planId, (string) $u['id'], $checkIn, $checkOut) : null;
                $unitChoices[] = [
                    'id' => (string) $u['id'],
                    'label' => (string) ($u['name'] !== '' ? $u['name'] : $u['code']),
                    // ⚠️ NULL means "no price of its own" — the room-type rate already shown applies. We do
                    // NOT restate it as a different number, and certainly not as €0.
                    'price' => $stay !== null ? $this->money($stay, $currency) : null,
                ];
            }
        }

        return view('booking.checkout', [
            // ⚠️ STOREFRONT SEO (D10) — NOINDEX. The checkout is per-visitor and per-quote; before this pass
            // it carried no robots directive at all, which means indexable by default.
            'seo' => $this->seo->forTransactionalPage(__('php/http/controllers/booking_engine_controller.checkout_title', ['property' => $property->name]), (string) $property->name),
            'property' => ['name' => (string) $property->name, 'type' => $this->propertyType((string) $property->type), 'slug' => (string) $property->slug],
            'offer' => $offer,
            'unitChoices' => $unitChoices,
            'wallet' => $wallet,
            'freeCancel' => $freeCancel,
            'search' => [
                'room_type_id' => $roomTypeId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $adults,
                'children' => $children,
            ],
        ]);
    }

    /**
     * PUBLIC reserve (design pass · Booking Screen 3, step 2) — the WRITE. CSRF-protected (session
     * form) + `throttle:booking`. Creates the advisory hold, then books THROUGH the certified
     * {@see BookingEngineService::book} — which routes through AssignmentService (source=direct,
     * re-checks availability under its lock and REJECTS when full — never overbooks) and takes
     * payment through FolioService (append-only). This controller writes NO inventory itself; it
     * only calls the service. On a lost race it returns an honest "no longer available" — never a
     * faked confirmation.
     */
    public function reserve(Request $request): RedirectResponse
    {
        $property = $this->property($request);

        $validated = $request->validate([
            'room_type_id' => ['required', 'string'],
            'rate_plan_id' => ['required', 'string'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['sometimes', 'integer', 'min:1'],
            'children' => ['sometimes', 'integer', 'min:0'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            // The guest's REAL payment choice (never decorative): pay now by card, or settle on arrival.
            'payment_option' => ['sometimes', 'string', 'in:card,on_arrival'],
            // D18-5 phase 3 — the SPECIFIC unit the guest chose, when they chose one.
            'unit_id' => ['sometimes', 'nullable', 'string'],
        ]);

        $adults = (int) ($validated['adults'] ?? 2);
        $children = (int) ($validated['children'] ?? 0);

        // ⚠️ SHOWN = ENFORCED, AND IT IS REUSE, NOT A NEW RULE. The submitted unit is re-checked against the
        // SAME read the operator path validates against and the same set the page offered; anything that is
        // not genuinely free for these dates is DROPPED rather than trusted, and the booking falls back to
        // type-level. So a stale tab or a hand-edited form can never pick a unit the lock would refuse — and
        // the lock remains the real guarantee, untouched.
        $chosenUnitId = null;
        $submitted = $validated['unit_id'] ?? null;
        if (is_string($submitted) && $submitted !== '') {
            $free = app(UnitAvailabilityService::class)->freeUnitsForRoomType(
                (string) $property->getKey(), (string) $validated['room_type_id'],
                (string) $validated['check_in'], (string) $validated['check_out'],
            );
            $freeIds = array_map(fn (array $u): string => (string) $u['id'], $free);
            $chosenUnitId = in_array($submitted, $freeIds, true) ? $submitted : null;
        }

        // Advisory hold (reserves NO inventory, has an expiry) — a price/availability snapshot only.
        $quote = $this->engine->quote(
            $property,
            $validated['room_type_id'],
            $validated['rate_plan_id'],
            $validated['check_in'],
            $validated['check_out'],
            $adults,
            $children,
            $validated['email'],
            null,
            $chosenUnitId,
        );

        $guestData = [
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
        ];

        // Book with the balance DUE (no inline charge) so a signed-in guest's REAL wallet credit can reduce it
        // FIRST (reconciling #17), and the remaining balance is then paid via the certified rail. The booking
        // routes THROUGH AssignmentService (re-checks under lock; never overbooks). No PAN touches us (SAQ A).
        $result = $this->engine->book($quote, 'checkout', $guestData, takePayment: false);

        if ($result['status'] === 'unavailable') {
            return $this->soldOutRedirect($property, $validated, $adults, $children);
        }

        $reservation = $result['reservation'];
        $folio = $result['folio'];
        $confirmationUrl = route('booking.reservation', [$property->slug, $quote->getKey()]);

        // already_converted (re-submit) → straight to the confirmation.
        if (! $reservation instanceof Reservation || ! $folio instanceof Folio) {
            return redirect()->to($confirmationUrl);
        }

        // Real wallet credit (founder decision D3) — ONLY a signed-in guest with a real spendable balance, capped
        // at what's owed, genuinely deducted (never a fabricated €50, #39); it reduces the folio balance (#17).
        $this->applyGuestWalletCredit($request, $reservation);

        $folio->refresh();
        $currency = (string) $reservation->currency;
        $balanceMinor = FolioTotals::for($folio->load(['items', 'payments']))['balance_minor'];

        // Fully covered by credit → nothing to charge; confirm.
        if ($balanceMinor <= 0) {
            return redirect()->to($confirmationUrl);
        }

        // Honor the guest's REAL payment choice — NEVER fabricate a payment (#39). "Pay on arrival" leaves the
        // balance genuinely DUE (settled at the property), and the confirmation reflects that honestly. The card
        // rail is only offered + valid when the certified TEST-MODE Stripe gateway is wired; otherwise the sole
        // honest option is pay-on-arrival, and we NEVER record an 'offline' payment the guest never made (#17: the
        // folio balance stays === total until money is really collected).
        $wantsCard = ($validated['payment_option'] ?? 'card') === 'card'
            && (string) config('services.payments.gateway') === 'stripe';

        if (! $wantsCard) {
            // Pay on arrival — record NO payment; the balance is due at the property and reconciles (#17).
            return redirect()->to($confirmationUrl);
        }

        // ⚠️⚠️ DIRECT CHARGE, AND NO FALLBACK. If the operator's account is not `charges_enabled`, the
        //    storefront cannot take a card at all — charging on the platform instead would put guest money
        //    back in Vyrobel's balance, which is the whole thing this switch removes.
        $connectedAccountId = app(OperatorConnectService::class)->accountForCharge(
            Organization::query()->findOrFail((string) $reservation->organization_id),
        );

        $session = app(StripeClientInterface::class)->createPaymentCheckout(
            amountMinor: $balanceMinor,
            currency: $currency,
            description: 'Booking '.$reservation->reference,
            successUrl: $confirmationUrl.'?paid=1',
            cancelUrl: $confirmationUrl.'?canceled=1',
            metadata: [
                'organization_id' => (string) $reservation->organization_id,
                'reservation_id' => (string) $reservation->getKey(),
                'folio_id' => (string) $folio->getKey(),
            ],
            idempotencyKey: StripeIdempotency::keyFor('folio_checkout', (string) $folio->getKey()),
            connectedAccountId: $connectedAccountId,
        );

        // No payment intent on the session (shouldn't happen for a payment-mode session) → don't strand
        // the guest; show the confirmation with the balance still due (honest).
        if ($session->paymentIntentId === null) {
            return redirect()->to($confirmationUrl);
        }

        // Pre-create the PENDING payment keyed on the intent id (idempotent) — settled on the webhook.
        $this->folios->recordCheckoutIntent($folio, Money::of($balanceMinor, $currency), $session->paymentIntentId, [
            'booking_quote_id' => $quote->getKey(),
            'source' => 'storefront',
        ], $connectedAccountId, $session);

        return redirect()->away($session->url);
    }

    /**
     * Apply a signed-in guest's REAL wallet credit to the booking (founder decision D3) — the SAME certified path
     * the operator uses ({@see GuestWalletService::applyToBooking}): capped at the guest's spendable
     * balance AND at what's owed, debited from the wallet + posted as a folio discount line (reconciling #17).
     * Anonymous storefront / no real balance / nothing owed → nothing applied (never a fabricated credit, #39).
     */
    private function applyGuestWalletCredit(Request $request, Reservation $reservation): int
    {
        if (! $request->boolean('apply_credit')) {
            return 0;
        }

        $guest = Auth::guard('guest')->user();
        if (! $guest instanceof Guest) {
            return 0;   // wallet credit is a signed-in feature only
        }

        $wallet = app(GuestWalletService::class);
        $spendable = $wallet->spendableMinor($guest);
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with(['items', 'payments'])->first();
        $owed = $folio instanceof Folio ? (int) FolioTotals::for($folio)['balance_minor'] : 0;

        $apply = max(0, min($spendable, $owed));
        if ($apply <= 0) {
            return 0;   // no real balance / nothing owed → never a fabricated credit
        }

        $wallet->applyToBooking($guest, $reservation, Money::of($apply, (string) $reservation->currency));

        return $apply;
    }

    /**
     * The honest "someone just booked it" redirect — back to checkout carrying the search, never a faked
     * confirmation and never an overbooking.
     *
     * @param  array<string, mixed>  $validated
     */
    private function soldOutRedirect(Property $property, array $validated, int $adults, int $children): RedirectResponse
    {
        return redirect()
            ->to(route('booking.checkout', $property->slug).'?'.http_build_query([
                'room_type_id' => $validated['room_type_id'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'adults' => $adults,
                'children' => $children,
            ]))
            ->with('booking_error', __('php/http/controllers/booking_engine_controller.room_just_taken'));
    }

    /**
     * PUBLIC styled confirmation (design pass · Booking Screen 3, step 3). Shows the REAL reservation
     * reference for a converted, property-scoped quote. Read-only.
     */
    public function reservation(Request $request): View
    {
        $property = $this->property($request);
        $quoteId = (string) $request->route('quote');

        $bookingQuote = BookingQuote::query()
            ->where('property_id', $property->getKey())
            ->with('convertedReservation')
            ->findOrFail($quoteId);

        $reservation = $bookingQuote->convertedReservation;
        abort_if($reservation === null, 404);

        // Honest payment state (never faked). 'paid' → settled (manual, or the Stripe webhook fired);
        // 'processing' → the guest returned from Stripe but the webhook hasn't settled it yet; 'on_arrival'
        // → the balance is due at the property. Card-return is signalled by ?paid=1 (success redirect).
        $payStatus = (string) $reservation->payment_status;
        $cardReturn = $request->query('paid') === '1';
        $payment = match (true) {
            $payStatus === 'paid' => ['label' => __('php/http/controllers/booking_engine_controller.payment_received'), 'note' => __('php/http/controllers/booking_engine_controller.payment_received_note')],
            // ⚠️ THIS PROMISED AN EMAIL THAT IS NEVER SENT. `SendGuestTransactionalEmails::HANDLED` covers
            //    reservation.confirmed / cancelled / payment.refunded / checked_out — NOT payment
            //    settlement, and no such Mailable exists. Found by the error-string sweep; it predates the
            //    Connect passes. ⚠️ The page itself IS the place the guest can check, so it says that.
            $cardReturn => ['label' => __('php/http/controllers/booking_engine_controller.payment_processing'), 'note' => __('php/http/controllers/booking_engine_controller.payment_processing_note')],
            default => ['label' => __('php/http/controllers/booking_engine_controller.pay_on_arrival'), 'note' => __('php/http/controllers/booking_engine_controller.pay_on_arrival_note')],
        };

        return view('booking.reservation', [
            // ⚠️ STOREFRONT SEO (D10) — NOINDEX. A booking confirmation carries a real reference and a real
            // guest email; it must never be indexable, and it was, by default, before this pass.
            'seo' => $this->seo->forTransactionalPage(__('php/http/controllers/booking_engine_controller.confirmed_title', ['property' => $property->name]), (string) $property->name),
            'property' => ['name' => (string) $property->name, 'type' => $this->propertyType((string) $property->type), 'slug' => (string) $property->slug],
            'reservation' => [
                'reference' => (string) $reservation->reference,
                'check_in' => CarbonImmutable::parse($reservation->check_in)->translatedFormat(LocalDate::pattern('l, j M Y')),
                'check_out' => CarbonImmutable::parse($reservation->check_out)->translatedFormat(LocalDate::pattern('l, j M Y')),
                'nights' => (int) CarbonImmutable::parse($reservation->check_in)->diffInDays(CarbonImmutable::parse($reservation->check_out)),
                'total' => $this->money((int) $reservation->total_minor, (string) $reservation->currency),
                'email' => (string) $bookingQuote->email,
            ],
            'payment' => $payment,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['sometimes', 'integer', 'min:1'],
            'children' => ['sometimes', 'integer', 'min:0'],
        ]);

        $offers = $this->engine->search(
            $this->property($request),
            $validated['check_in'],
            $validated['check_out'],
            (int) ($validated['adults'] ?? 1),
            (int) ($validated['children'] ?? 0),
        );

        return response()->json(['data' => $offers]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => ['required', 'string'],
            'rate_plan_id' => ['required', 'string'],
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'adults' => ['sometimes', 'integer', 'min:1'],
            'children' => ['sometimes', 'integer', 'min:0'],
            'email' => ['sometimes', 'email'],
            'session_token' => ['sometimes', 'string'],
        ]);

        $quote = $this->engine->quote(
            $this->property($request),
            $validated['room_type_id'],
            $validated['rate_plan_id'],
            $validated['check_in'],
            $validated['check_out'],
            (int) ($validated['adults'] ?? 1),
            (int) ($validated['children'] ?? 0),
            $validated['email'] ?? null,
            $validated['session_token'] ?? null,
        );

        return response()->json(['data' => $this->quotePayload($quote)], 201);
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_id' => ['required', 'string'],
            'payment_token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'first_name' => ['sometimes', 'string'],
            'last_name' => ['sometimes', 'string'],
        ]);

        // Tenant- AND property-scoped: a quote from another property/org simply isn't found.
        $quote = BookingQuote::query()
            ->where('property_id', $this->property($request)->getKey())
            ->findOrFail($validated['quote_id']);

        $result = $this->engine->book($quote, $validated['payment_token'], [
            'email' => $validated['email'],
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
        ]);

        $status = $result['status'] === 'confirmed' ? 201 : ($result['status'] === 'unavailable' ? 409 : 200);
        $reservation = $result['reservation'];

        return response()->json([
            'data' => [
                'status' => $result['status'],
                'reservation_reference' => $reservation?->reference,
                'payment_status' => $result['payment']?->status,
                'client_secret' => $result['client_secret'], // present when SCA/3DS is required
            ],
        ], $status);
    }

    public function confirmation(Request $request, string $quote): JsonResponse
    {
        $bookingQuote = BookingQuote::query()
            ->where('property_id', $this->property($request)->getKey())
            ->findOrFail($quote);

        return response()->json(['data' => $this->quotePayload($bookingQuote)]);
    }

    private function property(Request $request): Property
    {
        // Re-load tenant-scoped from the id the middleware resolved (context is already set).
        return Property::query()->findOrFail($request->attributes->get(ResolvePublicProperty::ATTRIBUTE));
    }

    /**
     * Shape one real search offer for the storefront. per-night is DERIVED from the real stay total
     * (honest — from real figures), never a separately-invented nightly price.
     *
     * @param  array<string, mixed>  $offer
     * @return array<string, mixed>
     */
    private function roomOffer(array $offer): array
    {
        $nights = max(1, (int) $offer['nights']);
        $totalMinor = (int) $offer['total_minor'];
        $currency = (string) $offer['currency'];

        return [
            'room_type_id' => (string) $offer['room_type_id'],
            'rate_plan_id' => (string) $offer['rate_plan_id'],
            'name' => (string) $offer['name'],
            'kind' => $this->roomKind((string) $offer['kind']),
            'available' => (int) $offer['available'],
            'nights' => $nights,
            'per_night' => $this->money((int) round($totalMinor / $nights), $currency),
            'total' => $this->money($totalMinor, $currency),
        ];
    }

    /**
     * The REAL sellable offer for one room-type on the given dates (via the certified search path),
     * or null if it isn't sellable then. Reused by the room detail + checkout review.
     *
     * @return array<string, mixed>|null
     */
    private function offerFor(Property $property, string $roomTypeId, string $checkIn, string $checkOut, int $adults, int $children): ?array
    {
        foreach ($this->engine->search($property, $checkIn, $checkOut, $adults, $children) as $offer) {
            if ((string) $offer['room_type_id'] === $roomTypeId) {
                $card = $this->roomOffer($offer);
                $taxes = Tax::query()->where('property_id', $property->getKey())->get();
                $fees = Fee::query()->where('property_id', $property->getKey())->get();
                $persons = max(1, $adults + $children);
                [$card['grand_total'], $card['grand_total_minor'], $card['fees_note'], $card['nightly_note']] = $this->grandTotal($taxes, $fees, $offer, $persons);
                $card['breakdown'] = $this->breakdownLines($taxes, $fees, $offer, $persons);   // room + fees + taxes, reconciling (#17)

                return $card;
            }
        }

        return null;
    }

    /**
     * The reconciling price breakdown for the checkout review — one display line per component (room + the
     * real charged taxes/fees) via the SAME {@see StayEstimator} the folio uses, so what the guest reviews
     * === what the folio charges to the cent (#17). An inclusive tax is flagged informational (already in room).
     *
     * @param  Collection<int, Tax>  $taxes
     * @param  Collection<int, Fee>  $fees
     * @param  array<string, mixed>  $offer
     * @return list<array{label: string, detail: string, amount: string, informational: bool}>
     */
    private function breakdownLines(Collection $taxes, Collection $fees, array $offer, int $persons): array
    {
        $currency = (string) $offer['currency'];
        $room = Money::of((int) $offer['total_minor'], $currency);
        $nights = max(1, (int) $offer['nights']);
        $estimate = StayEstimator::estimate($taxes, $fees, $room, $nights, $persons, $currency);

        return array_map(fn (array $line): array => [
            'label' => (string) $line['label'],
            'detail' => (string) $line['detail'],
            'amount' => $this->money($line['amount']->amountMinor(), $currency),
            'informational' => (bool) $line['informational'],
        ], $estimate['lines']);
    }

    /**
     * Real room-type teaser (names/kind/capacity + cover photo only) shown before a date search — no
     * availability or price is claimed, so nothing is fabricated.
     *
     * @param  array<string, string|null>  $covers  room_type_id → cover URL
     * @return list<array<string, mixed>>
     */
    private function teaserRooms(Property $property, array $covers = []): array
    {
        return RoomType::query()
            ->where('property_id', $property->getKey())
            ->orderBy('name')
            ->get()
            ->map(fn (RoomType $rt): array => [
                'name' => (string) $rt->name,
                'kind' => $this->roomKind((string) $rt->kind),
                'capacity' => (int) $rt->max_adults + (int) $rt->max_children,
                'cover' => $covers[(string) $rt->getKey()] ?? null,
            ])->all();
    }

    private function validDate(mixed $raw): ?string
    {
        if (! is_string($raw) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(int $minor, string $currency): string
    {
        $symbol = MoneyFormatter::symbol($currency);   // the ONE symbol map (§N.8f — one definition per concept)

        return $symbol.number_format($minor / 100, 2, '.', ',');
    }

    private function roomKind(string $kind): string
    {
        return match ($kind) {
            'room' => __('php/http/controllers/booking_engine_controller.kind_room'),
            'apartment' => __('php/http/controllers/booking_engine_controller.kind_apartment'),
            'villa' => __('php/http/controllers/booking_engine_controller.kind_villa'),
            'dorm' => __('php/http/controllers/booking_engine_controller.kind_dorm'),
            default => ucwords(str_replace('_', ' ', $kind)),
        };
    }

    private function propertyType(string $type): string
    {
        return match ($type) {
            'hotel' => __('php/http/controllers/booking_engine_controller.type_hotel'),
            'aparthotel' => __('php/http/controllers/booking_engine_controller.type_aparthotel'),
            'serviced_apartment' => __('php/http/controllers/booking_engine_controller.type_serviced_apartment'),
            'apartment_building' => __('php/http/controllers/booking_engine_controller.type_apartment_building'),
            'apartment' => __('php/http/controllers/booking_engine_controller.type_apartment'),
            'villa' => __('php/http/controllers/booking_engine_controller.type_villa'),
            'hostel' => __('php/http/controllers/booking_engine_controller.type_hostel'),
            'guest_house' => __('php/http/controllers/booking_engine_controller.type_guest_house'),
            'resort' => __('php/http/controllers/booking_engine_controller.type_resort'),
            'mixed' => __('php/http/controllers/booking_engine_controller.type_mixed'),
            'portfolio' => __('php/http/controllers/booking_engine_controller.type_portfolio'),
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(BookingQuote $quote): array
    {
        return [
            'quote_id' => $quote->getKey(),
            'reference' => $quote->reference,
            'status' => $quote->status,
            'check_in' => CarbonImmutable::parse($quote->check_in)->toDateString(),
            'check_out' => CarbonImmutable::parse($quote->check_out)->toDateString(),
            'currency' => $quote->currency,
            'total_minor' => $quote->total_minor,
            'held_until' => $quote->held_until !== null ? CarbonImmutable::parse($quote->held_until)->toIso8601String() : null,
            'expires_at' => $quote->expires_at !== null ? CarbonImmutable::parse($quote->expires_at)->toIso8601String() : null,
            'reservation_reference' => $quote->convertedReservation?->reference,
        ];
    }

    /**
     * The first REAL room photo across a property's room types, for `og:image` — or NULL.
     *
     * ⚠️ NULL IS A NORMAL, EXPECTED RESULT and must stay one: a property whose room types have no photos
     * gets NO `og:image` rather than a placeholder. A stock or platform-default image on a social card is a
     * fabricated impression of somewhere a guest is deciding whether to sleep (#39).
     *
     * @param  Collection<string, RoomType>  $roomTypes
     */
    private function firstPhotoUrl(Collection $roomTypes): ?string
    {
        foreach ($roomTypes as $roomType) {
            $url = $roomType->coverPhoto()?->url;
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return null;
    }
}
