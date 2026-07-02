<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DomainEventRecorded;
use App\Jobs\Sync\AriPushJob;
use App\Models\Channel;
use App\Models\DomainEvent;
use App\Models\Reservation;
use App\Models\ReservationUnit;
use App\Models\RoomType;
use App\Services\SyncJobDispatcher;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Enqueues an outbound ARI push (Step 3) whenever a domain event changes the sellable picture
 * for a room_type + date range: booking/cancel/no-show/extend (via AssignmentService),
 * inventory_block changes, and rate/restriction edits (via RateAndRestrictionService).
 *
 * A PURE consumer, same one-directional discipline as
 * {@see RecomputeAvailabilityOnInventoryChange}: it only ENQUEUES a job (via the Step 1
 * backbone) — it never mutates availability, never touches `sold`, and never emits domain
 * events itself (which would risk a push -> event -> push loop). Runs synchronously (no queue)
 * as a listener; the actual Channex call happens later, in the queued {@see AriPushJob}.
 *
 * Registered AFTER RecomputeAvailabilityOnInventoryChange for the same event (AppServiceProvider)
 * so inventory-structure changes (blocked/out_of_order) are already recomputed before this reads
 * `availability` — the push must reflect post-recompute state.
 */
class EnqueueAriPushOnInventoryChange
{
    /**
     * Event types whose payload already carries {room_type_id, start_date, end_date} — the same
     * shape RecomputeAvailabilityOnInventoryChange reads.
     */
    private const RANGE_PAYLOAD_TRIGGERS = [
        'inventory_block.created',
        'inventory_block.released',
        'unit.condition_changed',
        'unit.activated',
        'unit.deactivated',
        'rate_calendar.updated',
        'restriction.updated',
    ];

    /** Reservation-lifecycle events — affected room_type(s)+range derived from the subject. */
    private const RESERVATION_TRIGGERS = [
        'reservation.confirmed',
        'reservation.cancelled',
        'reservation.no_show',
        'reservation.imported',  // Step 4 OTA import — closes the loop: an OTA booking pushes ARI
        'reservation.modified',  // Step 4 OTA modification (re-synced lines)
    ];

    /** Single-line events — affected room_type+range derived from the reservation_unit itself. */
    private const RESERVATION_UNIT_TRIGGERS = [
        'reservation_unit.extended',
        'reservation_unit.cancelled',
    ];

    public function __construct(
        private SyncJobDispatcher $dispatcher,
        private TenantContext $tenant,
    ) {}

    public function handle(DomainEventRecorded $event): void
    {
        $domainEvent = $event->domainEvent;
        $eventType = $domainEvent->event_type;

        $allTriggers = [...self::RANGE_PAYLOAD_TRIGGERS, ...self::RESERVATION_TRIGGERS, ...self::RESERVATION_UNIT_TRIGGERS];
        if (! in_array($eventType, $allTriggers, true)) {
            return;
        }

        $this->tenant->forOrganization((string) $domainEvent->organization_id, function () use ($domainEvent, $eventType): void {
            // The early return above already restricts $eventType to the union of all three
            // trigger sets, so ruling out the first two guarantees the third — no need to
            // re-check it explicitly (and PHPStan can prove that).
            $ranges = match (true) {
                in_array($eventType, self::RANGE_PAYLOAD_TRIGGERS, true) => $this->rangesFromPayload($domainEvent->payload ?? []),
                in_array($eventType, self::RESERVATION_TRIGGERS, true) => $this->rangesFromReservation($domainEvent),
                default => $this->rangesFromReservationUnit($domainEvent),
            };

            foreach ($ranges as $range) {
                $this->enqueueForRoomType($range['room_type_id'], $range['start_date'], $range['end_date'], (string) $domainEvent->id);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array{room_type_id: string, start_date: string, end_date: string}>
     */
    private function rangesFromPayload(array $payload): array
    {
        $roomTypeId = $payload['room_type_id'] ?? null;
        $start = $payload['start_date'] ?? null;
        $end = $payload['end_date'] ?? null;

        if (! is_string($roomTypeId) || ! is_string($start) || ! is_string($end)) {
            return [];
        }

        return [['room_type_id' => $roomTypeId, 'start_date' => $start, 'end_date' => $end]];
    }

    /**
     * A reservation may span multiple room_types (multi-room booking) — one range per distinct
     * room_type. We use ALL of the reservation's lines regardless of status: the push only needs
     * the affected room_type + date span, and each AriPushJob reads CURRENT availability at
     * execution time anyway. Using every line correctly covers confirm (active), cancel
     * (now-cancelled lines still tell us the freed dates), import (active + overbooked), and
     * modify (old cancelled dates ∪ new dates) — one robust path, no per-event status guessing.
     *
     * @return list<array{room_type_id: string, start_date: string, end_date: string}>
     */
    private function rangesFromReservation(DomainEvent $domainEvent): array
    {
        $reservation = Reservation::query()->find($domainEvent->subject_id);
        if ($reservation === null) {
            return [];
        }

        return $this->mergeByRoomType($reservation->reservationUnits()->get());
    }

    /**
     * @return list<array{room_type_id: string, start_date: string, end_date: string}>
     */
    private function rangesFromReservationUnit(DomainEvent $domainEvent): array
    {
        $reservationUnit = ReservationUnit::query()->find($domainEvent->subject_id);
        if ($reservationUnit === null) {
            return [];
        }

        return $this->mergeByRoomType(collect([$reservationUnit]));
    }

    /**
     * @param  Collection<int, ReservationUnit>  $lines
     * @return list<array{room_type_id: string, start_date: string, end_date: string}>
     */
    private function mergeByRoomType(Collection $lines): array
    {
        return $lines
            ->groupBy('room_type_id')
            ->map(fn (Collection $group, string $roomTypeId): array => [
                'room_type_id' => $roomTypeId,
                'start_date' => CarbonImmutable::parse($group->min('check_in'))->toDateString(),
                'end_date' => CarbonImmutable::parse($group->max('check_out'))->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * One push per channel connected to this room_type's property. Idempotency key is
     * (channel, room_type, triggering domain_event) — see docs/DECISIONS.md for why: it dedupes
     * a truly-redelivered event without collapsing two DIFFERENT events (e.g. book then cancel)
     * into one lost push, because each AriPushJob reads CURRENT state at process() time rather
     * than a snapshot captured at enqueue time — so re-ordering between two pushes for the same
     * room_type+range is harmless (the later read always reflects the true current state).
     */
    private function enqueueForRoomType(string $roomTypeId, string $startDate, string $endDate, string $domainEventId): void
    {
        $roomType = RoomType::query()->find($roomTypeId);
        if ($roomType === null) {
            return;
        }

        // Only channels the property has actually connected — pushing to a disconnected/
        // disabled channel would just be a guaranteed, wasted retry-to-dead-letter cycle.
        $channels = Channel::query()
            ->where('property_id', $roomType->property_id)
            ->where('status', 'connected')
            ->get();

        foreach ($channels as $channel) {
            $this->dispatcher->dispatch(
                type: 'ari_push',
                direction: 'outbound',
                idempotencyKey: "ari_push:{$channel->id}:{$roomTypeId}:{$domainEventId}",
                jobClass: AriPushJob::class,
                payloadSummary: [
                    'channel_id' => $channel->id,
                    'room_type_id' => $roomTypeId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                channelId: $channel->id,
            );
        }
    }
}
