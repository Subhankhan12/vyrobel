<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Folio;
use App\Models\Payment;
use App\Models\Reservation;
use App\Support\Guest\GuestReservationView;
use App\Support\Guest\GuestTrip;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Guest Folio Receipt (Phase 4c · Sub-gate 1) — one owned booking's folio (charges / payments / balance) plus the
 * downloadable money documents. The on-screen figures come from the shared {@see GuestReservationView} (the same
 * folio the SG2 reservation screen shows, reconciling). A Refund Receipt / Credit Note is offered ONLY when a real
 * refund exists on the booking — never fabricated.
 *
 * 🔒 ISOLATION: the booking resolves THROUGH `$this->guest()->reservations()` — a foreign id → 404.
 */
class GuestReceiptController extends Controller
{
    use ResolvesGuest;

    public function show(string $reservation): View
    {
        $model = $this->owned($reservation);

        return view('guest.receipt', [
            'reservation' => (string) $model->getKey(),
            'trip' => GuestTrip::card($model, CarbonImmutable::today()),
            'folio' => GuestReservationView::folio($model),
            'hasRefund' => $this->hasRefund($model),
        ]);
    }

    /** The authed guest's OWN reservation, or 404 — resolved through their own relation (never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->with('property')->findOrFail($reservation);
    }

    /** True when the booking's folio carries a succeeded refund — the only time a refund/credit document is real. */
    private function hasRefund(Reservation $reservation): bool
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->with('payments')->first();

        return $folio !== null && $folio->payments->contains(
            fn (Payment $p): bool => (string) $p->type === 'refund' && (string) $p->status === 'succeeded'
        );
    }
}
