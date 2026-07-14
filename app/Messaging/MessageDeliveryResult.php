<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Payouts\PayoutResult;

/**
 * Immutable outcome of a {@see Contracts\MessageChannel::send()} — mirrors {@see PayoutResult}.
 * `externalMessageId` is the provider's id for the sent message (used as the outbound record's external
 * ref); `status` is the resulting delivery state (`sent`|`delivered`|`failed`); `failureReason` is set
 * when the channel could not deliver.
 */
final class MessageDeliveryResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly string $status = 'sent',
        public readonly ?string $externalMessageId = null,
        public readonly ?string $failureReason = null,
    ) {}
}
