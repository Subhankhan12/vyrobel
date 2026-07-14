<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

/** DEFERRED skeleton — guest email as a messaging channel. Throws until configured. */
class EmailChannel extends DeferredChannel
{
    public function name(): string
    {
        return 'email';
    }

    protected function requirement(): string
    {
        return 'an inbound-email provider (e.g. Postmark/Mailgun inbound parse) plus a verified sending domain';
    }
}
