<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Models\HousekeepingTask;
use App\Models\Unit;
use App\Services\HousekeepingService;
use App\Support\Tenancy\TenantContext;

/**
 * On `reservation.checked_out`, auto-generate a departure-clean task per checked-out unit AND flip each
 * unit to `dirty` (it needs cleaning before the next guest) — so the housekeeping board + the dashboard
 * "to clean" KPI reflect reality, and a later dirty→clean cycle is a real transition (not a clean→clean
 * no-op, the loop the browser pass flagged).
 *
 * A strict one-directional CONSUMER (same discipline as RecomputeAvailabilityOnInventoryChange): it
 * reacts to an already-committed, post-commit domain event and writes only within the HOUSEKEEPING domain
 * — it does NOT touch availability.sold, does NOT call AssignmentService, does NOT mutate the reservation.
 * The unit-dirty transition goes through `HousekeepingService` (not inline), which emits
 * `unit.housekeeping_changed` — a housekeeping-only event that NO inventory consumer reacts to, so it can
 * never re-trigger availability recompute. Runs synchronously — no queue. Idempotent per (unit, date,
 * departure); the unit is only dirtied when it isn't already dirty.
 */
class GenerateDepartureCleanTask
{
    public function __construct(
        private TenantContext $tenant,
        private HousekeepingService $housekeeping,
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

                // Flip the unit dirty via the real service path — idempotent (skip if already dirty).
                $unit = Unit::query()->find($unitId);
                if ($unit !== null && (string) $unit->housekeeping_status !== 'dirty') {
                    $this->housekeeping->markUnitHousekeepingStatus($unit, 'dirty');
                }
            }
        });
    }
}
