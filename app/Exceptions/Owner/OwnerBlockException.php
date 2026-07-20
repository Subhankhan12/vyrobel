<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use App\Exceptions\Booking\UnitUnavailableException;
use RuntimeException;

/**
 * The owner-scope boundary on the Block Calendar write path (Phase 2 · Sub-gate 2): an owner tried to block or
 * release inventory on a unit that is NOT one of their own. Distinct from
 * {@see UnitUnavailableException} (an ordinary occupied/blocked date) so the controller
 * can tell a TAMPERING attempt (→ 403) apart from a normal date conflict (→ a friendly "pick open dates").
 */
class OwnerBlockException extends RuntimeException
{
    public static function notYourUnit(string $unitId): self
    {
        return new self("Unit [{$unitId}] is not one of your units.");
    }
}
