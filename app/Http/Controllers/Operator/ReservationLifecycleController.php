<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReservationFolioActionController;
use App\Livewire\ReservationDetail;
use App\Models\Reservation;
use App\Services\AssignmentService;
use App\Services\ReservationCancellationService;
use App\Support\Money\MoneyFormatter;
use App\Support\Reservations\ReservationStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Operator reservation LIFECYCLE writes — the REAL cancel + mark-no-show actions driven from the styled
 * Reservation Detail ({@see ReservationDetail}) via the locked operator-mutation pattern: POST →
 * controller → {@see AssignmentService} → redirect (PRG), exactly as
 * {@see MaintenanceActionController} and
 * {@see ReservationFolioActionController} do.
 *
 * A normal POST re-runs the web middleware (SetTenantForWebRequest sets context), so every write is
 * genuinely tenant-scoped: `{reservation}` is a plain STRING param resolved through the
 * `BelongsToOrganization` global scope AFTER the middleware (never route-model-bound) → a cross-org id
 * 404s. `/app/*` also sits behind `EnsureBackOfficeAccess`, so an owner-role user never reaches these.
 *
 * Both actions delegate to {@see AssignmentService} — the SINGLE inventory write path (convention #9).
 * Nothing here touches `reservation_units`, `availability` or `sold`: `cancelReservation()`/`markNoShow()`
 * funnel into `terminate()`, which in ONE transaction frees every held night via `releaseLine()`, sets the
 * status + `cancelled_at` (+ `cancellation_reason` on a cancel), writes `reservation_status_history`, and
 * emits the domain/audit events post-commit.
 *
 * ⚠️ WHY THE TRANSITION GUARD LIVES HERE AND NOT IN THE SERVICE. `AssignmentService::terminate()` has NO
 * status guard (verified 2026-07-16, AssignmentService.php:586) — contrary to what a "guarded lifecycle"
 * reading suggests, re-cancelling an ALREADY-cancelled reservation does not throw. It would silently
 * succeed and OVERWRITE `cancelled_at`/`cancellation_reason` — destroying the real provenance the detail
 * screen renders — and append a meaningless `cancelled → cancelled` history row to an append-only audit
 * trail. So this controller checks the REAL current status against an allow-list BEFORE calling the
 * service, and refuses with a clean flash. Adding the guard to the service is the correct long-term fix
 * and is NOT in this gate's scope (its signatures are fixed); until then this allow-list is load-bearing,
 * not belt-and-braces. {@see ReservationDetail} gates the buttons off these SAME constants so the UI can
 * never offer a transition the write path will refuse.
 *
 * The `catch (RuntimeException)` is nonetheless real, not theatre: `releaseLine()` → `lockAvailability()`
 * throws `InsufficientAvailabilityException` (→ `BookingException` → `RuntimeException`) when a stay's
 * nights have no published availability row. That happens INSIDE `DB::transaction()`, so the rollback is
 * total — hence the flash can honestly say nothing was changed. An expected rejection must never 500.
 */
class ReservationLifecycleController extends Controller
{
    /**
     * The real `reservations.status` values a booking may be CANCELLED from — i.e. everything that is not
     * already finished. `checked_out` is excluded (the stay happened; cancelling it would be a fiction) and
     * so are the two terminal states. Cancelling a `checked_in` stay IS allowed: a mid-stay cancellation is
     * a real, if unusual, operation and `terminate()` correctly frees the remaining held nights.
     *
     * @var list<string>
     */
    public const CANCELLABLE = ['enquiry', 'tentative', 'confirmed', 'checked_in'];

    /**
     * The statuses a booking may be marked NO-SHOW from — `confirmed` ONLY.
     *
     * THE RULE (chosen here, documented deliberately): a no-show means "a guest who was expected to arrive
     * did not". `checked_in`/`checked_out` guests demonstrably arrived — marking them a no-show contradicts
     * a real recorded fact. `enquiry`/`tentative` are not yet confirmed stays: nobody has promised to
     * arrive, so there is nothing to fail to show up for (cancel is the right terminal for those). That
     * leaves exactly `confirmed`.
     *
     * NB deliberately NOT also gated on the arrival DATE having passed, though "no-show" implies it. A date
     * test needs a "today", and the only honest one is the PROPERTY's timezone — which nothing in the app
     * yet honours ({@see ReservationStage} documents that its `today()` is SERVER
     * time, matching FrontDesk, and that the two must be fixed together). Adding a third, divergent,
     * server-time "today" on a WRITE path would wrongly refuse a legitimate no-show for any property whose
     * arrival day has ended in its own timezone but not on the server. The operator marking the no-show IS
     * the human authority that the guest did not arrive, and `confirmed` already encodes "expected, not yet
     * arrived" — so status alone is the timezone-safe rule. The date question belongs to the deferred
     * auto-mark scheduler (docs/WAVE2-INVENTORY.md C5), which is where it must actually be answered.
     *
     * @var list<string>
     */
    public const NO_SHOWABLE = ['confirmed'];

    /** Cancel a reservation through the single write path, with the operator's optional free-text reason. */
    public function cancel(Request $request, ReservationCancellationService $cancellations, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        if (! in_array((string) $model->status, self::CANCELLABLE, true)) {
            return $this->back($model->id, 'That reservation can’t be cancelled — it’s already closed.');
        }

        // Read defensively (`reason[]=…` would make a naive string cast warn) and validate by hand rather
        // than via validate(): a ValidationException redirects with an `errors` bag, and this view renders
        // only `folio_notice` — the operator would see a silent no-op. Same manual-check posture as the
        // sibling ReservationFolioActionController.
        $raw = $request->input('reason');
        $reason = is_string($raw) ? trim($raw) : '';

        // `cancellation_reason` is a nullable string column — never silently truncate the operator's words.
        if (mb_strlen($reason) > 255) {
            return $this->back($model->id, 'That cancellation reason is too long — 255 characters maximum.');
        }

        try {
            // Cancel + auto-refund IN-WINDOW (founder decision): a cancellation within the property's free-cancel
            // window fully refunds everything paid (bounded #27, test-mode); after it, no auto-refund (manual).
            $result = $cancellations->cancelWithPolicyRefund($model, $reason !== '' ? $reason : null);
        } catch (RuntimeException) {
            // An expected rejection (e.g. no published availability row for a night) — the whole
            // terminate() transaction rolled back, so nothing changed. Clean flash, never a 500.
            return $this->back($model->id, 'That reservation couldn’t be cancelled — the booking service rejected it. Nothing was changed.');
        }

        $message = $result['refunded_minor'] > 0
            ? 'Reservation cancelled + '.MoneyFormatter::display($result['refunded_minor'], (string) $model->currency).' refunded (within the free-cancellation window). The held nights are back on the calendar.'
            : 'Reservation cancelled. The held nights are back on the calendar.';

        return $this->back($model->id, $message);
    }

    /**
     * Mark a reservation a no-show through the single write path.
     *
     * NB no reason is captured: `AssignmentService::markNoShow()` takes none (it calls
     * `terminate(…, null)` internally), so a no-show genuinely HAS no reason and the detail screen omits
     * the row rather than inventing one. Widening that signature is a service change, out of scope here.
     */
    public function markNoShow(AssignmentService $assignments, string $reservation): RedirectResponse
    {
        $model = Reservation::query()->findOrFail($reservation);   // tenant-scoped → cross-org 404

        if (! in_array((string) $model->status, self::NO_SHOWABLE, true)) {
            return $this->back($model->id, 'That reservation can’t be marked a no-show — only a confirmed booking can be.');
        }

        try {
            $assignments->markNoShow($model);
        } catch (RuntimeException) {
            return $this->back($model->id, 'That reservation couldn’t be marked a no-show — the booking service rejected it. Nothing was changed.');
        }

        return $this->back($model->id, 'Marked as a no-show. The held nights are back on the calendar.');
    }

    /** PRG back to the detail screen, using the ONLY flash key that view renders. */
    private function back(string $reservationId, string $notice): RedirectResponse
    {
        return redirect()->to(route('app.reservations.show', $reservationId))->with('folio_notice', $notice);
    }
}
