<?php

declare(strict_types=1);

namespace App\Payments\Stripe;

use App\Providers\AppServiceProvider;

/**
 * THE money #1 guard (Phase 8 · live Stripe) — a LIVE Stripe secret key must NEVER be used outside
 * `production`. The safety posture inverts for real money: the goal is that the build CANNOT charge a real
 * card, not that a charge "works". A dev / CI run uses either the {@see FakeStripeClient} (no network) or
 * real Stripe TEST-mode keys (`sk_test_…`, which move NO real money); a LIVE key (`sk_live_…` / `rk_live_…`)
 * is only ever honoured in `production`.
 *
 * Enforced in CODE, not by convention: {@see forceFakeOutsideProduction()} is consulted at the Stripe-client
 * binding ({@see AppServiceProvider}) — a live key requested outside production is refused and
 * the fake client is bound instead (a loud warning is logged), so a stray `STRIPE_FAKE=false` + a live key in
 * a dev `.env` can never reach live Stripe. (Test-mode keys are allowed outside production for the manual
 * live-test checkpoint; CI still pins `STRIPE_FAKE=true`.)
 */
final class StripeSafetyGuard
{
    /** Whether a secret is a LIVE Stripe key (as opposed to a test key or the fake). */
    public static function isLiveKey(?string $secret): bool
    {
        return is_string($secret) && (str_starts_with($secret, 'sk_live_') || str_starts_with($secret, 'rk_live_'));
    }

    /**
     * Whether the fake client MUST be used given the environment + config — true when already faking, OR when
     * a LIVE key is requested outside production (the refusal). A test-mode key outside production is allowed
     * (it charges nothing real); only `production` may use a live key.
     */
    public static function forceFakeOutsideProduction(string $environment, bool $configuredFake, ?string $secret): bool
    {
        if ($configuredFake) {
            return true;
        }

        return $environment !== 'production' && self::isLiveKey($secret);
    }
}
