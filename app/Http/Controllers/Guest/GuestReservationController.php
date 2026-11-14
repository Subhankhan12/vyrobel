<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Support\Guest\ArrivalPack;
use App\Support\Guest\GuestReservationView;
use App\Support\Guest\GuestTrip;
use App\Support\I18n\LocalDate;
use App\Support\Reservations\CancellationPolicy;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Guest Reservation detail (Phase 4b · Sub-gate 2; guest Phase-4 pixel-diff) — one of the authed guest's OWN
 * bookings, rebuilt to the wireframe: a status hero + 4 quick-stat tiles, a balance panel, arrival info, add-ons,
 * cancellation policy, message + receipt. All REAL over the shared, certified backend.
 *
 * 🔒 GUEST ISOLATION: the {reservation} is resolved THROUGH `$this->guest()->reservations()->findOrFail()`
 * (`lead_guest_id` + the guest's own org) — NEVER implicit route-model binding, which would resolve any
 * reservation in the guest's org (other guests share it). A foreign booking id simply doesn't resolve → 404.
 * Charges/balance via {@see GuestReservationView} (reconciling to the folio); the header reuses the same
 * {@see GuestTrip} card as Trips (no drift). Arrival details via {@see ArrivalPack} — which SELF-GATES the door
 * code to the release window (#33): a pre-window reservation carries no code, so the view can't leak it.
 */
class GuestReservationController extends Controller
{
    use ResolvesGuest;

    public function show(string $reservation): View
    {
        $today = CarbonImmutable::today();
        $model = $this->guest()->reservations()->with('property')->findOrFail($reservation);

        $folio = GuestReservationView::folio($model);
        $freeUntil = CancellationPolicy::freeUntil($model);

        return view('guest.reservation', [
            'firstName' => Str::of((string) $this->guest()->first_name)->trim()->explode(' ')->first() ?: 'traveller',
            'trip' => GuestTrip::card($model, $today),
            'folio' => $folio,
            'timeline' => GuestReservationView::timeline($model),
            'adults' => (int) $model->adults,
            'children' => (int) $model->children,
            'guests' => max(1, (int) $model->adults + (int) $model->children),
            'checkInShort' => CarbonImmutable::parse($model->check_in)->translatedFormat(LocalDate::pattern('j M')),
            'checkOutShort' => CarbonImmutable::parse($model->check_out)->translatedFormat(LocalDate::pattern('j M')),
            // ArrivalPack self-gates the door code / wifi to the release window (#33) — safe to pass whole.
            'arrival' => ArrivalPack::for($model, $today),
            // Real free-cancellation deadline (null when the policy gives none / it has passed).
            'freeUntil' => $freeUntil !== null && $freeUntil->greaterThan($today) ? $freeUntil->translatedFormat(LocalDate::pattern('D, j M Y')) : null,
            // Actions only make sense for a live (not past/cancelled) stay.
            'active' => ! in_array(ReservationStage::for($model, $today), [ReservationStage::PAST, ReservationStage::CANCELLED], true),
            // BOOKING TERMS (2026-11-14): what this guest accepted when they booked (the stored snapshot — their own booking only, via the guest-scoped $model).
            'termsRecord' => $model,
        ]);
    }
}
