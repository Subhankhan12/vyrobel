<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Models\Folio;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Money\MoneyFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ⚠️⚠️ R9 (founder, 2026-09-22) — REVERSE A MIS-CLICKED CHECK-IN, AND DISCLOSE WHAT IT DOES NOT TOUCH.
 *
 * ⚠️⚠️ WHY IT EXISTS: R7 CLOSED THE ONLY DOOR OUT. Blocking `checked_in → cancelled` left `checked_in` with a
 * single exit, `checked_out` — and check-out refuses while a balance is owed. So a clerk who checked in the
 * wrong row faced a waiver and a recorded stay that never happened. R9 is the door R7 needed.
 *
 * ⚠️⚠️ WHAT A CHECK-IN ACTUALLY WRITES — 🔬 MEASURED 2026-09-22 BY DIFFING EVERY TABLE ACROSS A REAL CALL,
 * not by reading the code, because this is the question that decides whether reversing is safe:
 *
 *     reservations.status          'confirmed' → 'checked_in'     ← the ONLY column that moves
 *     reservation_status_history   +1 row
 *     domain_events                +1 row
 *     audit_events                 +1 row
 *     …and NOTHING else. No folio item. No availability row. No housekeeping task. No email.
 *
 * ⚠️⚠️ **THAT ZERO IS THE SAFETY ARGUMENT, AND IT IS A MEASUREMENT RATHER THAN A CLAIM.** The feared defect —
 * *"a reverse that undoes the status but leaves a charge behind is a money bug wearing a fix's clothes"* —
 * cannot occur here, because a check-in posts no charge at all. ⚠️ The same diff also proves **no listener
 * acted** on `reservation.checked_in`: a listener that did anything would have written a row somewhere, and
 * exactly three tables moved.
 *
 * ⚠️ THIS IS THE MIRROR OF {@see ReverseCheckOut} AND DELIBERATELY NOT ITS TWIN. A check-OUT fans out — it
 * creates a departure clean, dirties the unit, notifies, and emails a review request — which is why R3 needed
 * a long disclosure of what could not be undone. A check-IN fans out to nothing, so R9's disclosure is
 * SHORT — but it is not absent, because things that happened *during* the stay are not the check-in's to undo:
 *
 *   • the STATUS returns to `confirmed`                   — reversible
 *   • the check-in's history row STAYS, a reversal is APPENDED  — ⚠️ #7. The stay really was checked in;
 *                                                            deleting that row would be a second lie
 *   • charges POSTED WHILE IN-HOUSE are LEFT and disclosed — ⚠️ a minibar item is a real debt; reversing a
 *                                                            status must never silently void money
 *   • the guest's ONLINE check-in record is LEFT and disclosed — the guest really did complete it
 *
 * ⚠️ IT DOES NOT TOUCH INVENTORY, and must not: the nights were occupied at BOOKING time and `checked_in`
 * never changed them, so `confirmed` holds exactly the same lines. Releasing anything here would be the
 * F-18 shape — a status operation reaching into the single inventory write path.
 *
 * ⚠️ AND IT IS SEPARATE FROM {@see ConfirmTentativeReservation}, which also produces `confirmed`. That one
 * converts a tentative HOLD; this one walks a stay backwards. One shared method would make a later reader
 * believe a hold and a reversed arrival are the same event, and the audit trail would stop distinguishing them.
 */
class ReverseCheckIn
{
    /** ⚠️ The one status this may be performed from. Asserted, never inferred from "not terminal". */
    private const ALLOWED_FROM = 'checked_in';

    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * ⚠️ WHAT WOULD HAPPEN — the confirmation surface reads THIS, so the operator sees every consequence
     * BEFORE pressing. It writes nothing.
     *
     * @return array{reversible: list<string>, not_reversed: list<string>}
     */
    public function preview(Reservation $reservation): array
    {
        $this->assertReversible($reservation);

        $reversible = [
            'The stay returns to confirmed, and the room stays held for the same nights.',
        ];

        $notReversed = [
            'The check-in itself stays in the booking history. It happened, and the record says so — the reversal is added alongside it.',
        ];

        // ⚠️ REAL FIGURES OR NO LINE AT ALL (#39). A fabricated "charges may remain" warning on a stay with an
        //    empty folio teaches an operator to ignore the panel.
        [$count, $minor, $currency] = $this->chargesSinceCheckIn($reservation);
        if ($count > 0) {
            $notReversed[] = $count.' charge'.($count === 1 ? '' : 's').' posted since check-in, totalling '
                .MoneyFormatter::display($minor, $currency)
                .', stay on the folio. Reversing an arrival does not un-spend money — remove them on the folio if they were a mistake too.';
        }

        if ($reservation->guest_checked_in_at !== null) {
            $notReversed[] = 'The guest\'s own online check-in stays completed. They really did fill it in, and this does not ask them to do it again.';
        }

        return ['reversible' => $reversible, 'not_reversed' => $notReversed];
    }

