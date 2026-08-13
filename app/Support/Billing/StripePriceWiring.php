<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Plan;
use App\Support\Stripe\StripeSafetyGuard;
use RuntimeException;

/**
 * BILLING PASS 2 — the gate between a configured plan and a REAL Stripe charge (2026-08-13).
 *
 * Everything here exists to make one class of failure impossible: **charging a customer an amount that is not
 * the amount the product showed them.** Passes 1 and 3 made the engine and the page read one source; the last
 * hop — what Stripe is actually told — is this.
 *
 * ⚠️ TWO THINGS MUST BE TRUE BEFORE A PLAN MAY BILL REAL MONEY, and in production both are enforced by throwing:
 *
 *   1. **A REAL PRICE ID.** `BillingPlanSeeder` falls back to `price_fake_*` when `STRIPE_PRICE_*` is unset —
 *      correct in dev (the fake client stands in), catastrophic in production, where it would send an id Stripe
 *      has never heard of. It fails loudly there rather than quietly.
 *
 *   2. ⚠️ **A DECLARED TAX CONVENTION — the gap this pass found.** The checkout sends `price` + `quantity` and
 *      NOTHING about tax. A per-unit plan's engine total is `units × rate + VAT`, so with a €9 Price and 8
 *      units Stripe would take €72.00 while the page says €88.56 — a **€16.56/month silent under-charge**, and
 *      §N.8d at the money boundary. The two honest resolutions (a VAT-inclusive unit Price, or Stripe Tax
 *      adding it) are a FOUNDER decision with different compliance consequences, so this class does not pick
 *      one — it refuses to bill a per-unit plan in production until one is declared.
 *
 * ⚠️ DEV AND CI ARE DELIBERATELY UNAFFECTED. With `STRIPE_FAKE=true` the fake client stands in, no money moves,
 * and fake ids are the right answer. The strictness applies exactly where it matters.
 */
class StripePriceWiring
{
    /** A Stripe id minted by the seeder's fallback, never by Stripe. */
    public const FAKE_PREFIX = 'price_fake_';

    /**
     * The Stripe Price id to charge this plan against — verified fit for the current environment.
     *
     * @throws RuntimeException in production when the plan is not wired to a real Price.
     */
    public static function priceIdFor(Plan $plan): string
    {
        $priceId = (string) ($plan->stripe_price_id ?? '');

        if (! self::productionMoneyPath()) {
            return $priceId;   // dev/CI: the fake client accepts (and expects) the placeholder.
        }

        if ($priceId === '') {
            throw new RuntimeException(
                "Plan [{$plan->code}] has no Stripe Price id. Set the matching STRIPE_PRICE_* env value to the ".
                'real Price created in the Stripe dashboard — see docs/BILLING-STRIPE-PRICES.md.'
            );
        }

        if (str_starts_with($priceId, self::FAKE_PREFIX)) {
            throw new RuntimeException(
                "Plan [{$plan->code}] is wired to the placeholder Price [{$priceId}], which does not exist in ".
                'Stripe. Create the real Price and set the matching STRIPE_PRICE_* env value — see '.
                'docs/BILLING-STRIPE-PRICES.md.'
            );
        }

        return $priceId;
    }

    /**
     * Refuse to bill a plan whose tax convention is undeclared (production only).
     *
     * ⚠️ Only PER-UNIT plans are gated. A flat tier is VAT-INCLUSIVE by founder ruling — €49 is the final price
     * — so `price × 1` already equals what the page shows and there is nothing further to declare. A per-unit
     * plan's engine total includes VAT that nothing currently sends to Stripe, so it cannot be billed until the
     * founder says how that VAT arrives.
     *
     * @throws RuntimeException in production when a per-unit plan has no declared tax convention.
     */
    public static function assertTaxConventionResolved(Plan $plan): void
    {
        if (! $plan->isPerUnit() || ! self::productionMoneyPath()) {
            return;
        }

        if (! in_array(self::taxBehavior(), ['inclusive', 'exclusive'], true)) {
            throw new RuntimeException(
                "Plan [{$plan->code}] is per-unit, but no Stripe tax convention is declared: the engine adds ".
                'VAT that the checkout does not send, so Stripe would charge the pre-VAT total. Set '.
                'STRIPE_TAX_BEHAVIOR to "inclusive" (a VAT-inclusive unit Price) or "exclusive" (Stripe Tax '.
                'adds it) — see docs/BILLING-STRIPE-PRICES.md. This is a founder decision, not a default.'
            );
        }
    }

    /**
     * Whether this plan's tax is Stripe's to compute — i.e. its Price is EXCLUSIVE of tax.
     *
     * ⚠️ BILLING PASS 2b — THIS IS THE LIFT OF PASS 2's REFUSAL, AND IT LIFTS FOR THE RIGHT REASON. Pass 2 made a
     * per-unit plan throw in production because the engine added a VAT the checkout never sent. The founder has
     * now ruled: **Stripe Tax, `tax_behavior: exclusive`.** So `assertTaxConventionResolved()` stops throwing —
     * not because the guard was weakened, but because `automatic_tax` is now actually wired and the pre-tax
     * subtotal the app computes really is the amount that reaches Stripe. The guard still fires if someone
     * unsets the convention.
     *
     * A FLAT tier is never Stripe-Tax'd: €49 is VAT-inclusive and final by the same ruling.
     */
    public static function usesStripeTax(Plan $plan): bool
    {
        return $plan->isPerUnit() && self::taxBehavior() === 'exclusive';
    }

    /** The declared convention for how tax reaches Stripe on an exclusive Price. */
    private static function taxBehavior(): string
    {
        return (string) (config('billing.stripe_tax_behavior') ?? '');
    }

    /**
     * Whether this environment can move REAL money — production AND not faking.
     *
     * Mirrors {@see StripeSafetyGuard}'s posture rather than re-deciding it: the guard binds
     * the fake client outside production, so anywhere it fakes, these checks would be theatre.
     */
    private static function productionMoneyPath(): bool
    {
        return app()->environment('production') && ! (bool) config('services.stripe.fake', false);
    }
}
