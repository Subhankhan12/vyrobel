<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

use App\Messaging\Contracts\MessageChannel;
use App\Messaging\InboundMessage;
use App\Messaging\MessageDeliveryResult;
use App\Models\Message;
use App\Payouts\Rails\StripeConnectPayoutRail;
use RuntimeException;

/**
 * Base for the DEFERRED, account-gated external channels (email/WhatsApp/OTA) — the honest skeleton leg
 * of the interface + fake + real pattern, exactly like {@see StripeConnectPayoutRail}.
 * Both `send()` and `normalizeInbound()` THROW so the channel can never silently pretend to work; the
 * message names precisely what account/credential unblocks it. Config-gated (`services.inbox.channels.*`),
 * never the active channel in tests.
 */
abstract class DeferredChannel implements MessageChannel
{
    /** What real account/credential this channel needs before it can be implemented. */
    abstract protected function requirement(): string;

    public function send(Message $message, ?string $to): MessageDeliveryResult
    {
        throw new RuntimeException($this->notConfigured());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function normalizeInbound(array $payload): InboundMessage
    {
        throw new RuntimeException($this->notConfigured());
    }

    private function notConfigured(): string
    {
        return "The {$this->name()} channel is not configured — deferred skeleton. It needs {$this->requirement()}.";
    }
}
