<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Models\DomainEvent;
use App\Models\MaintenanceSetting;
use App\Models\MaintenanceTicket;
use App\Models\Payout;
use App\Models\Reservation;
use App\Notifications\Operator\NewBookingNotification;
use App\Notifications\Operator\UrgentWorkOrderNotification;
use App\Notifications\Owner\PayoutSentNotification;
use App\Support\Notifications\NotificationRecipients;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Notification;

/**
 * Turns REAL domain events into notifications (Phase 3). Reacts to {@see DomainEventRecorded} — the same
 * synchronous bus the availability/ARI listeners use — and, for the event types it maps, resolves the correct
 * isolation-scoped recipient(s) and dispatches. It NEVER invents an event: it only handles `event_type`s that
 * actually fire today; unmapped events are ignored, and events whose backing feature is a later phase are simply
 * not mapped (deferred honestly). Runs in the event's org context so tenant-scoped reads + role checks resolve.
 *
 * Sub-gate 1 maps two events (one per recipient type) to prove the pipe end-to-end; Sub-gate 3 extends the match
 * with the rest of the real events, and Sub-gate 2 makes {@see FeedNotification::via()} preference-gated.
 */
class DispatchNotificationsForDomainEvent
{
    /** The event types this listener maps to notifications (extended in Sub-gate 3). */
    private const HANDLED = ['reservation.confirmed', 'payout.paid', 'maintenance_ticket.opened'];

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
            // Exhaustive over HANDLED (the early return above guarantees one of these); Sub-gate 3 adds an
            // arm here for every event type it adds to HANDLED, so the two stay in sync.
            match ($domainEvent->event_type) {
                'reservation.confirmed' => $this->newBooking($domainEvent, $orgId),
                'payout.paid' => $this->payoutSent($domainEvent),
                'maintenance_ticket.opened' => $this->urgentWorkOrder($domainEvent, $orgId),
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

        $operators = $this->recipients->operatorsForOrg($orgId);
        if ($operators->isNotEmpty()) {
            Notification::send($operators, new NewBookingNotification($reservation));
        }
    }

    /** A payout was paid → the ONE owner it belongs to (via their linked user). */
    private function payoutSent(DomainEvent $domainEvent): void
    {
        $payout = Payout::query()->find($domainEvent->subject_id);
        $user = $payout?->owner?->user;
        if ($user !== null) {
            $user->notify(new PayoutSentNotification($payout));
        }
    }

    /**
     * An URGENT maintenance ticket opened → the org's operators — but ONLY when the org's Maintenance Settings
     * opt in (`notify_new_urgent`). This is the ORG-level gate that finally makes the Phase-1 maintenance toggle
     * DO something; the per-user `urgent_work_order` preference then gates each recipient's delivery.
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

        $operators = $this->recipients->operatorsForOrg($orgId);
        if ($operators->isNotEmpty()) {
            Notification::send($operators, new UrgentWorkOrderNotification($ticket));
        }
    }
}
