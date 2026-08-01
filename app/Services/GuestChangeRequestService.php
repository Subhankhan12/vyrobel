<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Operator\ReservationCreateController;
use App\Models\AddOn;
use App\Models\Guest;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Models\Tax;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationExtras;
use Carbon\CarbonImmutable;

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
    public function __construct(private BookingEngineService $engine, private FolioService $folios) {}

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
     * A guest add-on request stores the chosen REAL AddOn ids (server-validated to the reservation's property).
     * The quantities + prices are NOT trusted from the guest — they are re-derived server-side at confirm time.
     *
     * @param  array<array-key, mixed>  $addOnIds
     */
    public function requestAddOns(Reservation $reservation, Guest $guest, array $addOnIds): GuestChangeRequest
    {
        return $this->create($reservation, $guest, GuestChangeRequest::TYPE_ADD_ON, [
            'add_on_ids' => array_values(array_unique(array_map('strval', $addOnIds))),
        ]);
    }

    public function requestCancellation(Reservation $reservation, Guest $guest, string $reason): GuestChangeRequest
    {
        return $this->create($reservation, $guest, GuestChangeRequest::TYPE_CANCELLATION, ['reason' => $reason]);
    }

    /**
     * OPERATOR side — records the decision. For a DATE-CHANGE / CANCELLATION this is status ONLY: the operator
     * runs the actual move / cancel-and-refund with their certified tools. For an ADD-ON request (founder
     * decision D4), confirming POSTS the real folio lines — the SAME certified path the operator New Reservation
     * uses (re-read scoped+active AddOns, server-derived quantity via {@see ReservationExtras}, a separate VAT
     * line on the taxable base) — so the extra genuinely "settles to the booking", reconciling #17. Guarded on
     * the pending→confirmed transition so a double-confirm never double-posts.
     */
    public function confirm(GuestChangeRequest $request): void
    {
        if ((string) $request->status !== 'pending') {
            return;   // already decided — never re-post the folio
        }

        if ((string) $request->type === GuestChangeRequest::TYPE_ADD_ON) {
            $this->postAddOnsToFolio($request);
        }

        $request->update(['status' => 'confirmed']);
    }

    public function decline(GuestChangeRequest $request): void
    {
        if ((string) $request->status !== 'pending') {
            return;
        }
        $request->update(['status' => 'declined']);
    }

    /**
     * Post the confirmed add-ons onto the reservation's folio — mirrors {@see ReservationCreateController}::postExtras
     * EXACTLY (server-authoritative re-read, per-basis quantity, a separate VAT line on the taxable base only),
     * so the guest-requested extra reconciles to the cent (#17) and the two surfaces cannot drift.
     */
    private function postAddOnsToFolio(GuestChangeRequest $request): void
    {
        $reservation = $request->reservation;
        if (! $reservation instanceof Reservation) {
            return;
        }

        $ids = array_values(array_filter(array_map('strval', (array) ($request->payload['add_on_ids'] ?? []))));
        if ($ids === []) {
            return;
        }

        $currency = (string) $reservation->currency;
        $propertyId = (string) $reservation->property_id;
        $guests = max(1, (int) $reservation->adults + (int) $reservation->children);
        $nights = max(1, (int) CarbonImmutable::parse($reservation->check_in)->diffInDays(CarbonImmutable::parse($reservation->check_out)));

        $folio = $this->folios->openFolio($reservation);   // idempotent (firstOrCreate)

        $addOns = AddOn::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get();

        $taxableMinor = 0;
        foreach ($addOns as $addOn) {
            if ((string) $addOn->currency !== $currency) {
                continue;   // never mix currencies on one folio
            }
            $quantity = ReservationExtras::quantityFor($addOn, $guests, $nights);
            $this->folios->postAddOn($folio, $addOn, $quantity);
            if ((bool) $addOn->taxable) {
                $taxableMinor += (int) $addOn->price_minor * $quantity;
            }
        }

        // A separate VAT line on the taxable EXTRAS base only — the stay's per-night/per-person taxes were
        // already posted at booking, so re-posting them would double-charge. Percentage-exclusive taxes only.
        if ($taxableMinor > 0) {
            foreach (Tax::query()->where('property_id', $propertyId)->get() as $tax) {
                $charged = (string) $tax->mode === 'percentage' && ! (bool) $tax->inclusive
                    && ($tax->currency === null || (string) $tax->currency === $currency);
                if ($charged) {
                    $this->folios->applyTax($folio, $tax, Money::of($taxableMinor, $currency), $nights, $guests);
                }
            }
        }
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
