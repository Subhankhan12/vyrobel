<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\Booking\InsufficientAvailabilityException;
use App\Exceptions\Booking\UnitUnavailableException;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Services\AssignmentService;
use App\Services\FrontDeskService;
use App\Support\Operator\OperatorWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

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
    public function checkIn(Request $request, FrontDeskService $desk, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        // Only a confirmed booking can be checked in — guard against nonsensical transitions
        // (e.g. re-opening a checked-out stay) without weakening the underlying idempotent action.
        if ($model->status !== 'confirmed') {
            return $this->backToBoard($request, "{$model->reference} can’t be checked in from “{$model->status}”.");
        }

        $desk->checkIn($model);

        return $this->backToBoard($request, "Checked in {$model->reference}.");
    }

    public function checkOut(Request $request, FrontDeskService $desk, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);

        if ($model->status !== 'checked_in') {
            return $this->backToBoard($request, "{$model->reference} can’t be checked out from “{$model->status}”.");
        }

        $desk->checkOut($model);

        return $this->backToBoard($request, "Checked out {$model->reference}.");
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
    public function move(Request $request, AssignmentService $assignment, string $reservation): RedirectResponse
    {
        // READ-ONLY workspace — edits off (same guard as reschedule/create; the UI hides the control).
        if (app(OperatorWorkspace::class)->isReadOnly()) {
            return $this->afterMove($request, $reservation, 'The workspace is paused — moves are off.');
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
            return $this->afterMove($request, $model->id, 'That room isn’t available for these dates — pick another.');
        }

        return $this->afterMove($request, $model->id, "Moved {$model->reference} to a new unit.");
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
            return $this->afterReschedule($request, 'The workspace is paused — moves are off.');
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
            return $this->afterReschedule($request, 'That move isn’t available — the unit or nights are already taken.');
        }

        return $this->afterReschedule($request, "Moved {$model->reference} to {$newCheckIn->format('j M')}.");
    }

    /** Redirect back to the Calendar, preserving the rack window (start/days), with a flash notice. */
    private function afterReschedule(Request $request, string $notice): RedirectResponse
    {
        $start = $request->input('return_start');
        $days = (int) $request->input('return_days');
        $params = array_filter([
            'start' => is_string($start) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) === 1 ? $start : null,
            'days' => in_array($days, [7, 14, 30], true) ? $days : null,
        ], fn ($v): bool => $v !== null);

        return redirect()->to(route('app.calendar').($params !== [] ? '?'.http_build_query($params) : ''))
            ->with('new_reservation_notice', $notice);
    }

    /**
     * Redirect after a move: back to the FRONT-DESK BOARD when it originated there (`return_to=front-desk`,
     * preserving `?date`), otherwise to the reservation detail (the rack/detail move surface is unchanged).
     */
    private function afterMove(Request $request, string $reservationId, string $notice): RedirectResponse
    {
        if ($request->input('return_to') === 'front-desk') {
            $date = $request->input('return_date');
            $target = route('app.front-desk').(is_string($date) && $date !== '' ? '?date='.urlencode($date) : '');

            return redirect()->to($target)->with('desk_notice', $notice);
        }

        return $this->backToDetail($reservationId, $notice);
    }

    /** Redirect back to the reservation detail with a folio_notice flash (shared by move/charge). */
    private function backToDetail(string $reservationId, string $notice): RedirectResponse
    {
        return redirect()->to(route('app.reservations.show', $reservationId))->with('folio_notice', $notice);
    }

    /** Redirect back to the board on the same day (preserves ?date), with a flash notice. */
    private function backToBoard(Request $request, string $notice): RedirectResponse
    {
        $date = $request->input('date');
        $target = route('app.front-desk').(is_string($date) && $date !== '' ? '?date='.urlencode($date) : '');

        return redirect()->to($target)->with('desk_notice', $notice);
    }
}
