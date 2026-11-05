<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Guest\Concerns\ResolvesGuest;
use App\Models\AddOn;
use App\Models\GuestChangeRequest;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\GuestChangeRequestService;
use App\Support\I18n\ListJoin;
use App\Support\I18n\LocalDate;
use App\Support\Reservations\CancellationPolicy;
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

    // ⚠️ I18N-01 — these English values are the stored CODES (validated with Rule::in, kept in the request's payload); the
    //    words a guest reads come from cancelReasonLabel(), so a translated label can never fail the validation.
    private const CANCEL_REASONS = ['Change of plans', 'My dates changed', 'Found somewhere else', 'Too expensive', 'Other'];

    public function __construct(private readonly GuestChangeRequestService $requests) {}

    public function show(Request $request, string $reservation): View
    {
        $model = $this->owned($reservation);
        $today = CarbonImmutable::today();

        // A REAL read-only price quote when the guest has picked new dates (GET preview) — never a charge.
        $newCheckIn = trim((string) $request->query('new_check_in', ''));
        $newCheckOut = trim((string) $request->query('new_check_out', ''));
        $quote = ($newCheckIn !== '' && $newCheckOut !== '' && $newCheckOut > $newCheckIn)
            ? $this->requests->quoteDateChange($model, $newCheckIn, $newCheckOut)
            : null;

        $freeUntil = CancellationPolicy::freeUntil($model);

        return view('guest.manage', [
            'reservation' => (string) $model->getKey(),
            'reference' => (string) $model->reference,
            'property' => (string) ($model->property->name ?? __('php/http/controllers/guest/guest_manage_controller.your_stay')),
            'checkIn' => CarbonImmutable::parse($model->check_in)->translatedFormat(LocalDate::pattern('D, j M Y')),
            'checkOut' => CarbonImmutable::parse($model->check_out)->translatedFormat(LocalDate::pattern('D, j M Y')),
            'newCheckIn' => $newCheckIn,
            'newCheckOut' => $newCheckOut,
            'quote' => $quote,
            'cancelReasons' => self::CANCEL_REASONS,
            // code => the label the guest reads (the select's option text; its value stays the code)
            'cancelReasonLabels' => array_combine(self::CANCEL_REASONS, array_map(fn (string $code): string => $this->cancelReasonLabel($code), self::CANCEL_REASONS)),
            'pending' => $this->pendingFor($model),
            // Real free-cancellation deadline (null when none / it has passed) — never a "convert to credit" fabrication.
            'freeUntil' => $freeUntil !== null && $freeUntil->greaterThan($today) ? $freeUntil->translatedFormat(LocalDate::pattern('D, j M Y')) : null,
            'canRequestChanges' => self::changesAllowed($model),
        ]);
    }

    /**
     * ⚠️⚠️ THE ACTIONS ARE GATED, THE PAGE IS NOT — AND THE SPLIT IS THE POINT.
     *
     * ⚠️ A guest has an entirely legitimate reason to open a stay that has ENDED: their dates, their folio,
     * their receipt. Gating the page would take that away to solve a different problem. ⚠️ What makes no
     * sense on a finished stay is ASKING TO CHANGE IT — and 🔬 measured before this guard existed, all three
     * requests went straight through on a `cancelled`, `checked_out` or `no_show` booking:
     * `GuestChangeRequestService` has no status check, so the guest got "we'll be in touch" and an operator
     * got a pending request they could not action.
     *
     * ⚠️ `TERMINAL_STATUSES` is the SHARED definition (#29), never a second list. ⚠️ `checked_in` is
     * deliberately NOT here: a guest in the room may still add extras, ask to extend, or ask to cancel —
     * and since R9 that last one is something an operator can actually carry out, in two deliberate steps.
     */
    private static function changesAllowed(Reservation $reservation): bool
    {
        return ! in_array((string) $reservation->status, AssignmentService::TERMINAL_STATUSES, true);
    }

    /** ⚠️ The refusal itself — the same sentence wherever it is needed, never re-typed per action. */
    private function refuseFinishedStay(Reservation $model): RedirectResponse
    {
        return $this->backWith($model, __('php/http/controllers/guest/guest_manage_controller.stay_closed'));
    }

    public function requestDateChange(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);

        // ⚠️ THE ROUTE HALF OF THE GATE. Hiding the form is the UI half; a direct POST must be refused too —
        //    the same #30 rule that says gate the CONTROL as well as the route, read in both directions.
        if (! self::changesAllowed($model)) {
            return $this->refuseFinishedStay($model);
        }
        $data = $request->validate([
            'new_check_in' => ['required', 'date', 'after_or_equal:today'],
            'new_check_out' => ['required', 'date', 'after:new_check_in'],
        ]);

        $this->requests->requestDateChange($model, $this->guest(), (string) $data['new_check_in'], (string) $data['new_check_out']);

        return $this->backWith($model, __('php/http/controllers/guest/guest_manage_controller.date_change_sent'));
    }

    public function requestAddOns(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);

        // ⚠️ THE ROUTE HALF OF THE GATE. Hiding the form is the UI half; a direct POST must be refused too —
        //    the same #30 rule that says gate the CONTROL as well as the route, read in both directions.
        if (! self::changesAllowed($model)) {
            return $this->refuseFinishedStay($model);
        }
        // Server-authoritative: the chosen extras must be REAL active AddOns of THIS property (never a name a
        // guest could invent). Shared by the manage section and the dedicated /guest/extras page (back() returns
        // to whichever posted). The operator's confirm posts them to the folio (reconciling #17).
        $data = $request->validate([
            'add_ons' => ['required', 'array', 'min:1'],
            'add_ons.*' => ['string', Rule::exists('add_ons', 'id')->where(fn ($q) => $q->where('property_id', $model->property_id)->where('is_active', true))],
        ]);

        $this->requests->requestAddOns($model, $this->guest(), (array) $data['add_ons']);

        return back()->with('status', __('php/http/controllers/guest/guest_manage_controller.extras_requested'));
    }

    public function requestCancellation(Request $request, string $reservation): RedirectResponse
    {
        $model = $this->owned($reservation);

        // ⚠️ THE ROUTE HALF OF THE GATE. Hiding the form is the UI half; a direct POST must be refused too —
        //    the same #30 rule that says gate the CONTROL as well as the route, read in both directions.
        if (! self::changesAllowed($model)) {
            return $this->refuseFinishedStay($model);
        }
        $data = $request->validate([
            'reason' => ['required', Rule::in(self::CANCEL_REASONS)],
        ]);

        $this->requests->requestCancellation($model, $this->guest(), (string) $data['reason']);

        return $this->backWith($model, __('php/http/controllers/guest/guest_manage_controller.cancellation_sent'));
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
                    GuestChangeRequest::TYPE_DATE_CHANGE => __('php/http/controllers/guest/guest_manage_controller.pending_date_change'),
                    GuestChangeRequest::TYPE_ADD_ON => __('php/http/controllers/guest/guest_manage_controller.pending_add_ons'),
                    GuestChangeRequest::TYPE_CANCELLATION => __('php/http/controllers/guest/guest_manage_controller.pending_cancellation'),
                },
                'detail' => $this->detail($r),
                'at' => CarbonImmutable::parse($r->created_at)->translatedFormat(LocalDate::pattern('j M Y · H:i')),
            ])
            ->all();
    }

    private function detail(GuestChangeRequest $request): string
    {
        $payload = $request->payload;

        return match ((string) $request->type) {
            GuestChangeRequest::TYPE_DATE_CHANGE => (string) ($payload['new_check_in'] ?? '').' → '.(string) ($payload['new_check_out'] ?? ''),
            // ⚠️ WAVE 3 — reads `add_on_ids`, the key GuestChangeRequestService actually WRITES (:77) and
            // reads back (:127). This read `add_ons`, which nothing ever writes, so every pending add-on row
            // rendered BLANK. Resolved to real NAMES too — the ids are ULIDs and mean nothing to a guest.
            GuestChangeRequest::TYPE_ADD_ON => $this->addOnNames((array) ($payload['add_on_ids'] ?? [])),
            GuestChangeRequest::TYPE_CANCELLATION => $this->cancelReasonLabel((string) ($payload['reason'] ?? '')),
        };
    }

    /**
     * Real add-on names for a pending request. Tenant-scoped (AddOn carries the org scope), so an id that is
     * not this org's simply does not resolve; an unresolvable id is omitted rather than printed raw.
     *
     * @param  list<mixed>  $ids
     */
    private function addOnNames(array $ids): string
    {
        if ($ids === []) {
            return '';
        }

        return AddOn::query()
            ->whereIn('id', array_map('strval', $ids))
            ->orderBy('name')
            ->pluck('name')
            ->pipe(fn ($names) => ListJoin::join($names));
    }

    /** The words for a stored cancellation-reason code; anything else (an older row) is shown as it was stored. */
    private function cancelReasonLabel(string $code): string
    {
        return match ($code) {
            'Change of plans' => __('php/http/controllers/guest/guest_manage_controller.reason_change_of_plans'),
            'My dates changed' => __('php/http/controllers/guest/guest_manage_controller.reason_dates_changed'),
            'Found somewhere else' => __('php/http/controllers/guest/guest_manage_controller.reason_found_elsewhere'),
            'Too expensive' => __('php/http/controllers/guest/guest_manage_controller.reason_too_expensive'),
            'Other' => __('php/http/controllers/guest/guest_manage_controller.reason_other'),
            default => $code,
        };
    }

    private function backWith(Reservation $reservation, string $message): RedirectResponse
    {
        return redirect()->route('guest.manage', $reservation->getKey())->with('status', $message);
    }
}
