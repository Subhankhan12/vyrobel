<?php

declare(strict_types=1);

namespace App\Exceptions\Owner;

use App\Services\OwnerPayoutService;
use RuntimeException;

/**
 * An owner's payouts are PAUSED (DEC-n, ruled 2026-10-02: "a pause stops every payout"). {@see OwnerPayoutService} refuses
 * to create, re-queue or send a payout for a paused owner — ONE check behind the automatic run, Run payouts now, Create
 * payouts and Pay. Extends {@see RuntimeException} so every caller that already reports a refusal per payout reports this
 * one too, and the payout is left exactly as it was (resuming makes it payable again).
 */
class PayoutsPausedException extends RuntimeException
{
    public static function forOwner(string $ownerName): self
    {
        return new self(__('php/exceptions/owner/payouts_paused_exception.paused', ['owner' => $ownerName]));
    }
}