    /**
     * ⚠️ THE DISCLOSURE IS RETURNED, NOT LOGGED AND FORGOTTEN — the caller renders it, exactly as R3 does.
     *
     * @return array{reversible: list<string>, not_reversed: list<string>}
     */
    public function handle(Reservation $reservation, ?User $by = null): array
    {
        $disclosure = $this->preview($reservation);

        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $fromStatus, $by): void {
            // ⚠️⚠️ THE LOCK IS THE WHOLE CONCURRENCY STORY. Two clerks double-clicking Reverse would otherwise
            //    both read `checked_in`, both write `confirmed` and both append a history row — one state
            //    change and TWO reversal trails, which is the shape #29 was created to refuse. Re-reading the
            //    status INSIDE the lock is what makes the second call a no-op rather than a duplicate.
            $locked = Reservation::query()->whereKey($reservation->getKey())->lockForUpdate()->first();

            if ($locked === null || (string) $locked->status !== self::ALLOWED_FROM) {
                // ⚠️ THE LOSER OF THE RACE REFUSES, IT DOES NOT SILENTLY SUCCEED. Both outcomes leave exactly
                //    one state change; the difference is whether the second clerk is told. A silent success
                //    would report "reversed" to someone whose press did nothing, which is the same class of
                //    lie as a warning that never fired. Same exception type as assertReversible(), so the
                //    controller's existing `catch (RuntimeException)` surfaces it as a flash, never a 500.
                throw new RuntimeException(
                    'That check-in was already reversed a moment ago — the stay is back to confirmed.'
                );
            }

            $locked->update(['status' => 'confirmed']);

            // ⚠️ APPENDED, NEVER EDITED (#7) — the same posture as ReverseCheckOut. `reason` is what tells a
            //    later reader this `checked_in → confirmed` row is an undo and not a fresh confirmation.
            ReservationStatusHistory::create([
                'reservation_id' => $locked->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'confirmed',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'reason' => 'check-in reversed',
                'created_at' => now(),
            ]);
        });

        $reservation->refresh();

        $this->domainEvents->record('reservation.checkin_reversed', $reservation, [
            'property_id' => $reservation->property_id,
        ]);
        $this->audit->record('reservation.checkin_reversed', $reservation,
            ['status' => $fromStatus], ['status' => 'confirmed']);

        return $disclosure;
    }

    /**
     * Charges posted at or after the moment this stay was checked in.
     *
     * ⚠️ DERIVED FROM THE REAL HISTORY ROW, never from "now minus a guess". The check-in's own
     * `reservation_status_history` row is the only honest marker of when the stay began.
     *
     * @return array{0: int, 1: int, 2: string}
     */
    private function chargesSinceCheckIn(Reservation $reservation): array
    {
        $currency = (string) ($reservation->currency ?: 'EUR');

        $checkedInAt = ReservationStatusHistory::query()
            ->where('reservation_id', $reservation->getKey())
            ->where('to_status', 'checked_in')
            ->latest('created_at')->value('created_at');

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();

        if ($checkedInAt === null || ! $folio instanceof Folio) {
            return [0, 0, $currency];
        }

        $items = $folio->items()->where('created_at', '>=', $checkedInAt)->get();

        return [$items->count(), (int) $items->sum('amount_minor'), $currency];
    }

    /**
     * ⚠️ THE BOUND, IN THE LAYER THAT WRITES — services are called by jobs and tests with no user, so the
     * controller's own pre-check bounds the HTTP surface only. Same posture as every other action here.
     */
    private function assertReversible(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if ($from !== self::ALLOWED_FROM) {
            // ⚠️ NAME THE REAL STATUS. "Cannot reverse" tells a front desk nothing; "this one is cancelled"
            //    tells them which screen to go to next.
            throw new RuntimeException(
                "Only a checked-in stay can have its check-in reversed; this one is {$from}."
            );
        }

        // ⚠️⚠️ A RUNTIME CHECK THAT THE TWO REFUSAL SETS HAVE NOT BEEN MERGED WAS WRITTEN HERE AND REMOVED,
        //    because PHPStan proved it could never fire: `$from` is already `=== 'checked_in'` by the branch
        //    above, and `TERMINAL_STATUSES` is a compile-time literal that does not contain it — so the
        //    condition is statically false and the guard was dead code wearing a guard's clothes.
        //
        // ⚠️ **THE PROPERTY STILL MATTERS; THE ENFORCEMENT BELONGS WHERE IT CAN ACTUALLY BE OBSERVED.** R9's
        //    premise is that `checked_in` is refused WITHOUT being terminal (R7 vs #29). If a later pass
        //    folds the two constants together, reversing something the rest of the system calls final
        //    becomes incoherent — so `ReverseCheckInTest` asserts the sets stay DISJOINT and
        //    `TerminateGuardTest` asserts it again from the other side. A test sees a constant change;
        //    this function could not have.
    }
}
