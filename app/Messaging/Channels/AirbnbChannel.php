<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

/** DEFERRED skeleton — Airbnb guest messaging. Throws until configured. */
class AirbnbChannel extends DeferredChannel
{
    public function name(): string
    {
        return 'airbnb';
    }

    protected function requirement(): string
    {
        return 'OTA messaging API access (via Channex messaging or the Airbnb partner API)';
    }
}
