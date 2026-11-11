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
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\Review;
use App\Models\TeamConversation;
use App\Models\TeamConversationMember;
use App\Models\TeamMessage;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\FeedNotification;
use App\Notifications\Guest\GuestBookingCancelledNotification;
use App\Notifications\Guest\GuestBookingConfirmedNotification;
use App\Notifications\Guest\GuestMessageReceivedNotification;
use App\Notifications\Guest\GuestRefundProcessedNotification;
use App\Notifications\Guest\GuestReviewRequestNotification;
use App\Notifications\Operator\BalanceDueNotification;
use App\Notifications\Operator\ChannelSyncErrorNotification;
use App\Notifications\Operator\InboxMessageNotification;
use App\Notifications\Operator\LowRatingNotification;
use App\Notifications\Operator\NewBookingNotification;
use App\Notifications\Operator\NewReviewNotification;
use App\Notifications\Operator\OwnerPayoutSentNotification;
use App\Notifications\Operator\StatementFinalizedNotification;
use App\Notifications\Operator\TeamMessageNotification;
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
use App\Support\Inbox\ThreadName;
use App\Support\Notifications\NotificationRecipients;
use App\Support\TeamChat\TeamChatReader;
use App\Support\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
use Illuminate\Support\Traits\Localizable;
use Throwable;

class DispatchNotificationsForDomainEvent
{
    use Localizable;

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
        // ⚠️ GUEST NOTIFICATIONS · GATE 2 — two events this dispatcher did not previously handle. Both already
        // fire today and already email the guest (SendGuestTransactionalEmails); they are added here so the
        // IN-APP row is dispatched from the notification dispatcher, leaving that email listener untouched.
        'reservation.cancelled',
        'payment.refunded',
        // TEAM CHAT (2026-10-06) — a teammate wrote: one bell entry per conversation while unread, in-app only.
        'team_chat.message_sent',
        // THE GUESTS SIDE (2026-10-06) — a guest or a unit owner wrote: nobody on the staff was told before.
        'inbox.message_received',
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

