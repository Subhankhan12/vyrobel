<?php

declare(strict_types=1);

namespace App\Payouts\Contracts;

use App\Models\Payout;
use App\Payouts\PayoutResult;

/**
 * A payout disbursement rail (Phase 3 · Module 1 Step 5) — mirrors the Phase 1 PaymentGateway seam.
 * The active rail is config-driven (`services.payouts.rail`). `ManualPayoutRail` records an offline
 * bank transfer as paid; `StripeConnectPayoutRail` is a deferred, config-gated skeleton.
 */
interface PayoutRail
{
    /** Machine name of the rail (matches `payouts.rail`): 'manual' | 'stripe_connect'. */
    public function name(): string;

    /** Disburse the payout. Returns success + an external reference, or a failure reason. */
    public function send(Payout $payout): PayoutResult;
}
