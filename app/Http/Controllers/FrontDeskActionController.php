<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Reservations\CheckInReservation;
use App\Actions\Reservations\ReverseCheckIn;
use App\Actions\Reservations\ReverseCheckOut;
use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Exceptions\Booking\UnitUnavailableException;
use App\Exceptions\Folio\OutstandingBalanceException;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Services\AssignmentService;
use App\Services\FrontDeskService;
use App\Support\Operator\CalendarReturn;
use App\Support\Operator\OperatorWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * Front-desk check-in / check-out — the REAL staff actions, driven from the styled Front Desk
 * (design pass · Operator Screen 2) via a POST → action → redirect (PRG) flow. This deliberately
 * does NOT use a Livewire wire-action: a POST to a normal route re-runs the full web middleware
 * stack (SetTenantForWebRequest sets the tenant context), so the mutation is genuinely tenant-scoped
 * — the owner-portal lesson that scope-dependent mutations must re-run the middleware, not ride a
 * /livewire/update re-render.
 *
 * `{reservation}` is a plain STRING route param (never a route-model-bound Model), resolved here via
 * the tenant-scoped global scope AFTER the middleware has set context — so a cross-org id 404s (the
 * standing RMB rule). The transition itself delegates to {@see FrontDeskService} (→ the Phase 2
 * Step 8 CheckIn/CheckOut actions); no inventory/folio/schema is touched.
 */
class FrontDeskActionController extends Controller
{
    public function checkIn(Request $request, FrontDeskService $desk, string $reservation): View|RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        // Only a confirmed booking can be checked in — guard against nonsensical transitions
        // (e.g. re-opening a checked-out stay) without weakening the underlying idempotent action.
        if ($model->status !== 'confirmed') {
            return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.cannot_check_in_from', ['reference' => $model->reference, 'status' => $model->status]), refused: true);
        }

        // ⚠️⚠️ THE EARLY CHECK-IN CONFIRMATION (APPLY 01 #2, 2026-09-22) — A WARNING, NOT A CONTROL.
        //
        // ⚠️ IT REFUSES NOTHING. There is no permission attached, no role that can pass it and no role that
        // cannot, and `confirm_early` is an ordinary form field rather than a signed token — an operator who
        // means it always gets through on the second press. That is the ruling: a front desk meets real
        // situations this product cannot enumerate (an early arrival, a corrected date, a walk-up against a
        // future booking), and a rule that BLOCKED them would be wrong more often than the mis-click it
        // prevents. ⚠️ Anyone tempted to "tighten" this into a gate is changing a ruling, not hardening code.
        //
        // ⚠️ WHY IT EARNS ITS INTERRUPTION ANYWAY: `checked_in` is the one status a check-out can follow, and
        // since R7 (2026-09-22) it can no longer be cancelled — with no reverse-check-in built, a mis-click
        // on the wrong row is currently NOT UNDOABLE. The cheapest place to catch that is before the press.
        //
        // ⚠️ The threshold is config, never per-organisation (see config/reservations.php), and the earliness
        // shown here is the action's OWN `daysEarly()` — the same number it writes to the audit row, so the
        // screen can never warn about a figure the record does not contain.
        if (CheckInReservation::isUnusuallyEarly($model) && ! $request->boolean('confirm_early')) {
            return view('operator.early-check-in', [
                'reservation' => $model,
                'daysEarly' => CheckInReservation::daysEarly($model),
            ]);
        }

        $desk->checkIn($model);

