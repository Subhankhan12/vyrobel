<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use App\Services\OwnerPayoutService;
use RuntimeException;

/**
 * A payout is HELD and cannot be disbursed yet (Phase 2 · Sub-gate 4 — Tax Center). Today the one cause is
 * missing tax details: {@see OwnerPayoutService::pay()} refuses to send money to an owner with no
 * tax ID on file, and releases automatically once they add one. Extends {@see RuntimeException} so the operator
 * Payout Run — which already catches RuntimeException per payout — reports it honestly and leaves the payout
 * `pending` (never a 500, never a half-sent transfer), rather than needing a new catch.
 */
class PayoutHeldException extends RuntimeException
{
    public static function missingTaxDetails(string $ownerName): self
    {
        return new self("held until {$ownerName} adds their tax details in the Tax Center.");
    }
}
