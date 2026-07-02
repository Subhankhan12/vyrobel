<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

/**
 * Level B rejection: the specific unit is already occupied by an active reservation_unit over
 * an overlapping [check_in, check_out), or an inventory_block overlaps it.
 */
class UnitUnavailableException extends BookingException
{
    public static function occupied(string $unitId): self
    {
        return new self("Unit [{$unitId}] is already occupied over the requested dates.");
    }

    public static function blocked(string $unitId): self
    {
        return new self("Unit [{$unitId}] is blocked (inventory_block) over the requested dates.");
    }
}
