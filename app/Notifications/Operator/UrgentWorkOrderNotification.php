<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\MaintenanceTicket;
use App\Notifications\FeedNotification;

/**
 * Operator: an URGENT maintenance ticket opened (Phase 3 · Sub-gate 2) — dispatched on the REAL
 * `maintenance_ticket.opened` event for urgent-priority tickets, but ONLY when the org's Maintenance Settings
 * opt in (`notify_new_urgent`) — this is what finally wires the Phase-1 maintenance notification toggle to real
 * dispatch. Per-user delivery is then gated by the `urgent_work_order` preference like any other.
 */
class UrgentWorkOrderNotification extends FeedNotification
{
    public function __construct(private MaintenanceTicket $ticket) {}

    public function eventKey(): string
    {
        return 'urgent_work_order';
    }

    public function category(): string
    {
        return 'maintenance';
    }

    public function data(object $notifiable): array
    {
        // I18N-01 — the category picks its own whole sentence (never a translated word passed in). `title` is a
        // required string and `category` a database enum of these five codes (default 'other'), so 'other' is the
        // remaining arm.
        $context = match ($this->ticket->category) {
            'plumbing' => $this->t('context', 'php/notifications/operator/urgent_work_order_notification.context_plumbing'),
            'electrical' => $this->t('context', 'php/notifications/operator/urgent_work_order_notification.context_electrical'),
            'appliance' => $this->t('context', 'php/notifications/operator/urgent_work_order_notification.context_appliance'),
            'structural' => $this->t('context', 'php/notifications/operator/urgent_work_order_notification.context_structural'),
            default => $this->t('context', 'php/notifications/operator/urgent_work_order_notification.context_other'),
        };

        return [
            'title' => $this->t('title', 'php/notifications/operator/urgent_work_order_notification.title', ['title' => (string) $this->ticket->title]),
            'context' => $context,
            'url' => route('app.maintenance', [], false),
            'category' => 'maintenance',
        ];
    }
}
