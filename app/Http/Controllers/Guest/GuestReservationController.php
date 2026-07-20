<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Support\Guest\GuestReservationView;
use App\Support\Guest\GuestTrip;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Guest Reservation detail (Phase 4b · Sub-gate 2) — one of the authed guest's OWN bookings.
 *
 * 🔒 GUEST ISOLATION: the {reservation} is resolved THROUGH `$this->guest()->reservations()->findOrFail()`
 * (`lead_guest_id` + the guest's own org) — NEVER implicit route-model binding, which would resolve any
 * reservation in the guest's org (other guests share it). A foreign booking id simply doesn't resolve → 404.
 * Charges/balance via the shared {@see GuestReservationView} (reconciling to the folio); the header reuses the
 * same {@see GuestTrip} card as Trips (no drift).
 */
class GuestReservationController extends Controller
{
    use ResolvesGuest;

    public function show(string $reservation): View
    {
        $model = $this->guest()->reservations()->with('property')->findOrFail($reservation);

        return view('guest.reservation', [
            'trip' => GuestTrip::card($model, CarbonImmutable::today()),
            'folio' => GuestReservationView::folio($model),
            'timeline' => GuestReservationView::timeline($model),
            'adults' => (int) $model->adults,
            'children' => (int) $model->children,
        ]);
    }
}
