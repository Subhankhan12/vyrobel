<?php

declare(strict_types=1);

namespace App\Services;

use App\Listeners\EnqueueAriPushOnInventoryChange;
use App\Models\Channel;
use App\Models\ChannelMapping;
use App\Services\Channex\AriSnapshotBuilder;
use App\Services\Channex\ChannelManagerClient;
use App\Services\Channex\ChannexClient;
use App\Support\Events\AuditEventRecorder;
use App\Support\Events\DomainEventRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The single WRITER for a channel's connection lifecycle — the piece that was MISSING, which left the certified
 * channel manager INERT in production. {@see EnqueueAriPushOnInventoryChange} enqueues an outbound
 * ARI push only to channels with `status = 'connected'`, and {@see ChannelMappingGuard} /
 * {@see AriSnapshotBuilder} only include a mapping with `status = 'active'` — yet nothing in
 * `app/` ever wrote either value (only the test factories did), so for a real operator the push could never fire.
 * `connect()` + `activateMapping()` are those writers.
 *
 * ⚠️ WHAT IS REAL vs DEFERRED (the honesty rule). The LOCAL activation is real and complete: `connect()` flips a
 * channel to `connected` and thereby ARMS the outbound ARI pipeline (Step 3) for it; `activateMapping()` flips a
 * mapping to `active` so a push has a room_type/rate_plan target. What stays ACCOUNT-GATED is the live provider
 * handshake — verifying credentials against a real Channex account, and the outbound bytes themselves, go through
 * the {@see ChannelManagerClient} seam whose real implementation
 * ({@see ChannexClient}) is config-gated and throws until Channex credentials exist. So:
 * connecting is a real state transition an operator controls; the sync to Channex waits on the account — surfaced
 * honestly as "Soon" wherever the (Phase 1) Channels UI presents it.
 *
 * Every transition records an audit event (who) + a domain event (business outbox) INSIDE the service, per the
 * event-discipline invariant — no controller calls a recorder. These `channel.*` / `channel_mapping.*` events are
 * deliberately NOT in the ARI trigger set, so connecting a channel never itself triggers a push (no loop).
 */
class ChannelConnectionService
{
    public function __construct(
        private DomainEventRecorder $domainEvents,
        private AuditEventRecorder $audit,
    ) {}

    /**
     * Mark a channel CONNECTED — the exact status the outbound ARI push gates on. Clears any prior error.
     * Idempotent: connecting an already-connected, error-free channel is a no-op (no duplicate event).
     */
    public function connect(Channel $channel): Channel
    {
        return $this->transitionChannel($channel, 'connected', 'channel.connected');
    }

    /** Mark a channel DISCONNECTED — the outbound ARI push stops targeting it. */
    public function disconnect(Channel $channel): Channel
    {
        return $this->transitionChannel($channel, 'disconnected', 'channel.disconnected');
    }

    /**
     * Mark a channel in ERROR with a human-readable reason (e.g. a failed live handshake once the provider
     * integration is wired). Only `connected` channels are pushed to, so an errored channel is skipped.
     */
    public function markError(Channel $channel, string $error): Channel
    {
        return DB::transaction(function () use ($channel, $error): Channel {
            $from = (string) $channel->status;
            $channel->forceFill(['status' => 'error', 'last_error' => $error])->save();

            $this->domainEvents->record('channel.error', $channel, ['provider' => (string) $channel->provider]);
            $this->audit->record('channel.error', $channel, ['status' => $from], ['status' => 'error', 'last_error' => $error]);

            return $channel;
        });
    }

    /**
     * Activate a channel mapping — the status {@see ChannelMappingGuard} + {@see AriSnapshotBuilder}
     * require to include it in a push. Stamps `last_verified_at` and clears any prior error. Idempotent.
     */
    public function activateMapping(ChannelMapping $mapping): ChannelMapping
    {
        if ($mapping->status === ChannelMapping::STATUS_ACTIVE && $mapping->last_error === null) {
            return $mapping;
        }

        return DB::transaction(function () use ($mapping): ChannelMapping {
            $from = (string) $mapping->status;
            $mapping->forceFill([
                'status' => ChannelMapping::STATUS_ACTIVE,
                'last_error' => null,
                'last_verified_at' => CarbonImmutable::now(),
            ])->save();

            $this->domainEvents->record('channel_mapping.activated', $mapping, ['channel_id' => (string) $mapping->channel_id]);
            $this->audit->record('channel_mapping.activated', $mapping, ['status' => $from], ['status' => ChannelMapping::STATUS_ACTIVE]);

            return $mapping;
        });
    }

    private function transitionChannel(Channel $channel, string $to, string $eventType): Channel
    {
        if ($channel->status === $to && $channel->last_error === null) {
            return $channel;
        }

        return DB::transaction(function () use ($channel, $to, $eventType): Channel {
            $from = (string) $channel->status;
            $channel->forceFill(['status' => $to, 'last_error' => null])->save();

            $this->domainEvents->record($eventType, $channel, ['provider' => (string) $channel->provider]);
            $this->audit->record($eventType, $channel, ['status' => $from], ['status' => $to]);

            return $channel;
        });
    }
}
