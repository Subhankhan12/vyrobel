<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Models\HousekeepingTask;
use App\Support\Tenancy\TenantContext;

/**
 * On `reservation.checked_out`, auto-generate a departure-clean task per checked-out unit.
 *
 * A strict one-directional CONSUMER (same discipline as RecomputeAvailabilityOnInventoryChange):
 * it reacts to an already-committed, post-commit domain event and creates housekeeping_tasks —
 * it does NOT touch availability.sold, does NOT call AssignmentService, does NOT mutate the
 * reservation, and does NOT emit domain events (nothing here can re-trigger inventory recompute).
 * Runs synchronously — no queue. Idempotent per (unit, date, departure).
 */
class GenerateDepartureCleanTask
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;

        if ($domainEvent->event_type !== 'reservation.checked_out') {
            return;
        }

        $payload = $domainEvent->payload ?? [];
        $propertyId = $payload['property_id'] ?? null;
        $unitIds = $payload['unit_ids'] ?? [];
        $date = is_string($payload['date'] ?? null) ? $payload['date'] : now()->toDateString();

        if (! is_string($propertyId) || ! is_array($unitIds) || $unitIds === []) {
            return;
        }

        $this->tenant->forOrganization((string) $domainEvent->organization_id, function () use ($propertyId, $unitIds, $date): void {
            foreach ($unitIds as $unitId) {
                if (! is_string($unitId)) {
                    continue;
                }

                HousekeepingTask::firstOrCreate(
                    ['unit_id' => $unitId, 'date' => $date, 'type' => 'departure'],
                    ['property_id' => $propertyId, 'status' => 'pending', 'priority' => 'normal'],
                );
            }
        });
    }
}
