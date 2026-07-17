<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

use App\Messaging\Contracts\MessageChannel;
use App\Messaging\InboundMessage;
use App\Messaging\MessageDeliveryResult;
use App\Models\Message;
use Illuminate\Support\Str;

/**
 * The MANUAL/INTERNAL channel — the only fully-working channel now (the "real" leg of the interface +
 * fake + real pattern, analogous to ManualPaymentGateway / ManualPayoutRail). **Recording IS the
 * delivery**: there is no external provider to hand off to, so an outbound message is delivered the moment
 * it is written. No network, no credentials — genuinely records + routes, never faked.
 *
 * IT CARRIES TWO KINDS OF TRAFFIC, and "recording is the delivery" is the honest description of both
 * (widened 2026-07-17, Wave 3 — the behaviour did not change, only this wording, which was written when
 * the inbox had one use):
 *   • An operator LOGGING an external conversation — a phone call or walk-in note. Delivery already
 *     happened, out of band; the record is the point.
 *   • An IN-APP owner↔operator message. Both parties read it inside Vyrobel, so writing the row IS the
 *     delivery — there is nothing to send and no address to route to.
 *
 * A distinct `in_app` channel + adapter was considered and rejected: it would be byte-for-byte identical to
 * this class, and CLAUDE.md's "reuse, don't duplicate" rule points here. The enum value names the MEDIUM
 * (internal, vs email/whatsapp/OTA), not the participant — `guest_id` XOR `owner_id` says who.
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
