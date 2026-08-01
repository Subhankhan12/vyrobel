<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Services\GuestChangeRequestService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guest Manage Booking (Phase 4b · Sub-gate 3) — date change / add-ons / cancellation, all as operator-confirmed
 * REQUESTS. The guest side NEVER writes the operator's domain (inventory / folio / refund) and can't confirm its
 * own request — it only creates `pending` {@see GuestChangeRequest} rows; the operator confirms + executes.
 *
 * 🔒 GUEST ISOLATION: the {reservation} resolves THROUGH `$this->guest()->reservations()->findOrFail` (SG2
 * pattern) — a foreign booking → 404. The change requests shown/created are for that owned booking only.
 */
class GuestManageController extends Controller
{
    use ResolvesGuest;

    private const CANCEL_REASONS = ['Change of plans', 'My dates changed', 'Found somewhere else', 'Too expensive', 'Other'];

    public function __construct(private readonly GuestChangeRequestService $requests) {}

    public function show(Request $request, string $reservation): View
    {
        $model = $this->owned($reservation);

        // A REAL read-only price quote when the guest has picked new dates (GET preview) — never a charge.
        $newCheckIn = trim((string) $request->query('new_check_in', ''));
        $newCheckOut = trim((string) $request->query('new_check_out', ''));
        $quote = ($newCheckIn !== '' && $newCheckOut !== '' && $newCheckOut > $newCheckIn)
            ? $this->requests->quoteDateChange($model, $newCheckIn, $newCheckOut)
            : null;

        return view('guest.manage', [
            'reservation' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'checkIn' => CarbonImmutable::parse($model->check_in)->format('D, j M Y'),
            'checkOut' => CarbonImmutable::parse($model->check_out)->format('D, j M Y'),
            'newCheckIn' => $newCheckIn,
            'newCheckOut' => $newCheckOut,
            'quote' => $quote,
            'cancelReasons' => self::CANCEL_REASONS,
            'pending' => $this->pendingFor($model),
        ]);
    }

    public function requestDateChange(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);
        $data = $request->validate([
            'new_check_in' => ['required', 'date', 'after_or_equal:today'],
            'new_check_out' => ['required', 'date', 'after:new_check_in'],
        ]);

        $this->requests->requestDateChange($model, $this->guest(), (string) $data['new_check_in'], (string) $data['new_check_out']);

        return $this->backWith($model, 'Date-change request sent — we’ll confirm the new dates and any price difference.');
    }

    public function requestAddOns(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);
        // Server-authoritative: the chosen extras must be REAL active AddOns of THIS property (never a name a
        // guest could invent). Shared by the manage section and the dedicated /guest/extras page (back() returns
        // to whichever posted). The operator's confirm posts them to the folio (reconciling #17).
        $data = $request->validate([
            'add_ons' => ['required', 'array', 'min:1'],
            'add_ons.*' => ['string', Rule::exists('add_ons', 'id')->where(fn ($q) => $q->where('property_id', $model->property_id)->where('is_active', true))],
        ]);

        $this->requests->requestAddOns($model, $this->guest(), (array) $data['add_ons']);

        return back()->with('status', 'Extras requested — the property will confirm and add them to your booking.');
    }

    public function requestCancellation(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);
        $data = $request->validate([
            'reason' => ['required', Rule::in(self::CANCEL_REASONS)],
        ]);

        $this->requests->requestCancellation($model, $this->guest(), (string) $data['reason']);

        return $this->backWith($model, 'Cancellation request sent — we’ll be in touch to confirm.');
    }

    /** The authed guest's OWN reservation, or 404 (resolved through their own relation — never a request id). */
    private function owned(string $reservation): Reservation
    {
        return $this->guest()->reservations()->findOrFail($reservation);
    }

    /**
     * @return list<array{type: string, label: string, detail: string, at: string}>
     */
    private function pendingFor(Reservation $reservation): array
    {
        return GuestChangeRequest::query()
            ->where('reservation_id', $reservation->getKey())
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn (GuestChangeRequest $r): array => [
                'type' => (string) $r->type,
                'label' => match ((string) $r->type) {
                    GuestChangeRequest::TYPE_DATE_CHANGE => 'Date change requested',
                    GuestChangeRequest::TYPE_ADD_ON => 'Add-ons requested',
                    GuestChangeRequest::TYPE_CANCELLATION => 'Cancellation requested',
                },
                'detail' => $this->detail($r),
                'at' => CarbonImmutable::parse($r->created_at)->format('j M Y · H:i'),
            ])
            ->all();
    }

    private function detail(GuestChangeRequest $request): string
    {
        $payload = $request->payload;

        return match ((string) $request->type) {
            GuestChangeRequest::TYPE_DATE_CHANGE => (string) ($payload['new_check_in'] ?? '').' → '.(string) ($payload['new_check_out'] ?? ''),
            GuestChangeRequest::TYPE_ADD_ON => implode(', ', array_map('strval', (array) ($payload['add_ons'] ?? []))),
            GuestChangeRequest::TYPE_CANCELLATION => (string) ($payload['reason'] ?? ''),
        };
    }

    private function backWith(Reservation $reservation, string $message): RedirectResponse
    {
        return redirect()->route('guest.manage', $reservation->getKey())->with('status', $message);
    }
}
