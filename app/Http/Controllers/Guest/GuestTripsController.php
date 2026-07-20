<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Guest;
use App\Models\Reservation;
use App\Support\Guest\GuestTrip;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

/**
 * Guest Trips (Phase 4b · Sub-gate 1) — the post-login landing: the authed guest's OWN stays, upcoming + past.
 *
 * 🔒 GUEST ISOLATION: the trips come from `$this->guest()->reservations()` — the authed guest's own lead bookings
 * ({@see Guest::reservations()} = `lead_guest_id`), never a request id — so another guest's bookings
 * can never appear. Amounts + status via the shared {@see GuestTrip} (reconciling to the folio).
 */
class GuestTripsController extends Controller
{
    use ResolvesGuest;

    public function index(): View
    {
        $today = CarbonImmutable::today();

        $cards = $this->guest()->reservations()
            ->with('property')
            ->orderByDesc('check_in')
            ->get()
            ->map(fn (Reservation $reservation): array => GuestTrip::card($reservation, $today));

        return view('guest.trips', [
            'upcoming' => $cards->reject(fn (array $c): bool => $c['is_past'])->values()->all(),
            'past' => $cards->filter(fn (array $c): bool => $c['is_past'])->values()->all(),
        ]);
    }
}
