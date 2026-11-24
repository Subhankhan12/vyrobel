<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use App\Support\Inventory\RoomCapacity;

/**
 * VY-03 (audit of 2026-11-24) — taking this unit away (a block, or out of order) would leave more bookings than rooms on some night.
 *
 * It carries the three figures the operator is shown BEFORE anything is saved: on how many nights, how many bookings at the worst, and
 * the first such night ({@see RoomCapacity::ifRemoved()}).
 *
 * ⚠️ IT IS A {@see UnitUnavailableException} ON PURPOSE. To the owner's Block Calendar the answer is the one it already gives — "those
 * dates include a guest booking … pick open dates" — and a repeat block skips the occurrence exactly as it skips a booked one. So every
 * existing caller of the block path handles this refusal without a new branch, and none can turn it into a crash.
 */
class InventoryOversoldException extends UnitUnavailableException
{
    public function __construct(
        string $message,
        public readonly int $nights,
        public readonly int $peak,
        public readonly ?string $firstNight,
    ) {
        parent::__construct($message);
    }

    /** @param  array{nights: int, peak: int, first_night: ?string}  $impact */
    public static function forImpact(string $what, array $impact): self
    {
        return new self(
            "{$what} would leave more bookings than rooms on {$impact['nights']} night(s) (worst: {$impact['peak']} too many, first: ".($impact['first_night'] ?? 'n/a').').',
            $impact['nights'],
            $impact['peak'],
            $impact['first_night'],
        );
    }
}
