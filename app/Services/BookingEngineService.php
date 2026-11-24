<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Booking\BookingException;
use App\Exceptions\Booking\StayNotBookableException;
use App\Models\BookingQuote;
use App\Models\Folio;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Payments\Contracts\PaymentGateway;
use App\Support\Booking\BookingTerms;
use App\Support\Folio\FolioTotals;
use App\Support\Folio\PropertyCharges;
use App\Support\Guest\GuestEmailMatch;
use App\Support\I18n\Locales;
use App\Support\Money\Money;
use App\Support\Pricing\NightlyRate;
use App\Support\Schedule\BoardDate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * The public booking engine (Step 7). A pure CONSUMER of the certified core: search reads
 * AvailabilityService + rate_calendar; quote writes only an ADVISORY booking_quotes hold (never
 * touches sold, never occupies inventory); book routes through AssignmentService (source=direct —
 * which re-checks availability under its lock and REJECTS when full, the Step 4 asymmetry) and
 * takes payment through FolioService. It never writes reservation_units/availability itself.
 *
 * All methods operate on an already-resolved, tenant-scoped Property (see ResolvePublicProperty)
 * and additionally filter by that property so one property can never surface another's inventory.
 */
class BookingEngineService
{
    public function __construct(
        private AvailabilityService $availability,
        private AssignmentService $assignment,
        private FolioService $folios,
    ) {}

    /**
     * Read-only availability + price search. Returns one offer per sellable room_type (has stay-
     * wide availability, fits occupancy, and has a fully-priced rate plan).
     *
     * @return list<array<string, mixed>>
     */
    public function search(Property $property, string $checkIn, string $checkOut, int $adults = 1, int $children = 0): array
    {
        // ⚠️ VY-15 — SHOWN = ENFORCED: a stay that has already begun at the property is refused by quote(), so it is never offered here.
        if ($this->hasPassed($property, $checkIn)) {
            return [];
        }

        $nights = $this->nights($checkIn, $checkOut);

        $roomTypes = RoomType::query()
            ->where('property_id', $property->getKey())
            ->where('max_adults', '>=', $adults)
            ->where('max_children', '>=', $children)
            ->get();

        $offers = [];
        foreach ($roomTypes as $roomType) {
            $available = $this->minAvailable($roomType->getKey(), $checkIn, $checkOut);
            if ($available < 1) {
                continue;
            }

            $offer = $this->cheapestRatePlan($roomType, $checkIn, $checkOut);
            if ($offer === null) {
                continue; // not sellable without a full price for the stay
            }

            $offers[] = [
                'room_type_id' => $roomType->getKey(),
                'name' => $roomType->name,
                'kind' => $roomType->kind,
                'available' => $available,
                'nights' => $nights,
                'rate_plan_id' => $offer['rate_plan_id'],
                'currency' => $offer['currency'],
                'total_minor' => $offer['total_minor'],
            ];
        }

        return $offers;
    }

