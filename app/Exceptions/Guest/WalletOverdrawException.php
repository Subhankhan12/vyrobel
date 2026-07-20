<?php

declare(strict_types=1);

namespace App\Exceptions\Guest;

use App\Services\GuestWalletService;
use RuntimeException;

/**
 * A wallet DEBIT would exceed the guest's derived spendable balance (Phase 4c · Sub-gate 2). The wallet can never
 * overdraw: {@see GuestWalletService::debit()} throws this BEFORE writing, so a guest can never spend
 * more credit than they hold (expired credits don't count toward the spendable balance). The controller catches it
 * and reports honestly — nothing is written.
 */
class WalletOverdrawException extends RuntimeException
{
    public static function forShortfall(int $requestedMinor, int $availableMinor): self
    {
        return new self("A wallet debit of {$requestedMinor} exceeds the spendable balance of {$availableMinor}.");
    }
}
