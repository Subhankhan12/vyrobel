<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Plan;
use Illuminate\Validation\Rule;

/**
 * WHAT A PLAN MUST BE FOR THE ENGINE TO CHARGE IT (the plan-panel pass, 2026-08-13).
 *
 * ⚠️ ON A MONEY SURFACE THE VALIDATION *IS* THE SAFETY (§N.18.2). Everything this class rejects is something the
 * engine would otherwise be handed and have to interpret: a per-unit plan with no rate bills €0 × units; an
 * unknown `pricing_type` falls through `isPerUnit()` to the flat branch and quietly bills the wrong shape; a
 * plan with no Stripe Price reaches a checkout that cannot complete. None of those announce themselves — they
 * surface at a customer's card. **A permissive rule here is not a lenient form; it is the bug.**
 *
 * ⚠️ ONE RULESET, BOTH PATHS (§N.8f). Create and edit share {@see rules()}. Two rulesets would drift, and the
 * looser one would become the way to save a malformed plan.
 */
class PlanIntegrity
{
    /** The only two shapes the engine understands — a CLOSED set, never a free string. */
    public const SHAPES = [Plan::PRICING_FLAT, Plan::PRICING_PER_UNIT];

    /**
     * Validation rules for a plan save.
     *
     * ⚠️ The shape-specific requirements are conditional on `pricing_type`, so a per-unit plan cannot be saved
     * without a rate and a flat one cannot be saved without a price — each is required exactly where it means
     * something, rather than both being optional and hoping the form is filled in properly.
     *
     * @return array<string, mixed>
     */
    public static function rules(?string $ignorePlanId = null): array
    {
        return [
            'code' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('plans', 'code')->ignore($ignorePlanId)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'pricing_type' => ['required', Rule::in(self::SHAPES)],
            'interval' => ['required', Rule::in(['monthly', 'yearly'])],
            'currency' => ['required', 'string', 'size:3'],

            // FLAT: the price IS the charge, so it must be present and positive.
            'price' => ['required_if:pricing_type,'.Plan::PRICING_FLAT, 'nullable', 'numeric', 'min:0'],

            // PER-UNIT: the rate is the charge basis. ⚠️ `gt:0` not `min:0` — a €0 rate is not a cheap plan,
            // it is a plan that silently bills nothing however many units the org runs.
            'unit_rate' => ['required_if:pricing_type,'.Plan::PRICING_PER_UNIT, 'nullable', 'numeric', 'gt:0'],

            // Informational only: with Stripe Tax the real rate is the customer's (billing pass 2b). Kept
            // because the letterhead and the flat-tier display still reference a configured percentage.
            'vat_pct' => ['nullable', 'integer', 'min:0', 'max:100'],

            'stripe_price_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Why this plan may NOT be offered to new subscribers yet, or null when it may.
     *
     * ⚠️ A PLAN WITHOUT A REAL STRIPE PRICE IS A DRAFT, NOT A PRODUCT. The panel can create a plan; only the
     * founder can create the Stripe Price behind it (`StripeSafetyGuard` forbids a live key outside
     * production). Activating one regardless would put a tier on the paywall whose checkout cannot complete —
     * exactly the broken checkout billing pass 2 made throw. This keeps that from being reachable in the first
     * place, which is the better half of the same guard.
     */
    public static function blockedFromActivation(Plan $plan): ?string
    {
        if (! in_array((string) $plan->pricing_type, self::SHAPES, true)) {
            return 'Its pricing shape is not one the billing engine understands.';
        }

        if ($plan->isPerUnit() && (int) ($plan->unit_rate_minor ?? 0) <= 0) {
            return 'A per-unit plan needs a unit rate above zero before it can be sold.';
        }

        if (! $plan->isPerUnit() && (int) $plan->price_minor <= 0) {
            return 'A flat plan needs a price above zero before it can be sold.';
        }

        $priceId = trim((string) ($plan->stripe_price_id ?? ''));

        if ($priceId === '') {
            return 'It has no Stripe Price id yet — create the Price in Stripe and paste its id here first.';
        }

        // ⚠️ A placeholder is fine in dev (the fake client stands in) and catastrophic in production, which is
        // exactly the split `StripePriceWiring` already draws. Mirrored rather than re-decided (§N.8f).
        if (str_starts_with($priceId, StripePriceWiring::FAKE_PREFIX) && app()->environment('production')
            && ! (bool) config('services.stripe.fake', false)) {
            return 'It is still wired to a placeholder Price id, which does not exist in Stripe.';
        }

        return null;
    }

    /** Convenience for views: may this plan be offered to new subscribers? */
    public static function isSellable(Plan $plan): bool
    {
        return self::blockedFromActivation($plan) === null;
    }
}
