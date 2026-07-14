<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Services\InboxService;

/**
 * A normalised inbound message — a {@see Contracts\MessageChannel::normalizeInbound()} turns a raw
 * provider payload into this channel-agnostic shape, which {@see InboxService::postInbound()}
 * then persists (idempotently). `externalThreadRef` groups the message into the right thread;
 * `externalMessageId` is the exactly-once idempotency anchor; `participantIdentity` is the sender's
 * channel address (stored ENCRYPTED on the thread as the routing anchor, never rendered).
 */
final class InboundMessage
{
    public function __construct(
        public readonly string $channel,
        public readonly string $body,
        public readonly ?string $externalThreadRef = null,
        public readonly ?string $externalMessageId = null,
        public readonly ?string $participantIdentity = null,
        public readonly ?string $senderLabel = null,
        public readonly ?string $subject = null,
    ) {}
}
