<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\GuestDocument;
use App\Models\Reservation;
use App\Services\Guest\GuestIdDocumentService;
use App\Support\Guest\GuestName;
use App\Support\Reservations\ReservationStage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Guest online check-in (Guest Portal batch 2 · GATE 2) — the GUEST side: a 4-step wizard (Details → ID →
 * Arrival → Done) that submits a pre-check-in. It stamps `reservations.guest_checked_in_at` — a submitted
 * INTENT — plus a real arrival window (operator/front-desk-visible) and, optionally, a real ENCRYPTED ID
 * photo for the legal guest register.
 *
 * ⚠️ This is NOT the operator's authoritative check-in. The front desk still performs that ({@see
 * \App\Services\FrontDeskService::checkIn()} → `status = 'checked_in'`, where the real arrival guards run);
 * the guest confirming their details never moves the booking's status. The two are deliberately separate.
 *
 * 🔒 ID (founder decision D2): captured through {@see GuestIdDocumentService} — encrypted at rest, private
 * disk, register-only, `verification_status = 'pending'` (captured, NEVER a fabricated "verified"), owner
 * NEVER sees it (#28), and hard-deleted 30 days after checkout. Door-code delivery is BY EMAIL (the real
 * pre-arrival mail + the in-window ArrivalPack #33) — SMS is honestly deferred (no provider).
 *
 * 🔒 GUEST ISOLATION: the booking resolves THROUGH `$this->guest()->reservations()` — a foreign id → 404.
 */
class GuestCheckinController extends Controller
{
    use ResolvesGuest;

    private const MAX_ID_KB = 8192;   // 8 MB

    /** The coarse arrival windows the guest can pick (label keyed by the stored slot value). */
    public const ARRIVAL_WINDOWS = [
        '11-13' => '11:00–13:00',
        '13-15' => '13:00–15:00',
        '15-18' => '15:00–18:00',
        '18+' => 'After 18:00',
    ];

    public function show(string $reservation): View
    {
        $model = $this->owned($reservation);
        $guest = $this->guest();
        $today = CarbonImmutable::today();
        $checkIn = CarbonImmutable::parse($model->check_in);

        $idDoc = GuestDocument::query()
            ->where('guest_id', $guest->getKey())
            ->where('reservation_id', $model->getKey())
            ->latest()
            ->first();

        $guestsTotal = (int) $model->adults + (int) $model->children;

        return view('guest.checkin', [
            'reservation' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? 'Your stay'),
            'guestName' => GuestName::display($guest),          // SELF — the authed guest's own name
            'firstName' => (string) $guest->first_name,
            'lastName' => (string) $guest->last_name,
            'email' => (string) $guest->email,                   // read-only confirmation (managed in Account)
            'phone' => (string) $guest->phone,                   // editable (arrival contact — the guest's own)
            'checkInLabel' => $checkIn->format('D, j M Y'),
            'checkInShort' => $checkIn->format('j M'),
            'checkInDay' => $checkIn->format('D'),
            'checkInTime' => $this->timeLabel($model->property?->check_in_time),
            'checkOutLabel' => CarbonImmutable::parse($model->check_out)->format('D, j M Y'),
            'nights' => (int) $checkIn->diffInDays(CarbonImmutable::parse($model->check_out)),
            'adults' => (int) $model->adults,
            'children' => (int) $model->children,
            'guestsTotal' => $guestsTotal,
            'coGuests' => max(0, $guestsTotal - 1),              // count only — NO fabricated co-guest name (#28/#39)
            'daysToArrival' => max(0, (int) $today->diffInDays($checkIn->startOfDay(), false)),
            'arrivalWindow' => (string) ($model->arrival_window ?? ''),
            'arrivalWindows' => self::ARRIVAL_WINDOWS,
            'arrivalNote' => (string) ($model->notes_guest ?? ''),
            'idCaptured' => $idDoc !== null,
            'idType' => (string) ($idDoc->type ?? 'passport'),
            'idTypes' => GuestIdDocumentService::TYPES,
            'checkinable' => $this->checkinable($model, $today),
            'submitted' => $model->guest_checked_in_at !== null,
        ]);
    }

    public function submit(Request $request, string $reservation, GuestIdDocumentService $ids): RedirectResponse
    {
        $model = $this->owned($reservation);
        $guest = $this->guest();

        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:40'],
            'arrival_window' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::ARRIVAL_WINDOWS))],
            'arrival_note' => ['nullable', 'string', 'max:1000'],
            'id_type' => ['nullable', 'string', 'in:'.implode(',', GuestIdDocumentService::TYPES)],
            'id_photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp,heic', 'max:'.self::MAX_ID_KB],
        ]);

        // Only for a live, non-departed booking (idempotent — a second submit never re-stamps the intent).
        if (! $this->checkinable($model, CarbonImmutable::today())) {
            return redirect()->route('guest.checkin', $model->getKey())
                ->with('status', 'This booking can no longer be checked in online.');
        }

        // Update the guest's OWN arrival contact phone (self-service; encrypted PII, #28).
        $phone = trim((string) ($validated['phone'] ?? ''));
        if ($phone !== '' && $phone !== (string) $guest->phone) {
            $guest->forceFill(['phone' => $phone])->save();
        }

        // Real arrival window + the free-text arrival note (operator/front-desk-visible via the reservation).
        $model->forceFill([
            'arrival_window' => $validated['arrival_window'] ?? $model->arrival_window,
            'notes_guest' => array_key_exists('arrival_note', $validated) ? ($validated['arrival_note'] ?: null) : $model->notes_guest,
        ]);

        // Stamp the pre-check-in INTENT (never the authoritative status), once.
        if ($model->guest_checked_in_at === null) {
            $model->forceFill(['guest_checked_in_at' => now()]);
        }
        $model->save();

        // Optional ID capture — encrypted, register-only, pending (never "verified"), owner-never (#28).
        if ($request->hasFile('id_photo')) {
            $ids->store($guest, $model, $request->file('id_photo'), (string) ($validated['id_type'] ?? 'passport'));
        }

        return redirect()->route('guest.checkin', $model->getKey())
            ->with('status', 'You’re checked in. We’ll email your arrival details before you travel.');
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

    private function timeLabel(mixed $time): string
    {
        if (! is_string($time) || $time === '') {
            return '15:00';
        }

        try {
            return CarbonImmutable::parse($time)->format('H:i');
        } catch (\Throwable) {
            return '15:00';
        }
    }
}
