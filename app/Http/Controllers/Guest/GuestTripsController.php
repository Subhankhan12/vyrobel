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
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Guest Trips (Phase 4b · Sub-gate 1; guest Phase-4 pixel-diff G1) — the post-login landing: the authed guest's
 * OWN stays, with a travel-account HERO (real stats), Upcoming/Past/Cancelled tabs, search + year filter.
 *
 * 🔒 GUEST ISOLATION: the trips come from `$this->guest()->reservations()` — the authed guest's own lead bookings
 * ({@see Guest::reservations()} = `lead_guest_id`), never a request id — so another guest's bookings can never
 * appear. Amounts + status via the shared {@see GuestTrip} (reconciling to the folio). Every hero figure is REAL
 * (stays completed / nights away / countries visited from the guest's OWN non-cancelled stays — never fabricated
 * "369 stays"). Tabs + search + year are native GET (no Alpine in the guest realm — progressive enhancement).
 */
class GuestTripsController extends Controller
{
    use ResolvesGuest;

    /** @var list<string> */
    private const TABS = ['upcoming', 'past', 'cancelled'];

    public function index(Request $request): View
    {
        $today = CarbonImmutable::today();
        $guest = $this->guest();

        $reservations = $guest->reservations()->with('property')->orderByDesc('check_in')->get();
        $reviewed = array_flip(Review::query()->whereIn('reservation_id', $reservations->pluck('id')->all())->pluck('reservation_id')->all());

        $cards = $reservations->map(function (Reservation $reservation) use ($today, $reviewed): array {
            $card = GuestTrip::card($reservation, $today);
            $isReviewed = isset($reviewed[(string) $reservation->getKey()]);
            $card['reviewed'] = $isReviewed;
            $card['reviewable'] = ! $isReviewed && $card['stage'] === ReservationStage::PAST;

            return $card;
        });

        // Bucket by stage (upcoming = anything not past/cancelled).
        $buckets = [
            'upcoming' => $cards->reject(fn (array $c): bool => $c['is_past'])->values(),
            'past' => $cards->filter(fn (array $c): bool => $c['stage'] === ReservationStage::PAST)->values(),
            'cancelled' => $cards->filter(fn (array $c): bool => $c['stage'] === ReservationStage::CANCELLED)->values(),
        ];

        $tab = in_array($request->query('tab'), self::TABS, true) ? (string) $request->query('tab') : 'upcoming';
        $query = trim((string) $request->query('q', ''));
        $year = (string) $request->query('year', '');

        // Apply the search + year filter to the active tab's list.
        $list = $buckets[$tab]
            ->when($query !== '', fn ($c) => $c->filter(fn (array $t): bool => Str::contains(Str::lower($t['property'].' '.$t['location'].' '.$t['reference']), Str::lower($query))))
            ->when($year !== '', fn ($c) => $c->filter(fn (array $t): bool => $t['year'] === $year))
            ->values()->all();

        // REAL travel-account stats — the guest's OWN completed (past) stays only (a cancelled booking never happened).
        $completed = $buckets['past'];
        $countries = $completed->pluck('country')->filter()->unique()->count();

        return view('guest.trips', [
            'firstName' => Str::of((string) $guest->first_name)->trim()->explode(' ')->first() ?: 'traveller',
            'stats' => [
                'stays' => $completed->count(),
                'nights' => (int) $completed->sum('nights'),
                'countries' => $countries,
            ],
            'tab' => $tab,
            'counts' => ['upcoming' => $buckets['upcoming']->count(), 'past' => $buckets['past']->count(), 'cancelled' => $buckets['cancelled']->count()],
            'query' => $query,
            'year' => $year,
            'years' => $cards->pluck('year')->unique()->sort()->reverse()->values()->all(),
            'trips' => $list,
        ]);
    }
}
