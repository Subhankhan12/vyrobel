<?php

declare(strict_types=1);

namespace App\Messaging\Channels;

/** DEFERRED skeleton — WhatsApp as a messaging channel. Throws until configured. */
class WhatsAppChannel extends DeferredChannel
{
    public function name(): string
    {
        return 'whatsapp';
    }

    protected function requirement(): string
    {
        return 'WhatsApp Business API approval plus a BSP (e.g. Twilio/Meta) number';
    }
}
