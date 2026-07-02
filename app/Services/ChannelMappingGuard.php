<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\UnmappedChannelEntityException;
use App\Models\Channel;
use App\Models\ChannelMapping;
use Illuminate\Support\Facades\Log;

/**
 * The mapping guard (Step 2). No outbound ARI push (Step 3) or inbound booking import (Step 4)
 * may proceed for a room_type / rate_plan that has no ACTIVE `channel_mapping` on the channel.
 *
 * "Never a silent no-op": the resolvers throw {@see UnmappedChannelEntityException} AND write a
 * warning to the application log first, so even a caller that swallows the exception still leaves
 * a clear, actionable trace. Callers with a live sync context (Steps 3-4) additionally record the
 * failure to `sync_logs`; this guard guarantees the loud failure independent of that.
 *
 * Only `status = active` counts as mapped — a pending/error/disabled mapping is treated as
 * unmapped so half-configured channels can't quietly leak inventory or bookings.
 */
class ChannelMappingGuard
{
    /**
     * Resolve the active room_type mapping for a channel, or fail loudly.
     */
    public function resolveRoomTypeMapping(Channel $channel, string $roomTypeId): ChannelMapping
    {
        return $this->resolve($channel, ChannelMapping::KIND_ROOM_TYPE, 'room_type_id', $roomTypeId);
    }

    /**
     * Resolve the active rate_plan mapping for a channel, or fail loudly.
     */
    public function resolveRatePlanMapping(Channel $channel, string $ratePlanId): ChannelMapping
    {
        return $this->resolve($channel, ChannelMapping::KIND_RATE_PLAN, 'rate_plan_id', $ratePlanId);
    }

    public function hasActiveRoomTypeMapping(Channel $channel, string $roomTypeId): bool
    {
        return $this->activeMapping($channel, ChannelMapping::KIND_ROOM_TYPE, 'room_type_id', $roomTypeId) !== null;
    }

    public function hasActiveRatePlanMapping(Channel $channel, string $ratePlanId): bool
    {
        return $this->activeMapping($channel, ChannelMapping::KIND_RATE_PLAN, 'rate_plan_id', $ratePlanId) !== null;
    }

    /**
     * INBOUND direction (Step 4 booking import): resolve an ACTIVE mapping by the provider's
     * external room_type id, or fail loudly — an OTA booking for an unmapped external room type
     * must not be silently dropped.
     */
    public function resolveInboundRoomTypeMapping(Channel $channel, string $externalRoomTypeId): ChannelMapping
    {
        return $this->resolveInbound($channel, ChannelMapping::KIND_ROOM_TYPE, $externalRoomTypeId);
    }

    /**
     * INBOUND rate_plan resolution: required-if-present. A payload that names an external rate
     * plan we don't have mapped is a clear error (fail loudly); a payload that omits it returns
     * null (the reservation line simply carries no rate plan).
     */
    public function resolveInboundRatePlanMapping(Channel $channel, ?string $externalRatePlanId): ?ChannelMapping
    {
        if ($externalRatePlanId === null || $externalRatePlanId === '') {
            return null;
        }

        return $this->resolveInbound($channel, ChannelMapping::KIND_RATE_PLAN, $externalRatePlanId);
    }

    private function resolveInbound(Channel $channel, string $kind, string $externalId): ChannelMapping
    {
        $mapping = ChannelMapping::query()
            ->where('channel_id', $channel->getKey())
            ->where('kind', $kind)
            ->where('external_id', $externalId)
            ->first();

        if ($mapping === null) {
            $this->blockedInbound($channel, $kind, $externalId, 'no internal entity mapped to this external id');
        }

        if (! $mapping->isActive()) {
            $this->blockedInbound($channel, $kind, $externalId, "mapping status is '{$mapping->status}', not active");
        }

        return $mapping;
    }

    private function resolve(Channel $channel, string $kind, string $column, string $entityId): ChannelMapping
    {
        $mapping = $this->anyMapping($channel, $kind, $column, $entityId);

        if ($mapping === null) {
            $this->blocked($channel, $kind, $entityId, 'no mapping exists');
        }

        if (! $mapping->isActive()) {
            $this->blocked($channel, $kind, $entityId, "mapping status is '{$mapping->status}', not active");
        }

        return $mapping;
    }

    private function activeMapping(Channel $channel, string $kind, string $column, string $entityId): ?ChannelMapping
    {
        return ChannelMapping::query()
            ->where('channel_id', $channel->getKey())
            ->where('kind', $kind)
            ->where($column, $entityId)
            ->where('status', ChannelMapping::STATUS_ACTIVE)
            ->first();
    }

    private function anyMapping(Channel $channel, string $kind, string $column, string $entityId): ?ChannelMapping
    {
        return ChannelMapping::query()
            ->where('channel_id', $channel->getKey())
            ->where('kind', $kind)
            ->where($column, $entityId)
            ->first();
    }

    private function blocked(Channel $channel, string $kind, string $entityId, string $reason): never
    {
        $exception = UnmappedChannelEntityException::for($channel, $kind, $entityId, $reason);

        Log::warning('channel.mapping_guard.blocked', [
            'channel_id' => (string) $channel->getKey(),
            'organization_id' => $channel->organization_id,
            'kind' => $kind,
            'entity_id' => $entityId,
            'reason' => $reason,
        ]);

        throw $exception;
    }

    private function blockedInbound(Channel $channel, string $kind, string $externalId, string $reason): never
    {
        $exception = UnmappedChannelEntityException::forInbound($channel, $kind, $externalId, $reason);

        Log::warning('channel.mapping_guard.blocked_inbound', [
            'channel_id' => (string) $channel->getKey(),
            'organization_id' => $channel->organization_id,
            'kind' => $kind,
            'external_id' => $externalId,
            'reason' => $reason,
        ]);

        throw $exception;
    }
}
