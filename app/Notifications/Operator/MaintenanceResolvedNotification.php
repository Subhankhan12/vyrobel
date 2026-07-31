<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Notifications\FeedNotification;

/**
 * #108 (Work Order · Mark-resolved · "Notify the front desk") — the ticket's reporter is told a work order is
 * resolved (and, when it returned to sale, back online). Real values only (#39): the title + unit + whether the
 * unit is back on sale. Maintenance category; links to the ticket. Sent ONLY when the operator kept the toggle on.
 */
class MaintenanceResolvedNotification extends FeedNotification
{
    public function __construct(
        private string $ticketId,
        private string $title,
        private string $unit,
        private bool $backOnSale,
    ) {}

    public function eventKey(): string
    {
        return 'maintenance_resolved';
    }

    public function category(): string
    {
        return 'maintenance';
    }

    public function data(object $notifiable): array
    {
        return [
            'title' => 'Work order resolved · '.$this->title,
            'context' => 'Unit '.$this->unit.($this->backOnSale ? ' — fixed and back online' : ' — resolved (still out of order)'),
            'url' => route('app.maintenance.show', $this->ticketId, false),
            'category' => 'maintenance',
        ];
    }
}
