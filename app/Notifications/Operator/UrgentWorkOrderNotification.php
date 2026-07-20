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
        $title = (string) ($this->ticket->title ?? 'Maintenance');
        $category = (string) ($this->ticket->category ?? '');

        return [
            'title' => 'Urgent work order · '.$title,
            'context' => ($category !== '' ? ucfirst($category).' · ' : '').'needs attention now',
            'url' => route('app.maintenance', [], false),
            'category' => 'maintenance',
        ];
    }
}
