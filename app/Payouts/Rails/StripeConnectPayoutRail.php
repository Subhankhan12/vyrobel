<?php

declare(strict_types=1);

namespace App\Payouts\Rails;

use App\Models\Payout;
use App\Payouts\Contracts\PayoutRail;
use App\Payouts\PayoutResult;
use RuntimeException;

/**
 * SKELETON ONLY — Stripe Connect owner payouts (Phase 3 · Module 1 Step 5). DEFERRED and UNVERIFIED:
 * real disbursement needs connected-account onboarding + KYC and is a separate credentialed live
 * checkpoint (like the Stripe payments one). It is config-gated (`services.payouts.rail=stripe_connect`)
 * and is NEVER the active rail in tests. `send()` throws so it can never silently pretend to pay.
 */
class StripeConnectPayoutRail implements PayoutRail
{
    public function name(): string
    {
        return 'stripe_connect';
    }

    public function send(Payout $payout): PayoutResult
    {
        throw new RuntimeException(
            'StripeConnectPayoutRail is a deferred skeleton — real Stripe Connect disbursement '
            .'(connected-account onboarding + KYC) is an unverified live checkpoint, not implemented.'
        );
    }
}
