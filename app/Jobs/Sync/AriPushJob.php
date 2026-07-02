<?php

declare(strict_types=1);

namespace App\Jobs\Sync;

use App\Exceptions\UnmappedChannelEntityException;
use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Models\Channel;
use App\Models\RoomType;
use App\Models\SyncJob;
use App\Services\ChannelMappingGuard;
use App\Services\Channex\AriSnapshotBuilder;
use App\Services\Channex\ChannelManagerClient;

/**
 * Pushes the CURRENT FULL STATE of one room_type + date range to one channel (Step 3 —
 * outbound ARI sync). A PURE consumer of committed state: it only reads `availability`/
 * `rate_calendar`/`restrictions` (via {@see AriSnapshotBuilder}) and calls the channel-manager
 * client — it never writes `sold`, never mutates availability, and never emits domain events
 * (which would risk a push -> event -> push loop). All context (channel/room_type/range) comes
 * from `sync_jobs.payload_summary`, set at enqueue time by
 * {@see EnqueueAriPushOnInventoryChange}.
 *
 * An unmapped room_type is NOT a retryable failure — retrying against the same missing mapping
 * would just fail identically until dead-letter, which is the wrong signal for an operator
 * (the fix is "map it," not "wait and retry"). So this is caught and logged as a skip; the job
 * still completes (no push happened, and none should have).
 */
class AriPushJob extends RetryableSyncJob
{
    protected function process(SyncJob $syncJob): void
    {
        /** @var array{channel_id: string, room_type_id: string, start_date: string, end_date: string} $payload */
        $payload = $syncJob->payload_summary ?? [];

        $channel = Channel::query()->findOrFail($payload['channel_id']);
        $roomType = RoomType::query()->findOrFail($payload['room_type_id']);

        try {
            $mapping = app(ChannelMappingGuard::class)->resolveRoomTypeMapping($channel, $roomType->getKey());
        } catch (UnmappedChannelEntityException $e) {
            $this->note($syncJob, 'warning', "ARI push skipped — {$e->getMessage()}");

            return;
        }

        $snapshot = app(AriSnapshotBuilder::class)->build(
            $channel,
            $roomType,
            $mapping->external_id,
            $payload['start_date'],
            $payload['end_date'],
        );

        app(ChannelManagerClient::class)->pushAri($channel, $snapshot);
    }
}
