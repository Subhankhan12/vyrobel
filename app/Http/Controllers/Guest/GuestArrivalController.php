<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Services\PdfService;
use App\Support\Guest\ArrivalPack;
use App\Support\Pdf\ArrivalPackDocument;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

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
            'reservationId' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? 'Your stay'),
            'firstName' => (string) ($this->guest()->first_name ?: 'there'),
            'checkIn' => CarbonImmutable::parse($model->check_in)->format('D, j M Y'),
            'pack' => ArrivalPack::for($model, CarbonImmutable::today()),
        ]);
    }

    /**
     * The printable Arrival Guide (Guest Portal · GATE 2) — a real PDF export of this owned booking's arrival
     * info. Resolved through the guest's OWN relation (foreign id = 404), rendered from the SAME window-gated
     * {@see ArrivalPack} as the screen + the #83 email (no drift). A pre-window PDF omits the door code (#33).
     */
    public function pdf(string $reservation, PdfService $pdf): Response
    {
        $model = $this->guest()->reservations()
            ->with(['property', 'reservationUnits.unit', 'leadGuest'])
            ->findOrFail($reservation);

        return $pdf->download(
            'pdfs.arrival',
            ArrivalPackDocument::data($model, CarbonImmutable::today()),
            'arrival-guide-'.$model->reference.'.pdf',
        );
    }
}
