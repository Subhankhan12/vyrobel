<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\Booking\BookingException;
use App\Models\BookingQuote;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Support\Money\Money;
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
    public function quote(Property $property, string $roomTypeId, string $ratePlanId, string $checkIn, string $checkOut, int $adults, int $children, ?string $email = null, ?string $sessionToken = null): BookingQuote
    {
        $roomType = RoomType::query()->where('property_id', $property->getKey())->findOrFail($roomTypeId);
        $ratePlan = RatePlan::query()->where('room_type_id', $roomType->getKey())->findOrFail($ratePlanId);

        $totalMinor = $this->priceOver($ratePlan->getKey(), $checkIn, $checkOut);
        if ($totalMinor === null) {
            throw new \InvalidArgumentException('No published rate for the selected dates.');
        }

        $currency = (string) $ratePlan->currency;
        $total = Money::of($totalMinor, $currency);

        return BookingQuote::create([
            'property_id' => $property->getKey(),
            'reference' => 'Q-'.Str::upper(Str::random(8)),
            'session_token' => $sessionToken ?? (string) Str::uuid(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'requested' => [[
                'room_type_id' => $roomType->getKey(),
                'rate_plan_id' => $ratePlan->getKey(),
                'qty' => 1,
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
     * full). On success: seats, posts the room charge + payment through FolioService, and marks the
     * quote converted. The booking's domain events drive the Step 3 ARI push outward.
     *
     * @param  array{email?: string, first_name?: string, last_name?: string}  $guestData
     * @return array{status: string, reservation: ?Reservation, payment: ?Payment, client_secret: ?string}
     */
    public function book(BookingQuote $quote, string $paymentToken, array $guestData): array
    {
        if ($quote->status === BookingQuote::STATUS_CONVERTED) {
            return ['status' => 'already_converted', 'reservation' => $quote->convertedReservation, 'payment' => null, 'client_secret' => null];
        }

        $guest = Guest::create([
            'first_name' => $guestData['first_name'] ?? 'Guest',
            'last_name' => $guestData['last_name'] ?? '',
            'email' => $guestData['email'] ?? $quote->email,
        ]);

        $checkIn = CarbonImmutable::parse($quote->check_in)->toDateString();
        $checkOut = CarbonImmutable::parse($quote->check_out)->toDateString();
        $total = Money::of((int) $quote->total_minor, (string) $quote->currency);

        /** @var list<array<string, mixed>> $requested */
        $requested = $quote->requested;
        $lines = [];
        foreach ($requested as $line) {
            $lines[] = [
                'room_type_id' => $line['room_type_id'],
                'rate_plan_id' => $line['rate_plan_id'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'adults' => $quote->adults,
                'children' => $quote->children,
                'price_minor' => $this->priceOver((string) $line['rate_plan_id'], $checkIn, $checkOut) ?? 0,
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
            ], $lines);
        } catch (BookingException) {
            // Someone took the last unit between hold and book — fail gracefully, never overbook.
            return ['status' => 'unavailable', 'reservation' => null, 'payment' => null, 'client_secret' => null];
        }

        $folio = $this->folios->openFolio($reservation);
        $this->folios->postCharge($folio, 'room', 'Room ('.$quote->reference.')', $total);
        $payment = $this->folios->recordPayment($folio, $total, $paymentToken, ['booking_quote_id' => $quote->getKey()]);

        $quote->update([
            'status' => BookingQuote::STATUS_CONVERTED,
            'converted_reservation_id' => $reservation->getKey(),
        ]);

        /** @var array<string, mixed> $paymentMetadata */
        $paymentMetadata = $payment->metadata ?? [];

        return [
            'status' => 'confirmed',
            'reservation' => $reservation->refresh(),
            'payment' => $payment,
            'client_secret' => is_string($paymentMetadata['client_secret'] ?? null) ? $paymentMetadata['client_secret'] : null,
        ];
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
    private function priceOver(string $ratePlanId, string $checkIn, string $checkOut): ?int
    {
        $start = CarbonImmutable::parse($checkIn)->startOfDay();
        $end = CarbonImmutable::parse($checkOut)->startOfDay();

        $prices = RateCalendar::query()
            ->where('rate_plan_id', $ratePlanId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
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
