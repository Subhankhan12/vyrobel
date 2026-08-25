<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use RuntimeException;

/**
 * ⚠️ A booking refused by a restriction the operator set.
 *
 * ⚠️ THE MESSAGE NAMES THE RESTRICTION, THE DATE AND THE VALUE. "Not available" is what makes an operator
 * distrust the system and stop setting restrictions altogether — and a restriction nobody sets is worth less
 * than one that is not enforced.
 */
class RestrictionViolationException extends RuntimeException
{
    public function __construct(
        public readonly string $restriction,
        public readonly string $date,
        public readonly ?int $value,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function minStay(string $date, int $required, int $actual): self
    {
        return new self('min_stay', $date, $required, sprintf(
            'A minimum stay of %d night(s) applies to arrivals on %s — this booking is %d night(s).',
            $required, $date, $actual
        ));
    }

    public static function maxStay(string $date, int $allowed, int $actual): self
    {
        return new self('max_stay', $date, $allowed, sprintf(
            'A maximum stay of %d night(s) applies to arrivals on %s — this booking is %d night(s).',
            $allowed, $date, $actual
        ));
    }

    public static function stopSell(string $date): self
    {
        return new self('stop_sell', $date, null, sprintf('%s is stopped for sale.', $date));
    }

    public static function closedToArrival(string $date): self
    {
        return new self('closed_to_arrival', $date, null, sprintf(
            '%s is closed to arrival — a stay cannot start that day, though one may run through it.', $date
        ));
    }

    public static function closedToDeparture(string $date): self
    {
        return new self('closed_to_departure', $date, null, sprintf(
            '%s is closed to departure — a stay cannot end that day, though one may run through it.', $date
        ));
    }
}
