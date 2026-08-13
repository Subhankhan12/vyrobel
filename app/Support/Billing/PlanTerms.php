<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Plan;

/**
 * THE ONE charge calculation, and the only place a set of pricing terms is turned into money.
 *
 * ⚠️ WHY IT WAS EXTRACTED (the plan-panel pass, 2026-08-13). There are now TWO sources of terms — the live
 * `Plan` row (what a NEW subscriber would agree to) and a subscription's CAPTURED terms (what an existing one
 * already agreed to). Two sources with two calculations is precisely how `SubscriptionPricing` drifted from
 * `Plan::chargeFor()` and put €88.56 on one screen against €49.00 on another (§N.17.4). So the sources differ
 * and the **arithmetic does not**: both build a `PlanTerms` and call the same method.
 *
 * ⚠️ THE QUANTITY IS NOT IN HERE, ON PURPOSE. Terms are frozen; the unit count is DERIVED live from the org's
 * real units (billing pass 1), because an org that adds a unit genuinely owes for it. Freezing the quantity
 * would bill a stale number — the §N.8d-with-money trap {@see BillableUnits} exists to avoid. **Terms frozen,
 * quantity live.**
 */
class PlanTerms
{
    private function __construct(
        public readonly string $pricingType,
        public readonly int $priceMinor,
        public readonly int $unitRateMinor,
        public readonly int $vatPct,
        public readonly string $currency,
        public readonly ?string $stripePriceId,
    ) {}

    /** The terms a NEW subscriber would agree to — the live catalogue row. */
    public static function fromPlan(Plan $plan): self
    {
        return new self(
            pricingType: (string) ($plan->pricing_type ?? Plan::PRICING_FLAT),
            priceMinor: (int) $plan->price_minor,
            unitRateMinor: (int) ($plan->unit_rate_minor ?? 0),
            vatPct: (int) ($plan->vat_pct ?? 0),
            currency: (string) $plan->currency,
            stripePriceId: $plan->stripe_price_id,
        );
    }

    /**
     * Terms as CAPTURED on a subscription — immune to later edits of the catalogue row.
     *
     * @param  array<string, mixed>  $captured
     */
    public static function fromCaptured(array $captured): self
    {
        return new self(
            pricingType: (string) ($captured['terms_pricing_type'] ?? Plan::PRICING_FLAT),
            priceMinor: (int) ($captured['terms_price_minor'] ?? 0),
            unitRateMinor: (int) ($captured['terms_unit_rate_minor'] ?? 0),
            vatPct: (int) ($captured['terms_vat_pct'] ?? 0),
            currency: (string) ($captured['terms_currency'] ?? 'EUR'),
            stripePriceId: $captured['terms_stripe_price_id'] ?? null,
        );
    }

    public function isPerUnit(): bool
    {
        return $this->pricingType === Plan::PRICING_PER_UNIT;
    }

    /** The quantity these terms bill: 1 for a flat tier, the org's REAL active units for a per-unit one. */
    public function quantity(): int
    {
        return $this->isPerUnit() ? BillableUnits::count() : 1;
    }

    /**
     * What is owed this period, in integer minor units (#17).
     *
     * FLAT     → the agreed price. VAT-inclusive by founder ruling, so the app really does know the total.
     * PER_UNIT → rate × the org's real units, PRE-TAX. ⚠️ No VAT is added: under Stripe Tax the rate belongs to
     *            the customer (€0 for an EU business under reverse charge), so the app states the subtotal it
     *            can stand behind and defers the rest (billing pass 2b). `tax_known` says which case this is.
     *
     * @return array{units: int, net_minor: int, vat_minor: int, gross_minor: int, tax_known: bool, pricing_type: string}
     */
    public function charge(): array
    {
        if (! $this->isPerUnit()) {
            return [
                'units' => 1,
                'net_minor' => $this->priceMinor,
                'vat_minor' => 0,
                'gross_minor' => $this->priceMinor,
                'tax_known' => true,
                'pricing_type' => Plan::PRICING_FLAT,
            ];
        }

        $units = $this->quantity();
        $subtotal = $units * $this->unitRateMinor;

        return [
            'units' => $units,
            'net_minor' => $subtotal,
            // Zero because it is UNKNOWN app-side, not because it is nil.
            'vat_minor' => 0,
            'gross_minor' => $subtotal,
            'tax_known' => false,
            'pricing_type' => Plan::PRICING_PER_UNIT,
        ];
    }

    /** The captured-terms columns for a subscription row. */
    public function toCapturedColumns(): array
    {
        return [
            'terms_pricing_type' => $this->pricingType,
            'terms_price_minor' => $this->priceMinor,
            'terms_unit_rate_minor' => $this->unitRateMinor,
            'terms_vat_pct' => $this->vatPct,
            'terms_currency' => $this->currency,
            'terms_stripe_price_id' => $this->stripePriceId,
            'terms_captured_at' => now(),
        ];
    }
}
