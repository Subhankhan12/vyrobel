<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

/**
 * Level A rejection: the requested nights would push sold + blocked + out_of_order above total
 * for the room_type (or the calendar was never published for a night).
 */
class InsufficientAvailabilityException extends BookingException
{
    public static function forNight(string $roomTypeId, string $night): self
    {
        return new self("Insufficient availability for room_type [{$roomTypeId}] on [{$night}].");
    }

    public static function calendarNotPublished(string $roomTypeId, string $night): self
    {
        return new self("No availability row published for room_type [{$roomTypeId}] on [{$night}]; recompute the calendar first.");
    }
}