    /**
     * Create an ADVISORY hold (Step 0 decision): a price snapshot + held_until/expires_at timers.
     * It does NOT touch availability.sold and reserves NO inventory — two holds on the last unit
     * both succeed; only one book() will seat.
     */
    /**
     * D18-5 phase 3 — `$unitId` is the SPECIFIC unit the guest chose, or NULL for a type-level quote.
     *
     * ⚠️ NO SCHEMA CHANGE: `requested` is already a JSON array of lines, so the unit rides INSIDE a line and
     * a quote minted before this existed simply lacks the key — which is exactly the backward-compatibility a
     * nullable column would have bought, at no cost.
     */
    /**
     * VY-04 (2026-11-24) — `$checkoutToken` is the ONE-TIME token of the checkout page this quote was made from, or NULL for a quote
     * that did not come from that form (the JSON API). It is stored under a UNIQUE index, so a second quote for the same page cannot be
     * created: the insert throws `UniqueConstraintViolationException`, which the checkout controller reads as "this page already booked".
     */
    public function quote(Property $property, string $roomTypeId, string $ratePlanId, string $checkIn, string $checkOut, int $adults, int $children, ?string $email = null, ?string $sessionToken = null, ?string $unitId = null, ?string $checkoutToken = null): BookingQuote
    {
        $roomType = RoomType::query()->where('property_id', $property->getKey())->findOrFail($roomTypeId);
        $ratePlan = RatePlan::query()->where('room_type_id', $roomType->getKey())->findOrFail($ratePlanId);

        // ⚠️⚠️ VY-15 — every quote is made HERE, by the web checkout and by the JSON API alike, so the rules are checked here once.
        $this->assertBookable($property, $roomType, $ratePlan, $checkIn, $adults, $children);

        // ⚠️ THE ONE RESOLVER, AND ZERO MOVEMENT BY CONSTRUCTION (§N.8f / D4-1). stayTotalForUnit() returns
        // NULL when the chosen unit carries no override anywhere in the stay — and NULL for a type-level
        // quote — so the fallback is the identical priceOver() call this line has always made. A stay that
        // nobody has priced individually is therefore quoted byte-for-byte as it is today.
        $totalMinor = ($unitId !== null
            ? NightlyRate::stayTotalForUnit((string) $ratePlan->getKey(), $unitId, $checkIn, $checkOut)
            : null) ?? $this->priceOver($ratePlan->getKey(), $checkIn, $checkOut);
        if ($totalMinor === null) {
            throw new \InvalidArgumentException('No published rate for the selected dates.');
        }

        $currency = (string) $ratePlan->currency;
        $total = Money::of($totalMinor, $currency);

        return BookingQuote::create([
            'property_id' => $property->getKey(),
            'reference' => 'Q-'.Str::upper(Str::random(8)),
            'session_token' => $sessionToken ?? (string) Str::uuid(),
            'checkout_token' => $checkoutToken,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'requested' => [[
                'room_type_id' => $roomType->getKey(),
                'rate_plan_id' => $ratePlan->getKey(),
                // ⚠️ The chosen unit rides the line — no migration. NULL means "any unit of the type", which
                // is what every quote meant before this feature existed.
                'unit_id' => $unitId,
                'qty' => 1,
                // ⚠️ VY-16 (2026-11-24) — THE LINE'S PRICE IS FIXED HERE, AT THE QUOTE, and book() writes exactly this on the booking
                //    line. It was re-priced at booking time, so a rate change in between left the folio on the quoted figure and the line
                //    (which the occupancy and revenue reports read) on the new one. Same JSON array, no migration.
                'price_minor' => $totalMinor,
            ]],
            'currency' => $currency,
            'subtotal' => $total,
            'total' => $total,
            'email' => $email,
            'status' => BookingQuote::STATUS_HELD,
            'held_until' => now()->addMinutes(15),   // advisory only
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Convert a held quote into a real booking. Routes THROUGH AssignmentService (source=direct),
     * which re-checks availability under its lock — the advisory hold guaranteed nothing. If the
     * seat is gone, returns ['status' => 'unavailable'] (never overbooks — direct rejects when
     * full). On success: seats, posts the room charge PLUS the property's real non-optional fees +
     * taxes (so the folio total is the TRUE total — #17: the shown grand total incl. the cleaning
     * fee === the folio total, never a hardcoded €45), then (when $takePayment) settles the FULL
     * folio balance through FolioService, and marks the quote converted. The booking's domain events
     * drive the Step 3 ARI push outward.
     *
     * ⚠️ $takePayment=false skips the inline charge and leaves the folio balance DUE — used by the
     * storefront's TEST-MODE hosted-Stripe rail (the controller then creates a Checkout session for
     * the balance and fulfils on the webhook). The default (true) records a full-balance payment inline
     * through the CARD gateway — and records NOTHING while the gateway is manual (VY-01, see below).
     *
     * ⚠️ `$terms` (BOOKING TERMS, 2026-11-14) is the {@see BookingTerms} the guest accepted. When given, the acceptance (time, version,
     * the terms exactly as shown) is written ON THE RESERVATION IN THE SAME TRANSACTION that creates it — never a later update that could be
     * missed. NULL (every existing caller, the certified engine tests) records no acceptance, exactly as before.
     *
     * @param  array{email?: string, first_name?: string, last_name?: string}  $guestData
     * @param  array{version: string, locale: string, sections: list<array{key: string, title: string, lines: list<string>}>, extra: ?string}|null  $terms
     * @return array{status: string, reservation: ?Reservation, payment: ?Payment, folio: ?Folio, client_secret: ?string}
     */
    public function book(BookingQuote $quote, string $paymentToken, array $guestData, bool $takePayment = true, ?array $terms = null): array
    {
        if ($quote->status === BookingQuote::STATUS_CONVERTED) {
            return ['status' => 'already_converted', 'reservation' => $quote->convertedReservation, 'payment' => null, 'folio' => null, 'client_secret' => null];
        }

        // ⚠️ VY-15 — AND AGAIN AT THE BOOKING: a quote is only a price snapshot, and nothing stops one being presented after its arrival
        //    day has passed or after the operator switched its rate plan off. Checked BEFORE anything is written (no guest, no booking).
        $this->assertQuoteStillBookable($quote);

        // ⚠️⚠️ VY-17 (audit of 2026-11-24) — ONE GUEST PER EMAIL IN AN ORGANISATION. This always created a NEW guest, so a second
        //    booking with the same email made a second record and the guest's portal account (one record) showed only one of the two.
        //    The booking now joins the guest who already holds the email here ({@see GuestEmailMatch::bookingGuest()} — the blind index,
        //    tenant-scoped, the portal account first). ⚠️ The existing record is used AS IT IS: this form is anonymous, so its name and
        //    language are never written onto someone who already exists.
        $guestEmail = $guestData['email'] ?? $quote->email;
        $guest = GuestEmailMatch::bookingGuest($guestEmail) ?? Guest::create([
            'first_name' => $guestData['first_name'] ?? 'Guest',
            'last_name' => $guestData['last_name'] ?? '',
            'email' => $guestEmail,
            // I18N-01 — the language the guest booked in (the storefront's language menu) goes with them, so the confirmation
            // and pre-arrival emails arrive in it (Guest::preferredLocale). English stays unset, exactly as before.
            ...(app()->getLocale() !== Locales::SOURCE ? ['language' => app()->getLocale()] : []),
        ]);

        $checkIn = CarbonImmutable::parse($quote->check_in)->toDateString();
        $checkOut = CarbonImmutable::parse($quote->check_out)->toDateString();
        $total = Money::of((int) $quote->total_minor, (string) $quote->currency);

        /** @var list<array<string, mixed>> $requested */
        $requested = $quote->requested;
        $lines = [];
        foreach ($requested as $line) {
            // ⚠️ A LINE FROM AN OLDER QUOTE HAS NO `unit_id` KEY — it resolves type-level, exactly as before.
            $lineUnitId = isset($line['unit_id']) && is_string($line['unit_id']) ? $line['unit_id'] : null;

            $lines[] = [
                'room_type_id' => $line['room_type_id'],
                'rate_plan_id' => $line['rate_plan_id'],
                // ⚠️ THE CORE IS FREE: createBooking() already accepts an optional per-line unit_id, and
                // occupy()/assertUnitFree() already guard it under the same lock. Nothing about the
                // concurrency guarantee changes — this only tells it WHICH unit was chosen.
                'unit_id' => $lineUnitId,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $quote->adults,
                'children' => $quote->children,
                // ⚠️⚠️ VY-16 — CHARGED = SHOWN = STORED ON THE LINE. The price is the one the QUOTE fixed (its line's `price_minor`),
                //    never a fresh read of the rate calendar: a rate changed after the quote must not put a second price on the booking.
                //    ⚠️ A quote made before the line carried a price has ONE line (quote() has only ever written one), so that line's
                //    price is the quote's own total — the figure the folio charges two statements below.
                'price_minor' => isset($line['price_minor']) && is_int($line['price_minor'])
                    ? $line['price_minor']
                    : (count($requested) === 1
                        ? (int) $quote->total_minor
                        : ((($lineUnitId !== null
                            ? NightlyRate::stayTotalForUnit((string) $line['rate_plan_id'], $lineUnitId, $checkIn, $checkOut)
                            : null) ?? $this->priceOver((string) $line['rate_plan_id'], $checkIn, $checkOut)) ?? 0)),
                'currency' => $quote->currency,
            ];
        }

        try {
            $reservation = $this->assignment->createBooking([
                'property_id' => $quote->property_id,
                'currency' => $quote->currency,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'lead_guest_id' => $guest->getKey(),
                'source' => 'direct',
                'adults' => $quote->adults,
                'children' => $quote->children,
                ...($terms !== null ? ['terms_accepted_at' => now(), 'terms_version' => $terms['version'], 'terms_snapshot' => $terms] : []),
            ], $lines);
        } catch (BookingException) {
            // Someone took the last unit between hold and book — fail gracefully, never overbook.
            return ['status' => 'unavailable', 'reservation' => null, 'payment' => null, 'folio' => null, 'client_secret' => null];
        } finally {
            // ⚠️⚠️ D-STOREFRONT-ORPHAN-GUEST (2026-11-11, his "do all"): A REFUSED BOOKING LEAVES NO GUEST BEHIND — the same rule the New
            //    reservation wizard has had since D-NEWRES-ORPHAN-GUEST (2026-10-04). The guest is created above, before the booking is
            //    tried; when the unit was just taken (or anything else stops the booking) that record — a name, and the email in its
            //    encrypted column and its blind-index hash — stayed with no stay at all, from a visitor's own form. It was made a moment
            //    ago by a plain insert (this method never matches a returning guest), so it is removed outright — never soft-deleted with
            //    its details kept (#28).
            //    ⚠️ ONLY IF NO BOOKING POINTS AT IT: createBooking() commits, THEN records its events and audit row — a failure there leaves a
            //    saved booking and no `$reservation` here, and deleting its guest would null that booking's lead guest (nullOnDelete).
            if (! isset($reservation) && $guest->wasRecentlyCreated
                && ! Reservation::withTrashed()->where('lead_guest_id', $guest->getKey())->exists()) {
                $guest->forceDelete();
            }
        }

        $folio = $this->folios->openFolio($reservation);
        $this->folios->postCharge($folio, 'room', 'Room ('.$quote->reference.')', $total);

        // #17 — post the property's REAL non-optional fees (e.g. the cleaning fee) + taxes onto the
        // folio, mirroring the operator New Reservation. recompute() re-derives total_minor from the
        // ledger, so the folio total is the TRUE total; the storefront shows exactly this (no €45 stub,
        // no fee/tax silently dropped). A property with no fees/taxes posts nothing (harmless no-op).
        $nights = $this->nights($checkIn, $checkOut);
        $persons = max(1, (int) $quote->adults + (int) $quote->children);
        PropertyCharges::postFees($this->folios, $folio, (string) $quote->property_id, $total, $nights, $persons, (string) $quote->currency);
        PropertyCharges::postTaxes($this->folios, $folio, (string) $quote->property_id, $total, $nights, $persons, (string) $quote->currency);

        // OWNER ATTRIBUTION — after the folio is complete, never before. Attribution reads the folio's room
        // revenue and `createBooking()` does not post it, so attributing earlier would bank a zero and latch.
        // Idempotent; a no-op for operator-owned inventory (see OwnerAttributionService::attributeReservation).
        app(OwnerAttributionService::class)->attributeReservation($reservation->refresh());

        // The TRUE amount owed after fees + taxes (nothing paid yet → balance == total).
        $balanceMinor = FolioTotals::for($folio->refresh())['balance_minor'];

        // Settle the FULL balance inline unless the caller defers to the hosted-Stripe rail. Paying the
        // full balance (not just the room) keeps payment_status == 'paid' now that fees/taxes are posted.
        //
        // ⚠️⚠️ VY-01 (audit of 2026-11-24, reproduced; P0) — AND NEVER THROUGH THE MANUAL GATEWAY. The manual gateway approves every
        //    charge: it models money a member of STAFF took outside any gateway (cash, a bank transfer) and is told about. This method is
        //    the PUBLIC booking engine — its caller is a visitor — so handing it the manual gateway recorded a `succeeded` payment for the
        //    whole stay on a visitor's own word: the reservation read `paid`, the folio balance 0, and the booking passed the collection
        //    gate towards an owner's payout. With no card gateway the booking is made with its balance DUE (pay at the property), exactly
        //    like the web checkout's "pay on arrival". A manual payment is recorded only by signed-in staff (`payments.record`).
        //    ⚠️ The guard is HERE, in the service, so every caller is covered — the JSON endpoint, the web checkout and any future one.
        $payment = $takePayment && $this->takesCardPayments()
            ? $this->folios->recordPayment($folio, Money::of($balanceMinor, (string) $quote->currency), $paymentToken, ['booking_quote_id' => $quote->getKey()])
            : null;

        $quote->update([
            'status' => BookingQuote::STATUS_CONVERTED,
            'converted_reservation_id' => $reservation->getKey(),
        ]);

        /** @var array<string, mixed> $paymentMetadata */
        $paymentMetadata = $payment instanceof Payment ? ($payment->metadata ?? []) : [];

        return [
            'status' => 'confirmed',
            'reservation' => $reservation->refresh(),
            'payment' => $payment,
            'folio' => $folio->refresh(),
            'client_secret' => is_string($paymentMetadata['client_secret'] ?? null) ? $paymentMetadata['client_secret'] : null,
        ];
    }

    /**
     * VY-15 (audit of 2026-11-24, reproduced) — the three things the storefront never OFFERS and therefore must never ACCEPT:
     * a stay whose arrival day has passed AT THE PROPERTY, more adults or children than the room type takes, and a rate plan that is
     * switched off. The search already hides all three; this is the same rule on the write side.
     *
     * @throws StayNotBookableException
     */
    private function assertBookable(Property $property, RoomType $roomType, RatePlan $ratePlan, string $checkIn, int $adults, int $children): void
    {
        if ($this->hasPassed($property, $checkIn)) {
            throw new StayNotBookableException('check_in', __('php/services/booking_engine_service.stay_in_past'));
        }

        $maxAdults = (int) $roomType->max_adults;
        $maxChildren = (int) $roomType->max_children;
        if ($adults > $maxAdults || $children > $maxChildren) {
            throw new StayNotBookableException(
                $adults > $maxAdults ? 'adults' : 'children',
                __('php/services/booking_engine_service.too_many_guests', ['adults' => $maxAdults, 'children' => $maxChildren]),
            );
        }

        if (! $ratePlan->is_active) {
            throw new StayNotBookableException('rate_plan_id', __('php/services/booking_engine_service.plan_unavailable'));
        }
    }

    /**
     * The same rules for a quote that is about to become a booking, read from the quote's own lines. A line whose room type or rate
     * plan no longer exists is left to the core (it refuses it); a quote for another property never reaches here (the controller's
     * lookup is property-scoped).
     *
     * @throws StayNotBookableException
     */
    private function assertQuoteStillBookable(BookingQuote $quote): void
    {
        $property = Property::query()->find($quote->property_id);
        if (! $property instanceof Property) {
            return;
        }
        $checkIn = CarbonImmutable::parse($quote->check_in)->toDateString();

        /** @var list<array<string, mixed>> $requested */
        $requested = $quote->requested;
        foreach ($requested as $line) {
            $roomType = RoomType::query()->where('property_id', $property->getKey())->find((string) ($line['room_type_id'] ?? ''));
            $ratePlan = $roomType instanceof RoomType
                ? RatePlan::query()->where('room_type_id', $roomType->getKey())->find((string) ($line['rate_plan_id'] ?? ''))
                : null;
            if ($roomType instanceof RoomType && $ratePlan instanceof RatePlan) {
                $this->assertBookable($property, $roomType, $ratePlan, $checkIn, (int) $quote->adults, (int) $quote->children);
            }
        }
    }

    /**
     * Has this arrival day already passed AT THE PROPERTY? The property's own day ({@see BoardDate::today()}, the rule the Calendar and
     * the Front Desk use) — a guest booking tonight in Baku is not refused because the server's day has not rolled, and the reverse.
     */
    public function hasPassed(Property $property, string $checkIn): bool
    {
        return CarbonImmutable::parse($checkIn)->toDateString() < BoardDate::today($property->timezone)->toDateString();
    }

    /**
     * VY-01 — does the public booking path have a gateway that REALLY charges a card? Only then may it record a payment. Read from the
     * resolved {@see PaymentGateway} (the one FolioService would charge through), never from a second copy of the config rule.
     */
    public function takesCardPayments(): bool
    {
        return app(PaymentGateway::class)->name() !== 'manual';
    }

    /**
     * Mark held/quoted quotes past their expiry as abandoned (recovery target). Does NOT touch
     * inventory — advisory holds never held any. Returns the number swept.
     */
    public function expireStaleQuotes(): int
    {
        return BookingQuote::query()
            ->whereIn('status', ['quoted', 'held'])
            ->where('expires_at', '<', now())
            ->update(['status' => BookingQuote::STATUS_ABANDONED, 'recovery_status' => 'pending']);
    }

    private function minAvailable(string $roomTypeId, string $checkIn, string $checkOut): int
    {
        $range = $this->availability->availabilityRange($roomTypeId, $checkIn, $checkOut);

        return $range === [] ? 0 : (int) min($range);
    }

    /**
     * @return array{rate_plan_id: string, currency: string, total_minor: int}|null
     */
    private function cheapestRatePlan(RoomType $roomType, string $checkIn, string $checkOut): ?array
    {
        $best = null;
        foreach (RatePlan::query()->where('room_type_id', $roomType->getKey())->where('is_active', true)->get() as $ratePlan) {
            $total = $this->priceOver($ratePlan->getKey(), $checkIn, $checkOut);
            if ($total === null) {
                continue;
            }
            if ($best === null || $total < $best['total_minor']) {
                $best = ['rate_plan_id' => $ratePlan->getKey(), 'currency' => (string) $ratePlan->currency, 'total_minor' => $total];
            }
        }

        return $best;
    }

    /**
     * Sum nightly prices for a rate plan across the stay; null if ANY night is unpriced (can't
     * sell a partially-priced stay).
     */
    /**
     * The total price of a rate plan over a stay (minor units), or null if ANY night is unpriced. Public so the
     * guest date-change quote (Phase 4b) can price a proposed new stay through the SAME rate path the booking
     * engine uses — a read-only, informational figure.
     */
    public function priceOver(string $ratePlanId, string $checkIn, string $checkOut): ?int
    {
        $start = CarbonImmutable::parse($checkIn)->startOfDay();
        $end = CarbonImmutable::parse($checkOut)->startOfDay();

        // ⚠️ PLAIN COLUMN, NOT whereDate() (audit P-3, fixed 2026-08-31). `rate_calendar.date` is a DATE
        //    column, so `DATE(date)` was the identity function — it changed nothing about what matched and
        //    cost the index range: EXPLAIN went `ref`/206 rows (every row of the plan, filtered afterwards)
        //    where it now goes `range`/14. This is the read the PUBLIC booking page waits on.
        //    ⚠️ The compared values are unchanged — still date STRINGS built before the query — so no day
        //    boundary moves. RateCalendarDateIndexTest proves that in zones 14h ahead and 11h behind UTC.
        $prices = RateCalendar::query()
            ->where('rate_plan_id', $ratePlanId)
            ->where('date', '>=', $start->toDateString())
            ->where('date', '<', $end->toDateString())
            ->get()
            ->keyBy(fn (RateCalendar $r): string => CarbonImmutable::parse($r->date)->toDateString());

        $total = 0;
        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $row = $prices->get($date->toDateString());
            if ($row === null) {
                return null;
            }
            $total += (int) $row->price_minor;
        }

        return $total;
    }

    private function nights(string $checkIn, string $checkOut): int
    {
        return (int) CarbonImmutable::parse($checkIn)->startOfDay()->diffInDays(CarbonImmutable::parse($checkOut)->startOfDay());
    }
}
