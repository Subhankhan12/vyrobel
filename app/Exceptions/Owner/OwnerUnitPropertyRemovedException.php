<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

/**
 * The owner tried to MANAGE a unit whose property has been ARCHIVED (2026-09-01).
 *
 * ⚠️ WHY IT IS NOT {@see OwnerBlockException::notYourUnit()}. The unit genuinely IS theirs — it is still
 * attributed to them, its past stays and earnings are still theirs, and it still appears in their Bookings
 * and statements, correctly. What has gone is the PROPERTY. Reporting that as "not one of your units" would
 * be a false statement to the owner, and reporting it as tampering (403) would be a false statement about
 * them. So it is its own outcome with its own message.
 *
 * ⚠️ IT EXTENDS `OwnerBlockException` ON PURPOSE — FAIL-SAFE BY INHERITANCE. Every existing
 * `catch (OwnerBlockException)` already refuses the write, so a caller that never learns about this subclass
 * still REFUSES rather than falling through and writing. Ordering a narrower catch first only improves the
 * MESSAGE; it can never be what makes the refusal happen.
 */
class OwnerUnitPropertyRemovedException extends OwnerBlockException
{
    public static function forUnit(string $unitId): self
    {
        return new self("Unit [{$unitId}] is at a property that has been removed, so it can no longer be managed.");
    }
}
