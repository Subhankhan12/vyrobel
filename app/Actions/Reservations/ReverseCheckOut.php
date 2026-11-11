<?php

declare(strict_types=1);

namespace App\Actions\Reservations;

use App\Models\GuestReviewInvite;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\ReservationStatusHistory;
use App\Models\User;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use App\Support\I18n\LocalDate;
use App\Support\Reservations\ReservationStatusLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * R3 (founder, 2026-09-19) — REVERSE A MIS-CLICKED CHECKOUT, AND DISCLOSE WHAT CANNOT BE REVERSED.
 *
 * ⚠️⚠️ THIS IS NOT A STATUS FLIP, AND A STATUS FLIP IS WHAT THE RULING REFUSED. A checkout fans out: it
 * creates a departure cleaning task, dirties the unit, notifies operators and the guest, and EMAILS the guest
 * a review request. Flipping `checked_out` back to `checked_in` while all of that stands would be a FALSE
 * UNDO — something that looks like a rollback and is not.
 *
 * ⚠️⚠️ SO THIS ACTION IS DELIBERATELY SEPARATE FROM {@see CheckInReservation}, WHICH STILL ONLY ACCEPTS
 * `confirmed` (R1/R4, unchanged). A plain check-in must never silently reverse a checkout — the reversal is a
 * named operation with its own disclosure, or the consequences arrive after the press instead of before it.
 *
 * ⚠️ WHAT IT DOES, AND WHAT IT REFUSES TO DECIDE:
 *
 *   • the STATUS is reversed                                    — reversible
 *   • a still-PENDING departure task is removed                 — nobody has started it
 *   • a STARTED task is LEFT ALONE and disclosed                — ⚠️ cancelling someone's in-progress work is
 *                                                                 an instruction, not an undo
 *   • the unit's `dirty` flag is LEFT ALONE and disclosed       — ⚠️ the guest is back in the room; asserting
 *                                                                 "clean" could be false the moment it is written
 *   • notifications already delivered are LEFT and disclosed    — someone may already have read them
 *   • the review-request EMAIL is disclosed, never "undone"     — it cannot be recalled
 *
 * ⚠️ AND THE THREE-STATE ANSWER ABOUT THAT EMAIL COMES FROM {@see GuestReviewInvite::stateFor()} — never from
 * testing a column for emptiness. A stay that checked out before the record existed is UNKNOWN, not "no".
 */
