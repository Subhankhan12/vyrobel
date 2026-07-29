<?php

declare(strict_types=1);

namespace App\Payouts;

use App\Models\PayoutRun;
use App\Payments\Stripe\StripeSafetyGuard;

/**
 * The automated payout engine's TEST/LIVE boundary (payout engine · Phase 1). ⚠️ The engine is `live` ONLY
 * when the configured Stripe secret is a genuine LIVE key AND the app is running in `production` — the exact
 * same posture {@see StripeSafetyGuard} enforces for charges. In every other case (a test key, the fake
 * client, any non-production environment) it is `test`, and real money never moves.
 *
 * Stamped on every {@see PayoutRun} (`mode`), logged on every run, and surfaced as the owner-page
 * TEST-MODE banner. Pure + side-effect-free, so it is trivially assertable.
 */
final class PayoutEngineMode
{
    public static function current(): string
    {
        $secret = (string) config('services.stripe.secret');

        $live = StripeSafetyGuard::isLiveKey($secret) && app()->isProduction();

        return $live ? 'live' : 'test';
    }

    public static function isTest(): bool
    {
        return self::current() === 'test';
    }
}