        return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.checked_in', ['reference' => $model->reference]));
    }

    public function checkOut(Request $request, FrontDeskService $desk, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        if ($model->status !== 'checked_in') {
            return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.cannot_check_out_from', ['reference' => $model->reference, 'status' => $model->status]), refused: true);
        }

        // ⚠️ THE SUPERVISOR OVERRIDE — gated on its OWN permission, checked HERE rather than relying on the
        //    checkout gate, because `reservations.checkout` is held by Front Desk and this deliberately is
        //    not. An agent may check out; only a supervisor may check out over a balance.
        $overrideReason = null;
        $overrideNote = null;
        if ($request->filled('override_reason')) {
            if ($request->user()?->can('reservations.checkout_override') !== true) {
                return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.override_supervisor_only'), refused: true);
            }
            $overrideReason = (string) $request->input('override_reason');
            $overrideNote = $request->filled('override_note') ? (string) $request->input('override_note') : null;
        }

        try {
            $desk->checkOut($model, $overrideReason, $overrideNote);
        } catch (InvalidArgumentException $e) {
            return $this->backToBoard($request, $e->getMessage(), refused: true);
        } catch (OutstandingBalanceException $e) {
            // The service is the authority — this only surfaces its refusal, with the amount and the ways
            // forward, because an unhelpful block is what drives a faked payment or a voided charge.
            return $this->backToBoard($request, $e->getMessage(), refused: true);
        }

        return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.checked_out', ['reference' => $model->reference]));
    }

    /**
     * MOVE a reservation line to another unit (Batch C · GATE 2) — the SINGLE guarded inventory write path:
     * {@see AssignmentService::assignUnit} (availability `FOR UPDATE` lock + no-overlap + same-room-type),
     * NEVER a raw `reservation_units.unit_id` update. Gated like check-in/out (any back-office; the POST
     * re-runs the tenant middleware). `{reservation}` + the line are resolved tenant-scoped here → cross-org
     * 404; the line MUST belong to this reservation (never trust the request id across reservations). An
     * occupied / cross-type target is refused by the service and surfaced as a flash — the UI offered only
     * free same-type units, but the service is the authority under the lock.
     */
    /**
     * R3 — the confirmation surface. ⚠️ It WRITES NOTHING: the operator must see every consequence, including
     * the one that cannot be undone, BEFORE pressing rather than after.
     */
    public function reverseCheckoutConfirm(Request $request, ReverseCheckOut $reverse, string $reservation): View|RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        if ($model->status !== 'checked_out') {
            return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.not_checked_out', ['reference' => $model->reference]), refused: true);
        }

        return view('operator.reverse-checkout', [
            'reservation' => $model,
            'disclosure' => $reverse->preview($model),
        ]);
    }

    /** R3 — perform it, and carry the disclosure back to the board so the record of what was NOT undone survives the redirect. */
    public function reverseCheckout(Request $request, ReverseCheckOut $reverse, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        if ($model->status !== 'checked_out') {
            return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.not_checked_out', ['reference' => $model->reference]), refused: true);
        }

        $disclosure = $reverse->handle($model, $request->user());

        return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.checked_in_again', ['reference' => $model->reference]).' '.$disclosure['review_email']['message']);
    }

    /**
     * ⚠️ R9 — the confirmation surface for reversing a mis-clicked check-in. It WRITES NOTHING.
     *
     * ⚠️ SAME SHAPE AS {@see reverseCheckoutConfirm()} ON PURPOSE — a front desk should not have to learn two
     * dialects for the same idea. The disclosure comes from the action's own preview(), so the screen cannot
     * describe consequences the action does not actually produce.
     */
    public function reverseCheckinConfirm(Request $request, ReverseCheckIn $reverse, string $reservation): View|RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        if ($model->status !== 'checked_in') {
            return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.not_checked_in', ['reference' => $model->reference]), refused: true);
        }

        return view('operator.reverse-checkin', [
            'reservation' => $model,
            'disclosure' => $reverse->preview($model),
        ]);
    }

    /** ⚠️ R9 — perform it, carrying the disclosure back to the board so what was NOT undone survives the redirect. */
    public function reverseCheckin(Request $request, ReverseCheckIn $reverse, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        if ($model->status !== 'checked_in') {
            return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.not_checked_in', ['reference' => $model->reference]), refused: true);
        }

        try {
            $disclosure = $reverse->handle($model, $request->user());
        } catch (RuntimeException $e) {
            // ⚠️ The lost-race branch lands here — a clean sentence, never a 500.
            return $this->backToBoard($request, $e->getMessage(), refused: true);
        }

        $tail = count($disclosure['not_reversed']) > 1
            ? ' '.$disclosure['not_reversed'][1]   // the money line when there is one — never invented
            : '';

        return $this->backToBoard($request, __('php/http/controllers/front_desk_action_controller.back_to_confirmed', ['reference' => $model->reference]).$tail);
    }

    public function move(Request $request, AssignmentService $assignment, string $reservation): RedirectResponse
    {
        // READ-ONLY workspace — edits off (same guard as reschedule/create; the UI hides the control).
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->afterMove($request, $reservation, __('php/http/controllers/front_desk_action_controller.workspace_paused'), refused: true);
        }

        $model = Reservation::query()->with('reservationUnits')->findOrFail($reservation);   // tenant-scoped → 404

        $validated = $request->validate([
            'reservation_unit_id' => ['required', 'string'],
            'unit_id' => ['required', 'string'],
            // ⚠️ THE FIRST NIGHT THE GUEST SLEPT IN THE NEW UNIT. Only meaningful once the stay has
            //    started; `AssignmentService` refuses anything outside the stay or in the future, so the
            //    bound lives at the boundary rather than only here.
            //    ⚠️ Gated by `reservations.assign` — the SAME permission as the move itself. Whoever
            //    performs the move knows which night the guest changed rooms; gating the date more
            //    tightly would mean the person who knows cannot state it.
            'effective_date' => ['nullable', 'date'],
        ]);

        $line = $model->reservationUnits->firstWhere('id', $validated['reservation_unit_id']);
        if (! $line instanceof ReservationUnit) {
            abort(404);   // the line is not part of THIS reservation
        }

        try {
            $eff = $validated['effective_date'] ?? null;
            $assignment->assignUnit($line, (string) $validated['unit_id'], is_string($eff) && $eff !== '' ? $eff : null);
        } catch (UnitUnavailableException|InvalidArgumentException) {
            // Occupied for these dates, or a different room type — the move is refused by the guard.
            return $this->afterMove($request, $model->id, __('php/http/controllers/front_desk_action_controller.room_unavailable'), refused: true);
        }

        return $this->afterMove($request, $model->id, __('php/http/controllers/front_desk_action_controller.moved_to_new_unit', ['reference' => $model->reference]));
    }

    /**
     * RESCHEDULE a line — the Calendar drag-to-reschedule (GATE 5.3). The SINGLE guarded inventory write path:
     * {@see AssignmentService::reschedule} (availability `FOR UPDATE` lock + release-old/claim-new + Level A/B
     * re-check), NEVER a raw dates/unit update. Gated `can:reservations.assign` (the POST re-runs the tenant
     * middleware). The stay LENGTH is preserved server-side (a MOVE): new_check_out = new_check_in + the line's
     * current nights — the drag sends only the target DATE (a server-rendered string) + unit, so no client date
     * arithmetic ever runs (no toISOString day-shift). An occupied / cross-type / unavailable target is refused
     * by the service under the lock and surfaced as a flash.
     */
    public function reschedule(Request $request, AssignmentService $assignment, string $reservation): RedirectResponse
    {
        // READ-ONLY workspace (billing paused/canceled but still in /app): edits are OFF. The UI hides the drag,
        // but this is the real server guard — the same self-check every operator write does (create/rate/desk).
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->afterReschedule($request, __('php/http/controllers/front_desk_action_controller.workspace_paused'), refused: true);
        }

        $model = Reservation::query()->with('reservationUnits')->findOrFail($reservation);   // tenant-scoped → 404

        $validated = $request->validate([
            'reservation_unit_id' => ['required', 'string'],
            'new_check_in' => ['required', 'date_format:Y-m-d'],
            'unit_id' => ['nullable', 'string'],
        ]);

        $line = $model->reservationUnits->firstWhere('id', $validated['reservation_unit_id']);
        if (! $line instanceof ReservationUnit) {
            abort(404);   // the line is not part of THIS reservation
        }

        // Preserve the stay LENGTH (a move, not a resize): the server derives check_out from the line's own
        // current nights — the client never computes a date, so there is no local-vs-UTC day-shift to get wrong.
        $nights = max(1, (int) CarbonImmutable::parse($line->check_in)->diffInDays(CarbonImmutable::parse($line->check_out)));
        $newCheckIn = CarbonImmutable::parse($validated['new_check_in'])->startOfDay();
        $newCheckOut = $newCheckIn->addDays($nights);

        $unitId = is_string($validated['unit_id'] ?? null) && $validated['unit_id'] !== '' ? (string) $validated['unit_id'] : null;

        try {
            $assignment->reschedule($line, $newCheckIn->toDateString(), $newCheckOut->toDateString(), $unitId);
        } catch (UnitUnavailableException|InsufficientAvailabilityException|InvalidArgumentException) {
            return $this->afterReschedule($request, __('php/http/controllers/front_desk_action_controller.move_unavailable'), refused: true);
        }

        return $this->afterReschedule($request, __('php/http/controllers/front_desk_action_controller.moved_to_date', ['reference' => $model->reference, 'date' => $newCheckIn->translatedFormat('j M')]));
    }

    /**
     * Redirect back to the Calendar as the person had it — its first night, 7/14/30 and, since 2026-10-04, its room-type filter
     * ({@see CalendarReturn}) — with a flash notice. ⚠️ 2026-10-04: a REFUSED move was said in the same quiet status line as one
     * that worked; it is now marked so the Calendar shows it as an error (`role="alert"`).
     */
    private function afterReschedule(Request $request, string $notice, bool $refused = false): RedirectResponse
    {
        $redirect = redirect()->to(CalendarReturn::window($request))->with('new_reservation_notice', $notice);

        return $refused ? $redirect->with('calendar_refused', true) : $redirect;
    }

    /**
     * Redirect after a move: back to the FRONT-DESK BOARD when it originated there (`return_to=front-desk`,
     * preserving `?date`), otherwise to the reservation detail (the rack/detail move surface is unchanged).
     */
    private function afterMove(Request $request, string $reservationId, string $notice, bool $refused = false): RedirectResponse
    {
        if ($request->input('return_to') === 'front-desk') {
            $date = $request->input('return_date');
            $target = route('app.front-desk').(is_string($date) && $date !== '' ? '?date='.urlencode($date) : '');

            return redirect()->to($target)->with('desk_notice', $notice)->with('desk_refused', $refused);
        }

        return $this->backToDetail($reservationId, $notice, $refused);
    }

    /**
     * Redirect back to the reservation detail with a folio_notice flash (shared by move/charge).
     *
     * ⚠️ `$refused` rides ALONGSIDE the message (audit D4). A lost inventory race — "That move isn't
     * available — the unit or nights are already taken" — used to render under the same green checkmark as
     * "Moved BK-1042 to 4 Sep", because the key carried no outcome for the view to read.
     * ⚠️ THE SAME REFUSAL REACHES TWO DESTINATIONS (`desk_notice` on the board, `folio_notice` on the
     * detail) depending on `return_to`, so BOTH carry the flag — fixing one and not the other would leave
     * the same failure honest on one screen and a lie on the other.
     */
    private function backToDetail(string $reservationId, string $notice, bool $refused = false): RedirectResponse
    {
        return redirect()->to(route('app.reservations.show', $reservationId))
            ->with('folio_notice', $notice)
            ->with('folio_refused', $refused);
    }

    /** Redirect back to the board on the same day (preserves ?date), with a flash notice. */
    private function backToBoard(Request $request, string $notice, bool $refused = false): RedirectResponse
    {
        $date = $request->input('date');
        $target = route('app.front-desk').(is_string($date) && $date !== '' ? '?date='.urlencode($date) : '');

        return redirect()->to($target)->with('desk_notice', $notice)->with('desk_refused', $refused);
    }
}
