<?php

declare(strict_types=1);

/*
 * Vyrobel subscription billing (Phase 7b) — the SaaS revenue model: Vyrobel bills the OPERATOR.
 *
 * ⚠️ EVERYTHING BELOW IS A CONFIGURABLE PLACEHOLDER. The trial length, tier names, prices and feature splits
 * are NOT final commercial pricing — they are sensible defaults Subhan sets before launch. Nothing here is
 * fabricated market pricing presented as real. `BillingPlanSeeder` materialises these into the real `plans`
 * + `plan_features` tables (one plan row per tier × interval).
 *
 * ⚠️ THE LIVE CHARGE IS ACCOUNT-GATED. Taking real money needs live Stripe Billing (Subhan's to obtain), so
 * `subscriptions.stripe_subscription_id` stays null and no charge is computed/taken here — the same honest
 * deferral as the guest card charge + the owner payout rail. The trial + state + gating below are REAL.
 */

return [
    // The free-trial length. A newly-provisioned org (Phase 7a) starts trialing for this many days from its
    // creation timestamp; the remaining days are DERIVED from that timestamp, never a stored countdown.
    'trial_days' => 14,

    // The tier whose features the trial grants (full access to evaluate everything).
    'trial_tier' => 'pro',

    'default_currency' => 'EUR',

    // Per-unit subscription pricing (Operator Billing exact-match #85) — the subscription is billed PER
    // active unit on the rack. ⚠️ PLACEHOLDER, like the tier prices: Subhan sets the real per-unit price +
    // VAT rate before go-live. The Billing page computes the real figure from the org's REAL active-unit
    // count × this price × (1 + VAT), reconciling (#17) — no real charge is taken until the live-Stripe flip.
    'per_unit_minor' => 900,   // €9.00 / active unit / month — PLACEHOLDER
    'vat_pct' => 23,           // the operator's subscription VAT rate (23%) — PLACEHOLDER

    /*
     * The tiers. `features` are keyed capabilities: `enabled` (boolean gate) and/or `limit` (int, null =
     * unlimited). The Paywall/Checkout read these for display; the SG3 feature-gate reads the org's plan's
     * `plan_features` rows (seeded from here) — so a tier difference like `channels` (Pro-only) is a REAL gate.
     */
    'tiers' => [
        'essential' => [
            'name' => 'Essential',
            'blurb' => 'For a single property finding its feet.',
            'prices_minor' => ['monthly' => 4900, 'yearly' => 49000],   // €49/mo · €490/yr — PLACEHOLDER
            // The Stripe Price ids (test-mode → live). Subhan sets these from the Stripe dashboard per
            // tier×interval; null defaults to a fake placeholder so the app flows end-to-end on the fake client.
            'stripe_prices' => ['monthly' => env('STRIPE_PRICE_ESSENTIAL_MONTHLY'), 'yearly' => env('STRIPE_PRICE_ESSENTIAL_YEARLY')],
            'features' => [
                'analytics' => ['label' => 'Insights & reports', 'enabled' => true],
                'channels' => ['label' => 'Channel manager', 'enabled' => false],   // Pro-only (a real tier gate)
                'team' => ['label' => 'Team members', 'enabled' => true, 'limit' => 5],
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'blurb' => 'For a growing portfolio across properties.',
            'prices_minor' => ['monthly' => 9900, 'yearly' => 99000],   // €99/mo · €990/yr — PLACEHOLDER
            'stripe_prices' => ['monthly' => env('STRIPE_PRICE_PRO_MONTHLY'), 'yearly' => env('STRIPE_PRICE_PRO_YEARLY')],
            'features' => [
                'analytics' => ['label' => 'Insights & reports', 'enabled' => true],
                'channels' => ['label' => 'Channel manager', 'enabled' => true],
                'team' => ['label' => 'Team members', 'enabled' => true, 'limit' => null],   // unlimited
            ],
        ],
    ],
];
