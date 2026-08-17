<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Models\Channel;
use App\Models\DomainEvent;
use App\Models\Folio;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\MessageThread;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Review;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\FeedNotification;
use App\Notifications\Operator\BalanceDueNotification;
use App\Notifications\Operator\ChannelSyncErrorNotification;
use App\Notifications\Operator\LowRatingNotification;
use App\Notifications\Operator\NewBookingNotification;
use App\Notifications\Operator\NewReviewNotification;
use App\Notifications\Operator\OwnerPayoutSentNotification;
use App\Notifications\Operator\StatementFinalizedNotification;
use App\Notifications\Operator\ThreadAssignedNotification;
use App\Notifications\Operator\UnitOutOfOrderNotification;
use App\Notifications\Operator\UrgentWorkOrderNotification;
use App\Notifications\Owner\AccountSecurityNotification;
use App\Notifications\Owner\OwnerMessageReceivedNotification;
use App\Notifications\Owner\OwnerNewBookingNotification;
use App\Notifications\Owner\OwnerReviewPostedNotification;
use App\Notifications\Owner\PayoutSentNotification;
use App\Notifications\Owner\StatementReadyNotification;
use App\Support\Folio\FolioTotals;
use App\Support\Guest\GuestName;
use App\Support\Notifications\NotificationRecipients;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Notification;

/**
 * Turns REAL domain events into notifications (Phase 3). Reacts to {@see DomainEventRecorded} — the same
 * synchronous bus the availability/ARI listeners use — and, for the event types it maps, resolves the correct
 * isolation-scoped recipient(s) and dispatches. It NEVER invents an event: it only handles `event_type`s that
 * actually fire today. Delivery is preference-gated inside {@see FeedNotification::via()}, so a
 * user's off toggle suppresses their copy. Runs in the event's org context so tenant reads + role checks resolve.
 *
 * ISOLATION: operator notifications go to the org's back-office staff ({@see NotificationRecipients}); owner
 * notifications go to the ONE owner the subject belongs to (via their linked user) — never another owner.
 *
 * DEFERRED HONESTLY (no backing event today — NOT wired, so nothing fake fires): arrivals-due / housekeeping-
 * behind / SLA-breach / daily-summary (all need a scheduler, not a discrete event) · new-device sign-in (needs
 * device detection — the OTHER half of account & security) · performance digest (needs a monthly aggregator) ·
 * terms-changed (no agreement-change event is recorded anywhere). (new-review is now WIRED — Phase 5.)
 */
class DispatchNotificationsForDomainEvent
{
    /** The event types this listener maps to notifications. Each has a match arm below (the two stay in sync). */
    private const HANDLED = [
        'reservation.confirmed',
        'reservation.checked_out',
        'unit.condition_changed',
        'maintenance_ticket.opened',
        'payout.paid',
        'owner_statement.finalized',
        'channel.error',
        'owner.payout_method_changed',
        'review.submitted',
        'inbox.thread_assigned',
        // ⚠️ OWNER FIX 2 — the OPERATOR's direction only. The owner's own reply is recorded `inbound` by
        // InboxService::postOwnerMessage(), so an owner is never notified about their own message.
        'inbox.message_sent',
    ];

    /**
     * Domain-event ids already turned into notifications — an IDEMPOTENCY guard. The sync event bus delivers a
     * given DomainEventRecorded to this listener MORE THAN ONCE (the listeners in app/Listeners are BOTH
     * auto-discovered AND manually registered in AppServiceProvider — the idempotent inventory listeners
     * tolerate the double-fire, but a notification must not be created twice). Keyed on the unique domain-event
     * id, so each real event produces its notifications exactly once, whatever the bus does.
     *
     * @var array<string, true>
     */
    private static array $processed = [];

    public function __construct(
        private TenantContext $tenant,
        private NotificationRecipients $recipients,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;

        if (! in_array($domainEvent->event_type, self::HANDLED, true)) {
            return;
        }

        $key = (string) $domainEvent->id;
        if (isset(self::$processed[$key])) {
            return;   // already dispatched for this exact event — the double-fire guard
        }
        self::$processed[$key] = true;

        $orgId = $domainEvent->organization_id;
        if (! is_string($orgId) || $orgId === '') {
            return;
        }

        // Dispatch inside the event's org context so tenant-scoped reads + the spatie team resolve correctly.
        $this->tenant->forOrganization($orgId, function () use ($domainEvent, $orgId): void {
            // Exhaustive over HANDLED (the early return above guarantees one of these).
            match ($domainEvent->event_type) {
                'reservation.confirmed' => $this->newBooking($domainEvent, $orgId),
                'reservation.checked_out' => $this->balanceDue($domainEvent, $orgId),
                'unit.condition_changed' => $this->unitOutOfOrder($domainEvent, $orgId),
                'maintenance_ticket.opened' => $this->urgentWorkOrder($domainEvent, $orgId),
                'payout.paid' => $this->payoutSent($domainEvent, $orgId),
                'owner_statement.finalized' => $this->statementFinalized($domainEvent, $orgId),
                'channel.error' => $this->channelSyncError($domainEvent, $orgId),
                'owner.payout_method_changed' => $this->accountSecurity($domainEvent),
                'review.submitted' => $this->newReview($domainEvent, $orgId),
                'inbox.thread_assigned' => $this->threadAssigned($domainEvent),
                'inbox.message_sent' => $this->ownerMessage($domainEvent),
            };
        });
    }

