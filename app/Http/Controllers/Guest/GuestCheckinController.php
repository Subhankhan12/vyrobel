<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\Reservation;
use App\Support\Guest\GuestName;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Guest online check-in (Phase 4b · Sub-gate 4) — the GUEST side: review the details we hold, confirm them, and
 * submit a pre-check-in. This stamps `reservations.guest_checked_in_at` — a submitted INTENT.
 *
 * ⚠️ This is NOT the operator's authoritative check-in. The front desk still performs that ({@see
 * \App\Services\FrontDeskService::checkIn()} → `status = 'checked_in'`, where the real arrival guards run); the
 * guest confirming their details never moves the booking's status. The two are deliberately separate columns.
 *
 * 🪤 PASSPORT / ID capture is ACCOUNT-GATED KYC and is NOT built here — an ID scan needs an external verification
 * provider. The screen says so honestly; no passport image is captured, stored, or "verified".
 *
 * 🔒 GUEST ISOLATION: the booking resolves THROUGH `$this->guest()->reservations()` — a foreign id → 404.
 */
class GuestCheckinController extends Controller
{
    use ResolvesGuest;

    public function show(string $reservation): View
    {
        $model = $this->owned($reservation);
        $today = CarbonImmutable::today();

        return view('guest.checkin', [
            'reservation' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? 'Your stay'),
            'guestName' => GuestName::display($this->guest()),   // SELF — the authed guest's own name
            'checkIn' => CarbonImmutable::parse($model->check_in)->format('D, j M Y'),
            'checkOut' => CarbonImmutable::parse($model->check_out)->format('D, j M Y'),
            'nights' => CarbonImmutable::parse($model->check_in)->diffInDays(CarbonImmutable::parse($model->check_out)),
            'adults' => (int) $model->adults,
            'children' => (int) $model->children,
            'checkinable' => $this->checkinable($model, $today),
            'submitted' => $model->guest_checked_in_at !== null,
        ]);
    }

    public function submit(string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);

        // Only for a live, non-departed booking; and idempotent (a second submit is a no-op, not a re-stamp).
        if ($this->checkinable($model, CarbonImmutable::today()) && $model->guest_checked_in_at === null) {
            $model->forceFill(['guest_checked_in_at' => now()])->save();
        }

        return redirect()->route('guest.checkin', $model->getKey())
            ->with('status', 'Thanks — your details are confirmed. We’ll have everything ready for your arrival.');
    }

    /** The authed guest's OWN reservation, or 404 — resolved through their own relation (never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->with('property')->findOrFail($reservation);
    }

    /** Checkinable = not cancelled/no-show and not already departed (arrival still ahead of / on the departure day). */
    private function checkinable(Reservation $reservation, CarbonImmutable $today): bool
    {
        if (in_array((string) $reservation->status, ReservationStage::CANCELLED_STATUSES, true)) {
            return false;
        }

        return CarbonImmutable::parse($reservation->check_out)->startOfDay()->greaterThanOrEqualTo($today->startOfDay());
    }
}
