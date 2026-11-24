<?php

declare(strict_types=1);

namespace App\Exceptions\Reservations;

use RuntimeException;

/**
 * VY-13 (audit of 2026-11-24, reproduced) — a guest cannot be checked in because the stay has no room, or its room is closed
 * (out of service / out of order). Thrown by the action that writes the status, so every caller is refused; the front desk turns it
 * into a page that names each problem.
 *
 * `$problems` is one finished sentence per room that is not ready (already in the reader's language) — the message is those
 * sentences joined, so a caller with no page of its own (the walk-in notice, a log line) still says exactly what is wrong.
 */
class RoomNotReadyException extends RuntimeException
{
    /** @param  list<string>  $problems */
    public function __construct(public readonly array $problems)
    {
        parent::__construct(implode(' ', $problems));
    }
}