    /** A new confirmed booking → the org's operator staff. */
    private function newBooking(DomainEvent $domainEvent, string $orgId): void
    {
        $reservation = Reservation::query()->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;
        }

        $this->toOperators($orgId, new NewBookingNotification($reservation));

        // ⚠️ OWNER FIX 2 — and the OWNER of each unit on the stay, once each. A reservation can span units
        // belonging to different owners, and each may be notified only about their OWN (isolation): the owner
        // is resolved from the unit, never from the reservation, so there is no path to another owner's stay.
        foreach ($this->ownersOfReservation($reservation) as [$owner, $unit]) {
            $owner->user?->notify(new OwnerNewBookingNotification($reservation, $unit));
        }
    }

    /**
     * The distinct owners with a unit on this reservation, paired with that unit.
     *
     * @return list<array{0: Owner, 1: Unit}>
     */
    private function ownersOfReservation(Reservation $reservation): array
    {
        $unitIds = ReservationUnit::query()
            ->where('reservation_id', $reservation->getKey())
            ->pluck('unit_id')
            ->filter()
            ->all();

        if ($unitIds === []) {
            return [];   // a type-only line (an OTA import before assignment) — no unit, so no owner yet
        }

        $out = [];
        $seen = [];
        foreach (Unit::query()->whereIn('id', $unitIds)->get() as $unit) {
            $ownerId = $unit->owner_id;
            if (! is_string($ownerId) || $ownerId === '' || isset($seen[$ownerId])) {
                continue;   // an unowned unit, or an owner already notified for this stay
            }
            $owner = Owner::query()->find($ownerId);
            if ($owner instanceof Owner) {
                $seen[$ownerId] = true;
                $out[] = [$owner, $unit];
            }
        }

        return $out;
    }

    /** A guest checked out still owing money → the org's operators (ONLY when the folio balance is > 0). */
    private function balanceDue(DomainEvent $domainEvent, string $orgId): void
    {
        $reservation = Reservation::query()->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;
        }

        $folio = Folio::query()->where('reservation_id', $reservation->getKey())->first();
        if ($folio === null) {
            return;
        }

        $balance = FolioTotals::for($folio)['balance_minor'];
        if ($balance <= 0) {
            return;   // settled in full — nothing to chase
        }

        $this->toOperators($orgId, new BalanceDueNotification($reservation, $balance, (string) $folio->currency));
    }

    /** A unit was taken out of service → the org's operators (ONLY on the transition INTO out_of_service). */
    private function unitOutOfOrder(DomainEvent $domainEvent, string $orgId): void
    {
        $payload = $domainEvent->payload ?? [];
        if (($payload['to'] ?? null) !== 'out_of_service' || ($payload['from'] ?? null) === 'out_of_service') {
            return;   // only the IN-transition; returning to service (or a no-op) does not notify
        }

        $unit = Unit::query()->find($domainEvent->subject_id);
        if (! $unit instanceof Unit) {
            return;
        }

        $this->toOperators($orgId, new UnitOutOfOrderNotification($unit));
    }

    /**
     * An URGENT maintenance ticket opened → the org's operators — but ONLY when the org's Maintenance Settings
     * opt in (`notify_new_urgent`). This is the ORG-level gate that makes the Phase-1 maintenance toggle DO
     * something; the per-user `urgent_work_order` preference then gates each recipient's delivery.
     */
    private function urgentWorkOrder(DomainEvent $domainEvent, string $orgId): void
    {
        $ticket = MaintenanceTicket::query()->find($domainEvent->subject_id);
        if (! $ticket instanceof MaintenanceTicket || (string) $ticket->priority !== 'urgent') {
            return;
        }

        // Org-level gate — no row means the migration default (notify on new urgent). Read-only, no side effect.
        $setting = MaintenanceSetting::query()->first();
        if ($setting !== null && ! $setting->notify_new_urgent) {
            return;
        }

        $this->toOperators($orgId, new UrgentWorkOrderNotification($ticket));
    }

    /** A payout was paid → the ONE owner it belongs to (owner-scoped) AND the org's operators (tenant-scoped). */
    private function payoutSent(DomainEvent $domainEvent, string $orgId): void
    {
        $payout = Payout::query()->find($domainEvent->subject_id);
        if (! $payout instanceof Payout) {
            return;
        }

        $payout->owner?->user?->notify(new PayoutSentNotification($payout));
        $this->toOperators($orgId, new OwnerPayoutSentNotification($payout));
    }

    /** An owner statement was finalized → the ONE owner it belongs to AND the org's operators. */
    private function statementFinalized(DomainEvent $domainEvent, string $orgId): void
    {
        $statement = OwnerStatement::query()->find($domainEvent->subject_id);
        if (! $statement instanceof OwnerStatement) {
            return;
        }

        $statement->owner?->user?->notify(new StatementReadyNotification($statement));
        $this->toOperators($orgId, new StatementFinalizedNotification($statement));
    }

    /** A channel connection errored → the org's operators. */
    private function channelSyncError(DomainEvent $domainEvent, string $orgId): void
    {
        $channel = Channel::query()->find($domainEvent->subject_id);
        if (! $channel instanceof Channel) {
            return;
        }

        $this->toOperators($orgId, new ChannelSyncErrorNotification($channel));
    }

    /**
     * A bank / payout-method change → the ONE owner (their linked user). This is the ALWAYS-ON "Account &
     * security" notification — the catalog flags it always_on, so it is never suppressed by a preference.
     */
    private function accountSecurity(DomainEvent $domainEvent): void
    {
        $owner = Owner::query()->find($domainEvent->subject_id);
        $user = $owner?->user;
        if ($user === null) {
            return;
        }

        $change = (string) (($domainEvent->payload['change'] ?? '') ?: '');
        $user->notify(new AccountSecurityNotification($change));
    }

    /**
     * A guest left a review → the org's operators (preference-gated on `new_review`). #90: when the REAL rating is
     * under 4★, ALSO fire the more-urgent `low_rating` alert (a real branch on the actual rating — never fabricated).
     */
    private function newReview(DomainEvent $domainEvent, string $orgId): void
    {
        $review = Review::query()->find($domainEvent->subject_id);
        if (! $review instanceof Review) {
            return;
        }

        $this->toOperators($orgId, new NewReviewNotification($review));

        if ((int) $review->rating < 4) {
            $this->toOperators($orgId, new LowRatingNotification($review));
        }

        // ⚠️ OWNER FIX 2 — and the ONE owner whose unit was reviewed. Resolved through the review's unit, so
        // a review of another owner's unit can never reach this owner. Fires on the review the operators are
        // told about; the owner's copy names no reviewer (#28).
        $ownerId = $review->unit?->owner_id;
        if (is_string($ownerId) && $ownerId !== '') {
            Owner::query()->find($ownerId)?->user?->notify(new OwnerReviewPostedNotification($review));
        }
    }

    /**
     * OWNER FIX 2 — an operator message on an OWNER thread → that ONE owner.
     *
     * ⚠️ A GUEST THREAD IS SKIPPED BY CONSTRUCTION: the two kinds are discriminated by `guest_id` XOR
     * `owner_id`, so a null `owner_id` simply returns. That is also why this cannot leak a guest conversation
     * to an owner — there is no owner to resolve on one.
     */
    private function ownerMessage(DomainEvent $domainEvent): void
    {
        $threadId = (string) (($domainEvent->payload['thread_id'] ?? '') ?: '');
        $thread = $threadId !== '' ? MessageThread::query()->find($threadId) : null;
        if (! $thread instanceof MessageThread) {
            return;
        }

        $ownerId = $thread->owner_id;
        if (! is_string($ownerId) || $ownerId === '') {
            return;   // an operator↔guest thread — not the owner's conversation
        }

        Owner::query()->find($ownerId)?->user?->notify(new OwnerMessageReceivedNotification($thread));
    }

    /**
     * A conversation was assigned to a staff member (#90) → notify THAT ONE assignee in-app (the real form of
     * "notified on their device"). Skips an unassign (assigned_to null) and a stale/foreign assignee. The feed
     * row carries the guest NAME only (#28) — an assignment is internal, never sent on a guest channel.
     */
    private function threadAssigned(DomainEvent $domainEvent): void
    {
        $assignedTo = (string) (($domainEvent->payload['assigned_to'] ?? '') ?: '');
        if ($assignedTo === '') {
            return;   // an unassign — nobody to notify
        }

        $thread = MessageThread::query()->find($domainEvent->subject_id);
        if (! $thread instanceof MessageThread) {
            return;
        }

        $assignee = User::query()->find($assignedTo);
        if (! $assignee instanceof User) {
            return;   // stale/foreign assignee — no ghost notification
        }

        $assignee->notify(new ThreadAssignedNotification($thread, GuestName::display($thread->guest)));
    }

    /** Send one notification to every back-office recipient in the org (no-op when there are none). */
    private function toOperators(string $orgId, object $notification): void
    {
        $operators = $this->recipients->operatorsForOrg($orgId);
        if ($operators->isNotEmpty()) {
            Notification::send($operators, $notification);
        }
    }
}
