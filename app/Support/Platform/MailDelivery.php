<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Controllers\Admin\SupportController;
use App\Support\Platform\Health\HealthBoard;

/**
 * ADMIN 01 · PHASE 3 — ONE definition of "is mail actually going anywhere?", read by the health board's mail card
 * ({@see HealthBoard}) and by the support tools ({@see SupportController}) that send a verification or a password
 * reset — so the board and the button can never disagree about whether an email reaches a person.
 *
 * ⚠️ `log` and `array` DISCARD. A "sent" from either is not a delivery, and the console must never present it as
 * one. (On production `log` is also written below the configured log level, so it is not even recorded.)
 */
final class MailDelivery
{
    public const DISCARDING = ['log', 'array'];

    public static function mailer(): string
    {
        return (string) config('mail.default');
    }

    public static function discards(): bool
    {
        return in_array(self::mailer(), self::DISCARDING, true);
    }
}
