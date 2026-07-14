<?php

declare(strict_types=1);

namespace App\Messaging;

use App\Messaging\Channels\AirbnbChannel;
use App\Messaging\Channels\BookingComChannel;
use App\Messaging\Channels\EmailChannel;
use App\Messaging\Channels\ManualChannel;
use App\Messaging\Channels\WhatsAppChannel;
use App\Messaging\Contracts\MessageChannel;
use InvalidArgumentException;

/**
 * Picks the {@see MessageChannel} adapter for a thread's `channel` enum — the inbox analogue of the
 * config-gated gateway/rail selection in AppServiceProvider. `manual` resolves to the real
 * {@see ManualChannel}; the external channels resolve to their DEFERRED skeletons, which throw
 * "not configured" on use (never faked as working). Resolving via the container keeps a test's swap
 * of an adapter effective.
 */
class MessageChannelResolver
{
    /** @var array<string, class-string<MessageChannel>> */
    private const MAP = [
        'manual' => ManualChannel::class,
        'email' => EmailChannel::class,
        'whatsapp' => WhatsAppChannel::class,
        'booking_com' => BookingComChannel::class,
        'airbnb' => AirbnbChannel::class,
    ];

    public function resolve(string $channel): MessageChannel
    {
        $class = self::MAP[$channel] ?? throw new InvalidArgumentException("Unknown inbox channel [{$channel}].");

        return app($class);
    }

    /** @return list<string> */
    public function channels(): array
    {
        return array_keys(self::MAP);
    }
}
