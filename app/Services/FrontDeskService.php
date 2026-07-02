<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\CheckOutReservation;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The front-desk STAFF ACTION LAYER (Step 8) — a thin assembly over already-built, already-proven
 * services. It owns NO inventory/payment/housekeeping logic of its own:
 *   - check-in / check-out delegate to the {@see CheckInReservation}/{@see CheckOutReservation}
 *     actions (pure state transitions — never AssignmentService);
 *   - room move delegates to {@see AssignmentService::assignUnit} (the ONLY inventory write path —
 *     it re-checks Level B on the target unit and frees the old);
 *   - payment collection delegates to {@see FolioService} (the single ledger recompute point,
 *     through whichever PaymentGateway is configured — Manual or Stripe).
 *
 * The only genuinely new code here is the read queries (today's arrivals / departures / in-house),
 * which are plain tenant-scoped reads over existing models.
 */
class FrontDeskService
{
    public function __construct(
        private CheckInReservation $checkIn,
        private CheckOutReservation $checkOut,
        private AssignmentService $assignment,
        private FolioService $folios,
    ) {}

    /**
     * Reservations due to arrive on $date (expected + already arrived), excluding cancelled/no-show.
     *
     * @return Collection<int, Reservation>
     */
    public function arrivals(string $propertyId, CarbonInterface|string $date): Collection
    {
        return Reservation::query()
            ->where('property_id', $propertyId)
            ->whereDate('check_in', CarbonImmutable::parse($date)->toDateString())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->with('leadGuest')
            ->get();
    }

    /**
     * Reservations due to depart on $date, excluding cancelled/no-show.
     *
     * @return Collection<int, Reservation>
     */
    public function departures(string $propertyId, CarbonInterface|string $date): Collection
    {
        return Reservation::query()
            ->where('property_id', $propertyId)
            ->whereDate('check_out', CarbonImmutable::parse($date)->toDateString())
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->with('leadGuest')
            ->get();
    }

    /**
     * Guests in-house on $date: checked in with a stay covering [check_in, check_out) ∋ $date.
     *
     * @return Collection<int, Reservation>
     */
    public function inHouse(string $propertyId, CarbonInterface|string $date): Collection
    {
        $day = CarbonImmutable::parse($date)->toDateString();

        return Reservation::query()
            ->where('property_id', $propertyId)
            ->where('status', 'checked_in')
            ->whereDate('check_in', '<=', $day)
            ->whereDate('check_out', '>', $day)
            ->with('leadGuest')
            ->get();
    }

    public function checkIn(Reservation $reservation): Reservation
    {
        return $this->checkIn->handle($reservation);
    }

    public function checkOut(Reservation $reservation): Reservation
    {
        return $this->checkOut->handle($reservation);
    }

    /**
     * Move a reservation line to a different physical unit — through the single write path, which
     * re-checks Level B on the target and frees the old unit (throws if the target is occupied).
     */
    public function moveRoom(ReservationUnit $reservationUnit, string $unitId): ReservationUnit
    {
        return $this->assignment->assignUnit($reservationUnit, $unitId);
    }

    /**
     * Collect a payment against the reservation's folio, through FolioService (ledger stays the
     * single recompute point) and the configured gateway. Token-only.
     */
    public function collectPayment(Reservation $reservation, Money $amount, ?string $cardToken = null): Payment
    {
        $folio = $this->folios->openFolio($reservation);

        return $this->folios->recordPayment($folio, $amount, $cardToken);
    }
}
