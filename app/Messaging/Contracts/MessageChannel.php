<?php

declare(strict_types=1);

namespace App\Messaging\Contracts;

use App\Messaging\Channels\ManualChannel;
use App\Messaging\InboundMessage;
use App\Messaging\MessageChannelResolver;
use App\Messaging\MessageDeliveryResult;
use App\Models\Message;
use App\Payouts\Contracts\PayoutRail;
use App\Services\Channex\ChannelManagerClient;

/**
 * A guest-messaging channel (unified inbox) — mirrors the {@see PayoutRail}
 * and {@see ChannelManagerClient} seams: an interface with a REAL implementation
 * ({@see ManualChannel}) plus config-gated, deferred SKELETONS for the real
 * external providers (email/WhatsApp/OTA) that THROW "not configured" until implemented + credentialed.
 * The active adapter for a thread's `channel` is picked by {@see MessageChannelResolver}.
 */
interface MessageChannel
{
    /** Machine name of the channel — matches the thread/message `channel` enum. */
    public function name(): string;

    /**
     * Send an outbound message over the channel. `$to` is the routing address (the decrypted
     * participant identity), supplied by the caller so the adapter never reads guest PII itself.
     * Returns the delivery outcome (external id + status), or THROWS if the channel is not configured.
     */
    public function send(Message $message, ?string $to): MessageDeliveryResult;

    /**
     * Normalise a raw inbound provider payload into a channel-agnostic DTO the InboxService can post.
     * Deferred adapters THROW (no inbound wiring until the provider is configured).
     *
     * @param  array<string, mixed>  $payload
     */
    public function normalizeInbound(array $payload): InboundMessage;
}