class ReverseCheckOut
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * ⚠️ WHAT WOULD HAPPEN — the confirmation surface reads THIS, so the operator sees every consequence
     * BEFORE pressing. It writes nothing.
     *
     * @return array{reversible: list<string>, not_reversed: list<string>, review_email: array<string, mixed>}
     */
    public function preview(Reservation $reservation): array
    {
        $this->assertReversible($reservation);

        $tasks = $this->departureTasks($reservation);
        $pending = $tasks->filter(fn (HousekeepingTask $t): bool => (string) $t->status === 'pending');
        $started = $tasks->reject(fn (HousekeepingTask $t): bool => (string) $t->status === 'pending');

        $reversible = [__('php/actions/reservations/reverse_check_out.reversible_status')];
        if ($pending->isNotEmpty()) {
            $reversible[] = trans_choice('php/actions/reservations/reverse_check_out.pending_tasks_removed', $pending->count(), ['count' => $pending->count()]);
        }

        $notReversed = [];
        if ($started->isNotEmpty()) {
            $notReversed[] = trans_choice('php/actions/reservations/reverse_check_out.started_tasks_kept', $started->count(), ['count' => $started->count()]);
        }
        $notReversed[] = __('php/actions/reservations/reverse_check_out.unit_stays_dirty');
        $notReversed[] = __('php/actions/reservations/reverse_check_out.notifications_stay_delivered');

        return [
            'reversible' => $reversible,
            'not_reversed' => $notReversed,
            'review_email' => $this->reviewEmailDisclosure($reservation),
        ];
    }

    /**
     * ⚠️ THE DISCLOSURE IS RETURNED, NOT LOGGED AND FORGOTTEN — the caller renders it. An undo whose
     * consequences only appear afterwards is the failure mode this was built to avoid.
     *
     * @return array{reversible: list<string>, not_reversed: list<string>, review_email: array<string, mixed>}
     */
    public function handle(Reservation $reservation, ?User $by = null): array
    {
        $disclosure = $this->preview($reservation);

        $fromStatus = (string) $reservation->status;

        DB::transaction(function () use ($reservation, $fromStatus, $by): void {
            $reservation->update(['status' => 'checked_in']);

            // ⚠️ APPENDED, NEVER EDITED (#7). The checkout's own history row stays exactly where it is — the
            //    stay really did check out, and erasing that would be a second lie.
            ReservationStatusHistory::create([
                'reservation_id' => $reservation->getKey(),
                'from_status' => $fromStatus,
                'to_status' => 'checked_in',
                'changed_by' => $by?->getKey() ?? Auth::id(),
                'reason' => 'checkout reversed',
                'created_at' => now(),
            ]);

            // ⚠️ ONLY THE UNSTARTED ONES. See the class docblock.
            $this->departureTasks($reservation)
                ->filter(fn (HousekeepingTask $t): bool => (string) $t->status === 'pending')
                ->each(fn (HousekeepingTask $t) => $t->delete());
        });

        $this->domainEvents->record('reservation.checkout_reversed', $reservation, [
            'property_id' => $reservation->property_id,
            'review_request_state' => $disclosure['review_email']['state'],
        ]);
        $this->audit->record('reservation.checkout_reversed', $reservation,
            ['status' => $fromStatus], ['status' => 'checked_in']);

        return $disclosure;
    }

    /** @return Collection<int, HousekeepingTask> */
    private function departureTasks(Reservation $reservation): Collection
    {
        $unitIds = $reservation->reservationUnits()->whereNotNull('unit_id')->pluck('unit_id')->all();

        if ($unitIds === []) {
            return collect();
        }

        return HousekeepingTask::query()
            ->whereIn('unit_id', $unitIds)
            ->where('type', 'departure')
            ->get();
    }

    /**
     * ⚠️⚠️ THREE STATES, NEVER TWO. "No row" is UNKNOWN — it means the stay checked out before the product
     * started recording, and saying "no request was sent" about it would be the precise lie R3 exists to stop.
     *
     * @return array{state: string, sent: bool, known: bool, message: string}
     */
    private function reviewEmailDisclosure(Reservation $reservation): array
    {
        $state = GuestReviewInvite::stateFor((string) $reservation->getKey());

        $message = match (true) {
            ! $state['known'] => __('php/actions/reservations/reverse_check_out.review_unknown'),
            $state['sent'] => $state['sent_at'] !== null
                ? __('php/actions/reservations/reverse_check_out.review_sent_on', ['date' => $state['sent_at']->translatedFormat(LocalDate::pattern('j M Y, H:i'))])
                : __('php/actions/reservations/reverse_check_out.review_sent'),
            $state['state'] === GuestReviewInvite::SUPPRESSED_BY_OPERATOR => __('php/actions/reservations/reverse_check_out.review_suppressed_by_operator'),
            $state['state'] === GuestReviewInvite::SUPPRESSED_BY_GUEST => __('php/actions/reservations/reverse_check_out.review_suppressed_by_guest'),
            default => __('php/actions/reservations/reverse_check_out.review_not_sent'),
        };

        return ['state' => $state['state'], 'sent' => $state['sent'], 'known' => $state['known'], 'message' => $message];
    }

    private function assertReversible(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if ($from !== 'checked_out') {
            throw new RuntimeException(__('php/actions/reservations/reverse_check_out.refused_not_checked_out', ['status' => ReservationStatusLabel::for((string) $from)]));
        }
    }
}
