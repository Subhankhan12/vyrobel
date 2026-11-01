<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\AddOn;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Models\Tax;
use App\Support\I18n\LocalDate;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationExtras;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Guest Add-ons — "Enhance your stay" (Guest Portal · GATE 2). The REAL org+property-scoped {@see AddOn}
 * catalogue (never the wireframe's invented 6), priced per {@see ReservationExtras} basis and shown
 * VAT-inclusive so what the guest sees === the true folio delta on confirmation (#17). Guest-scoped: the
 * booking resolves through the authed guest's OWN relation (foreign id = 404, #28).
 *
 * WRITE MODEL (founder decision D4): the form POSTs the chosen REAL AddOn ids to the certified
 * add-on-REQUEST path (`guest.manage.add-ons` → a pending {@see GuestChangeRequest}); the operator confirms
 * and the SAME certified folio posting runs — nothing settles until the host confirms. The guest never writes
 * the operator's folio directly.
 */
class GuestExtrasController extends Controller
{
    use ResolvesGuest;

    public function show(string $reservation): View
    {
        $model = $this->owned($reservation);
        $currency = (string) $model->currency;
        $guests = max(1, (int) $model->adults + (int) $model->children);
        $nights = max(1, (int) CarbonImmutable::parse($model->check_in)->diffInDays(CarbonImmutable::parse($model->check_out)));
        $vatRate = $this->vatRate((string) $model->property_id, $currency);

        $catalog = AddOn::query()
            ->where('property_id', $model->property_id)
            ->where('is_active', true)
            ->where('currency', $currency)
            ->orderBy('name')
            ->get()
            ->map(function (AddOn $addOn) use ($guests, $nights, $vatRate, $currency): array {
                $quantity = ReservationExtras::quantityFor($addOn, $guests, $nights);
                $net = (int) $addOn->price_minor * $quantity;
                $vat = (bool) $addOn->taxable ? (int) round($net * $vatRate / 100) : 0;
                $gross = $net + $vat;

                return [
                    'id' => (string) $addOn->getKey(),
                    'name' => (string) $addOn->name,
                    'basis' => ReservationExtras::basisLabel($addOn),
                    'unit_price' => MoneyFormatter::display((int) $addOn->price_minor, $currency),
                    'total' => MoneyFormatter::display($gross, $currency),
                    'total_minor' => $gross,
                    'taxable' => (bool) $addOn->taxable,
                ];
            })->all();

        return view('guest.extras', [
            'reservationId' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? __('php/http/controllers/guest/guest_extras_controller.your_stay')),
            'currency' => $currency,
            'vatRate' => $vatRate,
            'catalog' => $catalog,
            'pending' => $this->pendingAddOns($model),
        ]);
    }

    /** The property's effective add-on VAT rate = the sum of its charged (exclusive) percentage taxes. */
    private function vatRate(string $propertyId, string $currency): float
    {
        $rate = 0.0;
        foreach (Tax::query()->where('property_id', $propertyId)->get() as $tax) {
            $charged = (string) $tax->mode === 'percentage' && ! (bool) $tax->inclusive
                && ($tax->currency === null || (string) $tax->currency === $currency);
            if ($charged) {
                $rate += (float) $tax->rate;
            }
        }

        return $rate;
    }

    /**
     * The guest's own add-on requests for this booking (pending awaiting confirmation, or confirmed).
     *
     * @return list<array{status: string, names: list<string>, at: ?string}>
     */
    private function pendingAddOns(Reservation $reservation): array
    {
        return GuestChangeRequest::query()
            ->where('reservation_id', $reservation->getKey())
            ->where('type', GuestChangeRequest::TYPE_ADD_ON)
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest()
            ->get()
            ->map(fn (GuestChangeRequest $request): array => [
                'status' => (string) $request->status,
                'names' => $this->addOnNames($reservation, $request),
                'at' => $request->created_at?->translatedFormat(LocalDate::pattern('j M')),
            ])
            ->all();
    }

    /** @return list<string> */
    private function addOnNames(Reservation $reservation, GuestChangeRequest $request): array
    {
        $ids = array_values(array_filter(array_map('strval', (array) ($request->payload['add_on_ids'] ?? []))));
        if ($ids === []) {
            return [];
        }

        return AddOn::query()
            ->where('property_id', $reservation->property_id)
            ->whereIn('id', $ids)
            ->pluck('name')
            ->map('strval')
            ->all();
    }

    /** The authed guest's OWN reservation, or 404 (resolved through their own relation — never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->with('property')->findOrFail($reservation);
    }
}
