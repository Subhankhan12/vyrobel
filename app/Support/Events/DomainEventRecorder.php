<?php

declare(strict_types=1);

namespace App\Support\Events;

use App\Events\DomainEventRecorded;
use App\Models\DomainEvent;
use App\Support\Tenancy\TenantContext;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * The single reusable writer for the business event outbox (convention #7). Records a
 * semantic event (e.g. `reservation.confirmed`, `unit.checked_out`) that later drives the
 * availability recompute, automations, webhooks, and AI agents.
 *
 * Consumers are wired in later steps/phases; this just guarantees the events exist.
 */
class DomainEventRecorder
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public function record(
        string $eventType,
        ?Model $subject = null,
        array $payload = [],
        ?string $organizationId = null,
        ?DateTimeInterface $availableAt = null,
        ?DateTimeInterface $occurredAt = null,
    ): DomainEvent {
        $domainEvent = DomainEvent::create([
            'organization_id' => $organizationId ?? $this->tenant->id(),
            'event_type' => $eventType,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject !== null ? (string) $subject->getKey() : null,
            'payload' => $payload,
            'occurred_at' => $occurredAt ?? now(),
            'available_at' => $availableAt,
        ]);

        // Synchronous in-process fan-out (no queue): drives the availability-recompute listener
        // for inventory-structure events. Reservation events are ignored by that listener.
        DomainEventRecorded::dispatch($domainEvent);

        return $domainEvent;
    }
}
