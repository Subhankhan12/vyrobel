<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Support\Money\MoneyFormatter;

/**
 * Guest change REQUESTS (Phase 4b · Sub-gate 3) — the guest side NEVER writes the operator's domain.
 *
 * 🪤 A guest changing dates, adding extras, or cancelling touches inventory / the folio / availability / money, so
 * every guest action here only records a `pending` {@see GuestChangeRequest}. The guest does NOT move the booking,
 * charge the folio, or refund. The operator reviews the request and, on confirmation, runs the REAL change with
 * their certified tools ({@see AssignmentService::cancelReservation()}, the reservations screen, the folio) — that
 * execute is the operator's action, deferred honestly here to their side.
 *
 * The date-change quote is REAL + read-only: it prices the proposed new stay through the SAME rate path the
 * booking engine uses ({@see BookingEngineService::priceOver()}) — informational, not a charge.
 */
class GuestChangeRequestService
{
    public function __construct(private BookingEngineService $engine) {}

    /**
     * A real, read-only price-difference quote for a proposed new stay. `priced` is false when the new dates have
     * no published rate — the guest is told the operator will confirm the price, never a fabricated number.
     *
     * @return array{priced: bool, current: string, new: string|null, difference: string|null, difference_minor: int|null}
     */
    public function quoteDateChange(Reservation $reservation, string $newCheckIn, string $newCheckOut): array
    {
        $currency = (string) $reservation->currency;
        $currentMinor = (int) $reservation->total_minor;

        $ratePlanId = $reservation->reservationUnits()->whereNotNull('rate_plan_id')->value('rate_plan_id');
        $newMinor = is_string($ratePlanId) && $ratePlanId !== ''
            ? $this->engine->priceOver($ratePlanId, $newCheckIn, $newCheckOut)
            : null;

        return [
            'priced' => $newMinor !== null,
            'current' => MoneyFormatter::display($currentMinor, $currency),
            'new' => $newMinor !== null ? MoneyFormatter::display($newMinor, $currency) : null,
            'difference' => $newMinor !== null ? MoneyFormatter::display($newMinor - $currentMinor, $currency, signed: true) : null,
            'difference_minor' => $newMinor !== null ? $newMinor - $currentMinor : null,
        ];
    }

    public function requestDateChange(Reservation $reservation, Guest $guest, string $newCheckIn, string $newCheckOut): GuestChangeRequest
    {
        return $this->create($reservation, $guest, GuestChangeRequest::TYPE_DATE_CHANGE, [
            'new_check_in' => $newCheckIn,
            'new_check_out' => $newCheckOut,
            'quote' => $this->quoteDateChange($reservation, $newCheckIn, $newCheckOut),
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $addOns
     */
    public function requestAddOns(Reservation $reservation, Guest $guest, array $addOns): GuestChangeRequest
    {
        return $this->create($reservation, $guest, GuestChangeRequest::TYPE_ADD_ON, ['add_ons' => array_values(array_map('strval', $addOns))]);
    }

    public function requestCancellation(Reservation $reservation, Guest $guest, string $reason): GuestChangeRequest
    {
        return $this->create($reservation, $guest, GuestChangeRequest::TYPE_CANCELLATION, ['reason' => $reason]);
    }

    /**
     * OPERATOR side — status ONLY. This does NOT execute the real change: the operator runs the actual
     * date-change / cancel-and-refund / folio charge with their certified tools; this records the decision.
     */
    public function confirm(GuestChangeRequest $request): void
    {
        $request->update(['status' => 'confirmed']);
    }

    public function decline(GuestChangeRequest $request): void
    {
        $request->update(['status' => 'declined']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function create(Reservation $reservation, Guest $guest, string $type, array $payload): GuestChangeRequest
    {
        // organization_id is auto-filled from the tenant context (SetTenantForGuestRequest = the guest's own org).
        return GuestChangeRequest::create([
            'reservation_id' => $reservation->getKey(),
            'guest_id' => $guest->getKey(),
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }
}
