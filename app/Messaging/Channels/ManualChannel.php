<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

use App\Messaging\Contracts\MessageChannel;
use App\Messaging\InboundMessage;
use App\Messaging\MessageDeliveryResult;
use App\Models\Message;
use Illuminate\Support\Str;

/**
 * The MANUAL/internal channel — the only fully-working channel now (the "real" leg of the interface +
 * fake + real pattern, analogous to ManualPaymentGateway / ManualPayoutRail). An outbound message is
 * recorded and immediately considered delivered (no external provider); inbound is INJECTED (an
 * operator logs a call/walk-in note, or a test/demo posts a message) and normalised straight through.
 * No network, no credentials — genuinely records + routes, never faked.
 */
class ManualChannel implements MessageChannel
{
    public function name(): string
    {
        return 'manual';
    }

    public function send(Message $message, ?string $to): MessageDeliveryResult
    {
        // Internal channel: recording IS the delivery. A generated id gives the outbound a stable
        // external ref (parity with a provider's message id) without any external call.
        return new MessageDeliveryResult(
            successful: true,
            status: 'delivered',
            externalMessageId: 'manual:'.Str::ulid()->toBase32(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function normalizeInbound(array $payload): InboundMessage
    {
        return new InboundMessage(
            channel: 'manual',
            body: (string) ($payload['body'] ?? ''),
            externalThreadRef: isset($payload['external_thread_ref']) ? (string) $payload['external_thread_ref'] : null,
            externalMessageId: isset($payload['external_message_id']) ? (string) $payload['external_message_id'] : null,
            participantIdentity: isset($payload['participant_identity']) ? (string) $payload['participant_identity'] : null,
            senderLabel: isset($payload['sender_label']) ? (string) $payload['sender_label'] : null,
            subject: isset($payload['subject']) ? (string) $payload['subject'] : null,
        );
    }
}
