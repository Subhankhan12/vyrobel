<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use RuntimeException;

/**
 * VY-15 (audit of 2026-11-24) — the PUBLIC booking engine refuses a stay it could never have offered: dates that have passed at the
 * property, more guests than the room takes, or a rate plan the operator switched off.
 *
 * ⚠️ NOT a {@see BookingException}, deliberately: the engine reads a BookingException from the core as "someone took the last unit"
 * and answers `unavailable`. This is a different fact — the REQUEST is wrong, and no retry will make it right — so it carries the
 * field it is about and its own sentence, and each surface says it in its own way (a 422 on the API, a message on the checkout page).
 */
class StayNotBookableException extends RuntimeException
{
    public function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }
}
