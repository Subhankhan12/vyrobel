<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Notifications\FeedNotification;

/**
 * #108 (Work Order · Reassign · "Notify both technicians") — a technician is told about a reassignment: the new
 * assignee that they now own the order, or the prior assignee that they are off it. Real values only (#39): the
 * title + unit + which side this recipient is. Maintenance category; links to the ticket. Sent ONLY when the
 * operator kept the toggle on.
 */
class MaintenanceReassignedNotification extends FeedNotification
{
    public function __construct(
        private string $ticketId,
        private string $title,
        private string $unit,
        private bool $isNewAssignee,
    ) {}

    public function eventKey(): string
    {
        return 'maintenance_reassigned';
    }

    public function category(): string
    {
        return 'maintenance';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => $this->isNewAssignee ? 'Work order assigned to you · '.$this->title : 'Work order reassigned · '.$this->title,
            'context' => 'Unit '.$this->unit.($this->isNewAssignee ? ' — it is now yours' : ' — you are off this one'),
            'url' => route('app.maintenance.show', $this->ticketId, false),
            'category' => 'maintenance',
        ];
    }
}
