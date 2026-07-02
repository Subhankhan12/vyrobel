<?php

declare(strict_types=1);

namespace App\Services\Channex;

/**
 * The CURRENT FULL STATE of one room_type over one date range, for one channel — what actually
 * gets pushed. Never a delta: Channex (and every OTA behind it) is last-write-wins, so every
 * push must carry the complete current picture for the affected room_type + range, not just
 * what changed. Built fresh from committed data by {@see AriSnapshotBuilder} at process() time —
 * never from data captured when the triggering event was enqueued.
 */
final class AriSnapshot
{
    /**
     * @param  list<AriDateSnapshot>  $dates
     */
    public function __construct(
        public readonly string $channelId,
        public readonly string $externalPropertyId,
        public readonly string $roomTypeId,
        public readonly string $externalRoomTypeId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array $dates,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'channel_id' => $this->channelId,
            'external_property_id' => $this->externalPropertyId,
            'room_type_id' => $this->roomTypeId,
            'external_room_type_id' => $this->externalRoomTypeId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'dates' => array_map(fn (AriDateSnapshot $d): array => $d->toArray(), $this->dates),
        ];
    }
}
