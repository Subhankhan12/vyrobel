<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanFeature;
use App\Support\Billing\StripePriceWiring;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Materialises the placeholder tiers in config/billing.php into the real `plans` + `plan_features` tables —
 * one Plan row per tier × interval (e.g. `essential_monthly`, `pro_yearly`), each with its tier's features.
 * Idempotent (keyed on `plans.code`), so re-seeding just syncs. Plans are a GLOBAL platform catalog (not
 * tenant-scoped). Prices/features are placeholders (see the config header) — nothing is fabricated as final.
 */
class BillingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $currency = (string) config('billing.default_currency', 'EUR');

        /** @var array<string, array{name: string, prices_minor: array<string,int>, stripe_prices?: array<string, ?string>, features: array<string, array{label: string, enabled?: bool, limit?: ?int}>}> $tiers */
        $tiers = config('billing.tiers', []);

        foreach ($tiers as $tierCode => $tier) {
            foreach (['monthly', 'yearly'] as $interval) {
                // The Stripe Price id (Phase 8): the configured one (test-mode/live) or a fake placeholder so
                // the Checkout flow works end-to-end on the FakeStripeClient until Subhan sets the real ids.
                $stripePriceId = $tier['stripe_prices'][$interval] ?? null;
                $stripePriceId = is_string($stripePriceId) && $stripePriceId !== '' ? $stripePriceId : null;

                // ⚠️ BILLING PASS 2 — the fallback is DEV-ONLY now. It exists so the checkout flow works
                // end-to-end on the fake client; in production it would seed an id Stripe has never heard of,
                // which is exactly the silent-wrong-charge shape this project treats as a defect. There, an
                // unset STRIPE_PRICE_* is a loud failure at seed time instead.
                if ($stripePriceId === null) {
                    if (app()->environment('production') && ! (bool) config('services.stripe.fake', false)) {
                        throw new RuntimeException(
                            "No Stripe Price configured for [{$tierCode}_{$interval}]. Set ".
                            'STRIPE_PRICE_'.strtoupper($tierCode).'_'.strtoupper($interval).' to the real Price '.
                            'id from the Stripe dashboard — see docs/BILLING-STRIPE-PRICES.md.'
                        );
                    }

                    $stripePriceId = StripePriceWiring::FAKE_PREFIX.$tierCode.'_'.$interval;
                }

                $plan = Plan::query()->updateOrCreate(
                    ['code' => $tierCode.'_'.$interval],
                    [
                        'name' => $tier['name'],
                        'price_minor' => (int) ($tier['prices_minor'][$interval] ?? 0),
                        // ⚠️ The config tiers are FLAT and stay flat — this pass changes no existing charge.
                        // An explicit type beats relying on the column default: the shape is now a stated fact
                        // about each plan rather than something inferred from silence.
                        'pricing_type' => Plan::PRICING_FLAT,
                        'currency' => $currency,
                        'stripe_price_id' => $stripePriceId,
                        'interval' => $interval,
                        'is_active' => true,
                    ],
                );

                foreach ($tier['features'] as $key => $feature) {
                    PlanFeature::query()->updateOrCreate(
                        ['plan_id' => $plan->id, 'key' => $key],
                        [
                            'enabled' => (bool) ($feature['enabled'] ?? true),
                            'limit' => $feature['limit'] ?? null,
                        ],
                    );
                }
            }
        }
    }
}
