<?php

declare(strict_types=1);

namespace App\Notifications\Operator;

use App\Models\Unit;
use App\Notifications\FeedNotification;

/**
 * Operator: a unit was taken out of service (Phase 3) — dispatched on the REAL `unit.condition_changed` domain
 * event, but ONLY on the transition INTO `out_of_service` (the dispatcher checks the event payload's `to`, so
 * returning a unit to service does not notify). Maintenance category; links to the maintenance board.
 */
class UnitOutOfOrderNotification extends FeedNotification
{
    public function __construct(private Unit $unit) {}

    public function eventKey(): string
    {
        return 'unit_out_of_order';
    }

    public function category(): string
    {
        return 'maintenance';
    }

    public function data(object $notifiable): array
    {
        $label = (string) ($this->unit->name ?: $this->unit->code);

        return [
            'title' => 'Unit out of service · '.$label,
            'context' => 'Taken out of service — availability updated',
            'url' => route('app.maintenance', [], false),
            'category' => 'maintenance',
        ];
    }
}
