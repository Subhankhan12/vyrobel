<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Review;
use App\Support\Guest\GuestTrip;
use App\Support\Reservations\ReservationStage;
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

        $reservations = $this->guest()->reservations()->with('property')->orderByDesc('check_in')->get();

        // Which of the guest's own stays already have a review (one per stay) — to show "Review" vs "Reviewed".
        $reviewed = array_flip(Review::query()->whereIn('reservation_id', $reservations->pluck('id')->all())->pluck('reservation_id')->all());

        $cards = $reservations->map(function (Reservation $reservation) use ($today, $reviewed): array {
            $card = GuestTrip::card($reservation, $today);
            $isReviewed = isset($reviewed[(string) $reservation->getKey()]);
            $card['reviewed'] = $isReviewed;
            // Reviewable = a completed (PAST) stay not yet reviewed (never a cancelled/future stay).
            $card['reviewable'] = ! $isReviewed && ReservationStage::for($reservation, $today) === ReservationStage::PAST;

            return $card;
        });

        return view('guest.trips', [
            'upcoming' => $cards->reject(fn (array $c): bool => $c['is_past'])->values()->all(),
            'past' => $cards->filter(fn (array $c): bool => $c['is_past'])->values()->all(),
        ]);
    }
}
