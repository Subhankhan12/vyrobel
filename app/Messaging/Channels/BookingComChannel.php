<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

/** DEFERRED skeleton — Booking.com guest messaging. Throws until configured. */
class BookingComChannel extends DeferredChannel
{
    public function name(): string
    {
        return 'booking_com';
    }

    protected function requirement(): string
    {
        return 'OTA messaging API access (via Channex messaging or the Booking.com partner API)';
    }
}