        // ⚠️⚠️ A NOTIFICATION FAILURE MUST NOT ABORT THE LISTENER CHAIN, AND IT USED TO.
        //
        // This listener is one of several on `DomainEventRecorded`, they run SYNCHRONOUSLY in registration
        // order, and a throw here stops every listener after it. `AdjustOwnerLedgerOnRefund` — which posts
        // the owner's debit for a guest refund — was registered LATER, so a failure in here meant the guest
        // was refunded and the OWNER'S LEDGER WAS NEVER DEBITED. Measured end to end: refund `succeeded`,
        // `domain_events` row written, owner ledger unchanged, and the retry could not recover it because
        // `settleRefund`'s status guard returns early on an already-settled row.
        //
        // ⚠️ ITS SIBLING ALREADY DID THIS. `SendGuestTransactionalEmails` wraps its sends in try/catch and
        //    only logs, precisely so "a mail failure never breaks the event". This is the same rule for the
        //    same bus — an in-app notification is best-effort in exactly the way an email is, and neither
        //    is worth a money row. One convention, not two (§N.8f).
        //
        // ⚠️ IT SWALLOWS NOTHING SILENTLY: the failure is logged with the event type and id, and the
        //    domain event itself is already durably recorded, so the notification can be reconstructed.
        // ⚠️ THE GUARD IS INLINE RATHER THAN AROUND AN EXTRACTED METHOD, AND THAT IS DELIBERATE.
        //    Moving the `match` into its own method separated it from the
        //    `in_array(..., self::HANDLED)` early return that narrows `event_type`, so PHPStan could
        //    no longer prove the match exhaustive — and that exhaustiveness is a real safety property:
        //    adding a type to HANDLED without a branch must fail loudly, not fall through silently.
        try {
            // Dispatch inside the event's org context so tenant-scoped reads + the spatie team resolve correctly.
            $this->tenant->forOrganization($orgId, function () use ($domainEvent, $orgId): void {
                // Exhaustive over HANDLED (the early return above guarantees one of these).
                match ($domainEvent->event_type) {
                    'reservation.confirmed' => $this->newBooking($domainEvent, $orgId),
                    'reservation.checked_out' => $this->checkedOut($domainEvent, $orgId),
                    'unit.condition_changed' => $this->unitOutOfOrder($domainEvent, $orgId),
                    'maintenance_ticket.opened' => $this->urgentWorkOrder($domainEvent, $orgId),
                    'payout.paid' => $this->payoutSent($domainEvent, $orgId),
                    'owner_statement.finalized' => $this->statementFinalized($domainEvent, $orgId),
                    'channel.error' => $this->channelSyncError($domainEvent, $orgId),
                    'owner.payout_method_changed' => $this->accountSecurity($domainEvent),
                    'review.submitted' => $this->newReview($domainEvent, $orgId),
                    'inbox.thread_assigned' => $this->threadAssigned($domainEvent),
                    'inbox.message_sent' => $this->ownerMessage($domainEvent),
                    'reservation.cancelled' => $this->guestBookingCancelled($domainEvent),
                    'payment.refunded' => $this->guestRefundProcessed($domainEvent),
                    'team_chat.message_sent' => $this->teamMessage($domainEvent, $orgId),
                    'inbox.message_received' => $this->inboxMessage($domainEvent, $orgId),
                };
            });
        } catch (Throwable $e) {
            Log::warning('Domain-event notification dispatch failed', [
                'event_type' => $domainEvent->event_type,
                'domain_event' => $domainEvent->id,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ⚠️ GUEST NOTIFICATIONS · GATE 2 — `reservation.checked_out` now serves TWO independent audiences, so it
     * fans out rather than nesting.
     *
     * ⚠️ THIS SHAPE IS THE FIX FOR A REAL BUG, not tidiness. G7 was first appended to the END of
     * {@see balanceDue()}, which returns early when there is no folio or the balance is settled — so the
     * guest's review invitation would have fired ONLY for guests who still owed money, and most stays are
     * settled at check-out. The operator's balance-due notice and the guest's review invitation are unrelated
     * conditions; nesting one inside the other silently made the second depend on the first.
     */
    private function checkedOut(DomainEvent $domainEvent, string $orgId): void
    {
        $this->balanceDue($domainEvent, $orgId);
        $this->guestReviewRequest($domainEvent);
    }

    /**
     * G7 — invite the GUEST to review the stay they just completed. Additive: `SendGuestTransactionalEmails`
     * still sends ReviewRequestMail on this same event and is untouched.
     */
    private function guestReviewRequest(DomainEvent $domainEvent): void
    {
        $stay = Reservation::query()->with(['leadGuest', 'property'])->find($domainEvent->subject_id);
        if (! $stay instanceof Reservation) {
            return;
        }

        $stay->leadGuest?->notify(new GuestReviewRequestNotification($stay));
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

        // ⚠️ GUEST NOTIFICATIONS · G1 — and the GUEST whose booking this is. The third audience on the same
        // event, dispatched from the same place the operator and owner fan-outs already live.
        //
        // ⚠️ THIS DOES NOT REPLACE THE EMAIL. `SendGuestTransactionalEmails` sends BookingConfirmationMail on
        // this same event and is untouched — it still fires. The catalog entry's `email_default => false`
        // keeps this row IN-APP ONLY so the guest is not emailed twice.
        //
        // ⚠️ THE LEAD GUEST ONLY, resolved FROM THE RESERVATION — never a request id, and never every guest on
        // the stay. `Guest` already uses `Notifiable`, so this needs no schema change (#28: the notifiable IS
        // the subject of the booking, so no third party is told anything).
        $reservation->leadGuest?->notify(new GuestBookingConfirmedNotification($reservation));
    }

    /**
     * The distinct owners with a unit on this reservation, paired with that unit.
     *
     * @return list<array{0: Owner, 1: Unit}>
     */
    private function ownersOfReservation(Reservation $reservation): array
    {
        $lines = ReservationUnit::query()
            ->where('reservation_id', $reservation->getKey())
            ->whereNotNull('unit_id')
            ->get(['unit_id', 'check_in']);
        $unitIds = $lines->pluck('unit_id')->map(fn ($id): string => (string) $id)->unique()->values()->all();

        if ($unitIds === []) {
            return [];   // a type-only line (an OTA import before assignment) — no unit, so no owner yet
        }

        // ⚠️ DEC-p — the day each unit's stay starts here (the earliest line on it), so a stay that began before the owner took the unit over
        //    tells that owner nothing: it is not theirs (same rule as the earnings, `Unit::ownerIdForStayStarting()`).
        $startByUnit = [];
        foreach ($lines as $line) {
            $day = Carbon::parse($line->check_in)->toDateString();
            $key = (string) $line->unit_id;
            $startByUnit[$key] = isset($startByUnit[$key]) && $startByUnit[$key] < $day ? $startByUnit[$key] : $day;
        }

        $out = [];
        $seen = [];
        foreach (Unit::query()->whereIn('id', $unitIds)->get() as $unit) {
            $ownerId = $unit->ownerIdForStayStarting($startByUnit[(string) $unit->id]);
            if (! is_string($ownerId) || $ownerId === '' || isset($seen[$ownerId])) {
                continue;   // an unowned unit (or a stay from before the owner had it), or an owner already notified for this stay
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
        // ⚠️ FINANCE-GROUP (OP-FIX-3) — addressed only to roles that may read owner finance. It links to
        // `/app/owners`, which OP-FIX-2 gated on `reports.view`; sending it wider would put an owner's
        // payout figure in a housekeeper's feed and point them at a page they cannot open.
        $this->toOperatorsWithPermission($orgId, 'reports.view', new OwnerPayoutSentNotification($payout));
    }

    /** An owner statement was finalized → the ONE owner it belongs to AND the org's operators. */
    private function statementFinalized(DomainEvent $domainEvent, string $orgId): void
    {
        $statement = OwnerStatement::query()->find($domainEvent->subject_id);
        if (! $statement instanceof OwnerStatement) {
            return;
        }

        $statement->owner?->user?->notify(new StatementReadyNotification($statement));
        // ⚠️ FINANCE-GROUP (OP-FIX-3) — see payoutSent(): same audience, same linked page, same permission.
        $this->toOperatorsWithPermission($orgId, 'reports.view', new StatementFinalizedNotification($statement));
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
            // ⚠️ GUEST NOTIFICATIONS · GATE 2 — THE `else` NOBODY WROTE. This branch used to just return.
            // `message_threads` is `guest_id` XOR `owner_id`, so a thread with no owner is the operator↔GUEST
            // conversation: the guest is notified instead, from the property (never from an owner — owner↔guest
            // messaging does not exist).
            //
            // ⚠️⚠️ THIS ONE WIRE COVERS BOTH SENDING PATHS. The BULK send (GuestBulkActionController) posts
            // through `InboxService::sendOutbound()`, which records this same `inbox.message_sent` — so a bulk
            // send to N guests already reaches here N times, once per recipient, each self-scoped. Adding a
            // second notify in the bulk controller would give every bulk recipient TWO rows for one message.
            $thread->guest?->notify(new GuestMessageReceivedNotification($thread));

            return;
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

    /**
     * TEAM CHAT (2026-10-06) — a teammate wrote. Every OTHER member who still works here gets ONE bell entry for the
     * conversation: the earlier entry, read or not, is replaced by one that counts what is unread ("Max Brown sent you 2
     * messages"; in a group "Anna Lee wrote in Morning shift" / "3 new messages in Morning shift"). It names people, never
     * the text. ⚠️ A member whose membership ended keeps their place in the conversation but is never rung (the review).
     */
    private function teamMessage(DomainEvent $domainEvent, string $orgId): void
    {
        $message = TeamMessage::query()->with(['sender:id,name', 'conversation'])->find($domainEvent->subject_id);
        if (! $message instanceof TeamMessage || ! $message->conversation instanceof TeamConversation) {
            return;
        }
        $conversation = $message->conversation;
        $sender = $message->sender->name ?? null;
        $reader = app(TeamChatReader::class);

        $staff = $this->recipients->operatorsForOrg($orgId)->map(fn (User $u): string => (string) $u->getKey())->all();
        $recipients = User::query()->whereIn('id', $staff)->whereIn('id', TeamConversationMember::query()
            ->where('conversation_id', $conversation->getKey())->where('user_id', '!=', (string) $message->user_id)->select('user_id'))->get();

        foreach ($recipients as $recipient) {
            $unread = max(1, $reader->unreadIn($conversation, $recipient));
            $direct = $conversation->kind === TeamConversation::DIRECT;
            // The names are worked out in the READER's language (a group's "Anna, Max and 2 others"; a removed sender).
            [$name, $groupName] = $this->withLocale($recipient->preferredLocale(), fn (): array => [
                (string) ($sender ?? __('php/listeners/dispatch_notifications_for_domain_event.a_teammate')),
                $direct ? '' : $reader->displayName($conversation, $recipient),
            ]);

            $recipient->notifications()   // one entry per conversation — never a pile of read ones beneath it
                ->where('type', TeamMessageNotification::class)
                ->where('data->conversation_id', (string) $conversation->getKey())
                ->delete();
            $recipient->notify(new TeamMessageNotification((string) $conversation->getKey(), $direct, $name, $unread, $groupName));
        }
    }

    /**
     * THE GUESTS SIDE (2026-10-06) — a guest or a unit owner wrote. The person the conversation is assigned to (while they
     * may still read guest conversations), else everyone who may (`guests.view`), gets ONE bell entry for it — the earlier
     * entry, read or not, is replaced by one that counts ("2 new messages from Ines Ibarra"). Name only (#28).
     */
    private function inboxMessage(DomainEvent $domainEvent, string $orgId): void
    {
        $thread = MessageThread::query()->with(['guest', 'owner'])->find((string) ($domainEvent->payload['thread_id'] ?? ''));
        if (! $thread instanceof MessageThread) {
            return;
        }

        $readers = $this->recipients->operatorsForOrgWithPermission($orgId, 'guests.view');
        $assignee = $thread->assigned_to !== null ? $readers->firstWhere('id', (string) $thread->assigned_to) : null;
        $recipients = $assignee instanceof User ? collect([$assignee]) : $readers;

        $name = ThreadName::for($thread);
        $unread = max(1, (int) $thread->unread_count);

        foreach ($recipients as $recipient) {
            $recipient->notifications()   // one entry per conversation — never a pile of read ones beneath it
                ->where('type', InboxMessageNotification::class)
                ->where('data->thread_id', (string) $thread->getKey())
                ->delete();
            $recipient->notify(new InboxMessageNotification((string) $thread->getKey(), $name, $unread, $thread->owner_id !== null));
        }
    }

    /** Send one notification to every back-office recipient in the org (no-op when there are none). */
    private function toOperators(string $orgId, object $notification): void
    {
        $operators = $this->recipients->operatorsForOrg($orgId);
        if ($operators->isNotEmpty()) {
            Notification::send($operators, $notification);
        }
    }

    /**
     * Send one notification only to back-office recipients who hold a permission — the audience for a
     * notification whose CONTENT is gated on that permission (OP-FIX-3).
     *
     * ⚠️ This narrows WHO IS ADDRESSED, never WHAT IS SAID: the notification bodies are untouched, and they
     * carry no guest contact before or after (#28).
     */
    private function toOperatorsWithPermission(string $orgId, string $permission, object $notification): void
    {
        $operators = $this->recipients->operatorsForOrgWithPermission($orgId, $permission);
        if ($operators->isNotEmpty()) {
            Notification::send($operators, $notification);
        }
    }

    /**
     * ⚠️ GUEST NOTIFICATIONS · GATE 2 · G5 — the guest's booking was cancelled.
     *
     * Additive: `SendGuestTransactionalEmails` still sends CancellationMail on this same event and is
     * untouched. Only the LEAD guest is notified, resolved FROM the reservation — never a request id.
     */
    private function guestBookingCancelled(DomainEvent $domainEvent): void
    {
        $reservation = Reservation::query()->with(['leadGuest', 'property'])->find($domainEvent->subject_id);
        if (! $reservation instanceof Reservation) {
            return;
        }

        $reservation->leadGuest?->notify(new GuestBookingCancelledNotification($reservation));
    }

    /**
     * ⚠️ GUEST NOTIFICATIONS · GATE 2 · G6 — a refund was processed on the guest's booking.
     *
     * ⚠️ SUCCEEDED REFUNDS ONLY — the same condition the email applies. A refund that failed tells the guest
     * nothing true, and a row claiming otherwise would be worse than none.
     */
    private function guestRefundProcessed(DomainEvent $domainEvent): void
    {
        $refund = Payment::query()->with(['folio.reservation.leadGuest', 'folio.reservation.property'])
            ->find($domainEvent->subject_id);
        if (! $refund instanceof Payment || (string) $refund->status !== 'succeeded') {
            return;
        }

        $reservation = $refund->folio?->reservation;
        if (! $reservation instanceof Reservation) {
            return;
        }

        $reservation->leadGuest?->notify(new GuestRefundProcessedNotification($reservation));
    }
}
