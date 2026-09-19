<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Exceptions\Folio\OutstandingBalanceException;
use App\Listeners\GenerateDepartureCleanTask;
use App\Models\Folio;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\Folio\CheckoutOverrideReason;
use App\Support\Folio\FolioTotals;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Checks a reservation out. This is a pure reservation STATE transition — it does NOT touch
 * inventory (the nights were consumed, not freed), so it never goes through AssignmentService
 * and never mutates availability. It emits `reservation.checked_out` post-commit; downstream
 * (housekeeping) reacts to that event as a one-directional consumer.
 */
class CheckOutReservation
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * ⚠️ `$overrideReason` — a SUPERVISOR override permitting a checkout while money is still owed.
     *
     * ⚠️⚠️ IT IS AN ACT, NOT A STATE. It is passed per checkout and never stored as a standing flag on the
     * reservation or the folio: a persistent "may check out unpaid" would be a permanent hole rather than a
     * one-time decision somebody made and can be held to.
     *
     * ⚠️⚠️ AND IT CHANGES NO MONEY. It permits an ACTION; it does not resolve a DEBT. The balance is still
     * owed afterwards, `BalanceDueNotification` still fires, and `reservationIsCollected()` — literally
     * `balance_minor <= 0` — still reads FALSE, so no owner is paid for cash nobody has received.
     */
    public function handle(Reservation $reservation, ?User $by = null, ?string $overrideReason = null, ?string $overrideNote = null): Reservation
    {
        if ($reservation->status === 'checked_out') {
            return $reservation; // idempotent
        }

        // ⚠️ THE STATUS BOUND RUNS FIRST — before the money guard. A cancelled booking must be refused as
        // cancelled, not reported as having an outstanding balance, and no side effect may fire either way.
        $this->assertTransition($reservation);

        $this->assertFolioSettledOrBilled($reservation, $overrideReason, $overrideNote);

        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $fromStatus, $by): void {
            $reservation->update(['status' => 'checked_out']);

            ReservationStatusHistory::create([
                'reservation_id' => $reservation->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'checked_out',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'created_at' => now(),
            ]);
        });

        // Post-commit. Payload carries what the housekeeping listener needs (no re-query, no
        // inventory reference). This event is NOT an availability-recompute trigger.
        $unitIds = $reservation->reservationUnits()
            ->where('status', 'active')
            ->whereNotNull('unit_id')
            ->pluck('unit_id')
            ->all();

        $this->domainEvents->record('reservation.checked_out', $reservation, [
            'property_id' => $reservation->property_id,
            'unit_ids' => $unitIds,
            'date' => CarbonImmutable::parse($reservation->check_out)->toDateString(),
        ]);
        $this->audit->record('reservation.checked_out', $reservation, ['status' => $fromStatus], ['status' => 'checked_out']);

        return $reservation->refresh();
    }

    /**
     * ⚠️⚠️ CHECKOUT IS REFUSED WHILE MONEY IS OWED — unless the balance is billed to a company account.
     *
     * ⚠️ THE GUARD SITS AT THE CONVERGENCE POINT, not in a screen. `FrontDeskService::checkOut()` is this
     * action's only caller and the controller is that method's only caller, so guarding here covers every
     * future caller too; a UI-only block would be bypassable by all of them while looking like protection.
     *
     * ⚠️ IT READS `FolioTotals` — THE SAME COMPUTATION THE FOLIO SCREEN RENDERS (§N.8f), never a second
     * one. A block that fires while the desk reads "€0 due" is indefensible, and the agent routes around it.
     *
     * ⚠️ ONLY AN AMOUNT OWED BLOCKS. A CREDIT balance (the guest overpaid) and a ZERO both pass — a sign
     * error here would block the guests least at fault. Integer minor units; no float comes near it.
     *
     * ⚠️⚠️ THE EXEMPTION IS WHAT MAKES THIS DEFENSIBLE RATHER THAN A BAN ON CORPORATE BUSINESS. A guest can
     * legitimately owe at departure when a company settles later, so a folio carrying a keyed
     * `billed_to_account_id` checks out with the balance still standing.
     *
     * ⚠️⚠️ AND NOTE WHAT IS *NOT* DONE HERE: billing posts NOTHING, so the balance is still owed and this
     * guard must test the BILLED STATE rather than a zeroed balance. Zeroing it to make one guard "just
     * work" would make `OwnerCollection::reservationIsCollected()` — literally `balance_minor <= 0` — read
     * the booking as COLLECTED, and the payout engine would release owner money for cash nobody has
     * received.
     */
    private function assertFolioSettledOrBilled(Reservation $reservation, ?string $overrideReason = null, ?string $overrideNote = null): void
    {
        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();

        if (! $folio instanceof Folio) {
            return;   // nothing was ever charged — nothing can be owed
        }

        if ($folio->billed_to_account_id !== null) {
            return;   // ⚠️ owed, but owed by the account — see the note above
        }

        $folio->loadMissing(['items', 'payments']);
        $balance = (int) FolioTotals::for($folio)['balance_minor'];

        if ($balance <= 0) {
            return;   // settled, or in credit
        }

        // ⚠️ THE SUPERVISOR OVERRIDE. Refused unless the reason is one of the KEYED values — free text is
        //    not accepted anywhere, because an override nobody can count is an override nobody can
        //    evaluate, and the honest measure of this whole block is the override RATE.
        if ($overrideReason !== null) {
            if (! CheckoutOverrideReason::isValid($overrideReason)) {
                throw new InvalidArgumentException('That is not a recognised checkout-override reason.');
            }

            if ($overrideReason === CheckoutOverrideReason::OTHER && trim((string) $overrideNote) === '') {
                throw new InvalidArgumentException('An "other" override needs a note saying what happened.');
            }

            // ⚠️ THE AMOUNT IS PART OF THE RECORD, not just the act: a €0.01 override and a €900 override
            //    are different events and must not look alike in six months.
            $this->audit->record('reservation.checkout_overridden', $reservation, null, array_filter([
                'reason' => $overrideReason,
                'note' => $overrideNote,
                'balance_minor' => $balance,
                'currency' => (string) $folio->currency,
            ], static fn ($v): bool => $v !== null && $v !== ''));

            return;   // ⚠️ permitted — and the balance is untouched
        }

        throw new OutstandingBalanceException(
            $balance,
            (string) $folio->currency,
            MoneyFormatter::display($balance, (string) $folio->currency),
        );
    }

    /**
     * ⚠️ THE LADDER'S BOUND (F-18) — see {@see CheckInReservation::assertTransition()} for the derivation.
     *
     * ⚠️⚠️ `confirmed` IS PERMITTED HERE, AND THAT IS A CORRECTION FORCED BY EVIDENCE, NOT A CONVENIENCE.
     * Phase 9 reported `confirmed → checked_out` as half of F-18. It is not illegal: 🔬 `DemoSeeder` performs
     * it TWICE through this very action (:605 the past review stay, :700 the walk-in), never checking in
     * first — so it is a transition THE PRODUCT PERFORMS. A first map that refused it reddened 41 tests across
     * five certified suites and **broke `migrate:fresh --seed`**. ⚠️ The guard was NOT widened to green those
     * tests; the map was re-derived after checking a source the first derivation missed. ⚠️ Whether an express
     * checkout with no arrival record SHOULD be legal is a founder ruling — docs/STATUS-LADDERS.md R4.
     *
     * ⚠️ What stays refused is the dangerous half: `cancelled`, `no_show` and (via the idempotent return)
     * a second `checked_out` — the states AssignmentService::TERMINAL_STATUSES already declares final.
     *
     * ⚠️⚠️ THIS ONE HAS SIDE EFFECTS AND THE CHECK-IN ONE DOES NOT, which is why it sits above every other
     * statement in `handle()`. 🔬 MEASURED by running an illegal checkout with this guard removed (on a
     * `cancelled` reservation): a departure housekeeping task was created, the unit flipped `clean → dirty`
     * ({@see GenerateDepartureCleanTask}), and 2 in-app notification rows were written. 🔬 No money and no
     * availability moved — neither action touches AssignmentService — which confirms Phase 9's money answer.
     *
     * ⚠️ THE REVIEW EMAIL IS A REACHABLE PATH THE FIXTURE DID NOT EXERCISE, and the distinction matters.
     * `SendGuestTransactionalEmails::reviewRequest()` is wired to `reservation.checked_out` and gated ONLY on
     * the org's `review-request` template being enabled and the guest's own `reviews` preference — NEITHER of
     * which is a status check. 🔬 It queued 0 mailables in the probe (one of those switches was closed in the
     * fixture), so it is NOT claimed here as having fired; in production with both open, an illegal checkout
     * would ask a guest to review a stay that never happened, and an email cannot be recalled.
     */
    private const ALLOWED_FROM = ['checked_in'];

    /**
     * ⚠️⚠️ RULED BY THE FOUNDER, 2026-09-19 — R4: `confirmed` → `checked_out` IS REFUSED. A stay must be
     * checked in before it can be checked out. The docblock above records the history: `confirmed` was
     * PERMITTED here from FIX 04 until this ruling, because the seeder performed it and the map was derived
     * from what the product did. The founder has now decided what the product SHOULD do, and the seeder was
     * changed to check in first rather than the guard widened to suit it.
     *
     * ⚠️ THE REFUSAL NAMES THE WAY OUT. A front desk that hits a bare "cannot do that" has no next move, so
     * the message says which transition covers each real case — otherwise the guard just teaches people to
     * route around it.
     */
    private function assertTransition(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if (in_array($from, self::ALLOWED_FROM, true)) {
            return;
        }

        throw new RuntimeException(match ($from) {
            // ⚠️ R4, and the case a front desk actually meets: the guest is leaving and was never arrived.
            'confirmed' => 'Cannot check out a confirmed reservation: a stay must be checked in before it can '
                .'be checked out. If the guest stayed and is leaving early, check them in first and then out. '
                .'If they never arrived, mark the booking a no-show instead.',
            'cancelled', 'no_show' => "Cannot check out a {$from} reservation: the booking is closed and no "
                .'stay took place. A guest who turned up after all needs a new booking.',
            default => "Cannot check out a {$from} reservation.",
        });
    }
}
