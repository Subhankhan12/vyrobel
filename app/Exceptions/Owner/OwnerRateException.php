<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use RuntimeException;

/**
 * The owner-scope boundary on the Owner Rates MONEY write (Phase 2 · Sub-gate 3): an owner tried to set a rate
 * on a room type they may NOT edit — one they don't own, or one they own only PARTLY (it also contains a unit
 * owned by the operator or another owner). Because rates are per-room-type, editing a shared room type would
 * move someone else's price, so the write is refused. The controller turns this into a 403.
 */
class OwnerRateException extends RuntimeException
{
    public static function notYourRoomType(string $roomTypeId): self
    {
        return new self("Room type [{$roomTypeId}] is not one you may set rates for.");
    }
}
