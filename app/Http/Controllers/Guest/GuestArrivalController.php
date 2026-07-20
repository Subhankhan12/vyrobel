<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Support\Guest\ArrivalPack;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Guest Arrival Info (Phase 4b · Sub-gate 4) — the arrival pack for ONE owned booking: directions, check-in/out
 * times, door code, wifi, arrival notes.
 *
 * 🔒 DOOR-CODE ISOLATION (the sensitive one): the booking is resolved THROUGH `$this->guest()->reservations()`
 * (the guest's own relation — a foreign id is a 404, never route-model binding), and the code/wifi are gated a
 * SECOND time by the arrival window inside {@see ArrivalPack} (withheld until ~check-in, and after cancellation).
 * Leaking a door code to the wrong guest — or months early — is a physical-security issue, so both guards apply.
 * The unbacked pieces (a real code the operator hasn't set, local recommendations) are honest — never fabricated.
 */
class GuestArrivalController extends Controller
{
    use ResolvesGuest;

    public function show(string $reservation): View
    {
        $model = $this->guest()->reservations()
            ->with(['property', 'reservationUnits.unit'])
            ->findOrFail($reservation);

        return view('guest.arrival', [
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? 'Your stay'),
            'checkIn' => CarbonImmutable::parse($model->check_in)->format('D, j M Y'),
            'pack' => ArrivalPack::for($model, CarbonImmutable::today()),
        ]);
    }
}
