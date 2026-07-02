<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Channel;
use App\Services\ChannelMappingGuard;
use RuntimeException;

/**
 * Thrown by {@see ChannelMappingGuard} when an ARI push or booking import is
 * attempted for a room_type / rate_plan that has no ACTIVE channel_mapping on the channel.
 *
 * The whole point of the mapping guard: an unmapped entity must fail loudly with an actionable
 * message, never be silently skipped (which would drop inventory pushes or orphan OTA bookings
 * without anyone noticing).
 */
class UnmappedChannelEntityException extends RuntimeException
{
    public function __construct(
        public readonly string $channelId,
        public readonly string $kind,
        public readonly string $entityId,
        public readonly ?string $reason = null,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public static function for(Channel $channel, string $kind, string $entityId, ?string $reason = null): self
    {
        $detail = $reason !== null ? " ({$reason})" : '';

        return new self(
            channelId: (string) $channel->getKey(),
            kind: $kind,
            entityId: $entityId,
            reason: $reason,
            message: "No active channel_mapping for {$kind} [{$entityId}] on channel "
                ."[{$channel->name} / {$channel->getKey()}]{$detail}. "
                ."Create/activate a channel_mapping (kind={$kind}) before syncing this entity — "
                .'the push/import was blocked to avoid silently dropping inventory or bookings.',
        );
    }

    /**
     * INBOUND variant: an OTA booking referenced an EXTERNAL id we have no active mapping for.
     * `entityId` holds the external id (the thing we couldn't resolve to an internal entity).
     */
    public static function forInbound(Channel $channel, string $kind, string $externalId, ?string $reason = null): self
    {
        $detail = $reason !== null ? " ({$reason})" : '';

        return new self(
            channelId: (string) $channel->getKey(),
            kind: $kind,
            entityId: $externalId,
            reason: $reason,
            message: "No active channel_mapping for inbound {$kind} external id [{$externalId}] on channel "
                ."[{$channel->name} / {$channel->getKey()}]{$detail}. "
                ."Map this external {$kind} before importing its bookings — the import was blocked "
                .'and the booking left unprocessed (never silently dropped) for manual resolution.',
        );
    }
}
