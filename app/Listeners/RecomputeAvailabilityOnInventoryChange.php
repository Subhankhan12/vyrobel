<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Services\AvailabilityService;
use App\Support\Tenancy\TenantContext;

/**
 * Defense-in-depth availability recompute. Reacts to INVENTORY-STRUCTURE domain events only
 * (unit/block changes) and recomputes `total`/`out_of_order`/`blocked` for the affected
 * room_type + date range.
 *
 * It deliberately does NOTHING for reservation events: `sold` is owned exclusively by
 * AssignmentService (mutated inline in its locked transaction). This listener NEVER touches
 * `sold` and NEVER emits domain events (which would loop). Runs synchronously — no queue.
 */
class RecomputeAvailabilityOnInventoryChange
{
    /**
     * Event types whose payload ({room_type_id, start_date, end_date}) triggers a recompute.
     */
    private const TRIGGERS = [
        'inventory_block.created',
        'inventory_block.released',
        'unit.condition_changed',
        'unit.activated',
        'unit.deactivated',
    ];

    public function __construct(
        private AvailabilityService $availability,
        private TenantContext $tenant,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;

        if (! in_array($domainEvent->event_type, self::TRIGGERS, true)) {
            return; // not an inventory-structure change — sold-affecting events are handled inline
        }

        $payload = $domainEvent->payload ?? [];
        $roomTypeId = $payload['room_type_id'] ?? null;
        $start = $payload['start_date'] ?? null;
        $end = $payload['end_date'] ?? null;

        if (! is_string($roomTypeId) || ! is_string($start) || ! is_string($end)) {
            return; // insufficient context to recompute
        }

        // Recompute must run in the event's tenant context so the scoped queries resolve.
        $this->tenant->forOrganization(
            (string) $domainEvent->organization_id,
            fn () => $this->availability->recompute($roomTypeId, $start, $end),
        );
    }
}
