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

        $reversible = ['The stay returns to checked in.'];
        if ($pending->isNotEmpty()) {
            $reversible[] = $pending->count().' departure cleaning task'.($pending->count() === 1 ? '' : 's')
                .' will be removed — not started yet.';
        }

        $notReversed = [];
        if ($started->isNotEmpty()) {
            $notReversed[] = $started->count().' cleaning task'.($started->count() === 1 ? ' has' : 's have')
                .' already been started and will be left alone — cancelling work someone has begun is a decision for housekeeping, not an undo.';
        }
        $notReversed[] = 'The unit stays marked dirty. The guest is back in the room, so "clean" may not be true.';
        $notReversed[] = 'Notifications already delivered to staff and the guest stay delivered.';

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
            ! $state['known'] => 'We have no record of whether a review request was sent for this stay, because '
                .'it checked out before this product started recording them. It may or may not have been sent.',
            $state['sent'] => 'A review request was emailed to the guest'
                .($state['sent_at'] !== null ? ' on '.$state['sent_at']->format('j M Y, H:i') : '')
                .'. It cannot be recalled.',
            $state['state'] === GuestReviewInvite::SUPPRESSED_BY_OPERATOR => 'No review request was sent — this '
                .'property has the review-request email switched off.',
            $state['state'] === GuestReviewInvite::SUPPRESSED_BY_GUEST => 'No review request was sent — the '
                .'guest has asked not to receive review emails.',
            default => 'No review request was sent — there was no contact address, or the send failed.',
        };

        return ['state' => $state['state'], 'sent' => $state['sent'], 'known' => $state['known'], 'message' => $message];
    }

    private function assertReversible(Reservation $reservation): void
    {
        $from = (string) $reservation->status;

        if ($from !== 'checked_out') {
            throw new RuntimeException(
                "Only a checked-out stay can have its checkout reversed; this one is {$from}."
            );
        }
    }
}
