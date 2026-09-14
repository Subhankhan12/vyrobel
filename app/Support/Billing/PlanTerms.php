<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Exceptions\MissingPlanTermsException;
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
        // ⚠️⚠️ AN EMPTY OR PARTIAL SNAPSHOT REFUSES — it does NOT default.
        //
        // Every `?? 0` below used to be a silent coercion, and together they turned "we captured nothing"
        // into a charge of **€0 with `tax_known: true`**: not a wrong number, an assertion that the number
        // is CERTAIN. Measured on three reachable paths — a subscription created against a soft-deleted
        // plan, a raw insert that bypasses the model's capture hook, and a partial capture with no price.
        //
        // ⚠️ THE RULE IS "MUST NOT ANSWER", NOT "MUST NOT ANSWER ZERO". A plausible non-zero default would
        //    be worse: an invented price on a charging path.
        // ⚠️ A NULL BASIS IS NOT A PRICE OF ZERO. `PlanIntegrity` refuses to make either shape sellable
        //    without a positive amount, so an absent amount means the capture is incomplete, not free.
        $pricingType = $captured['terms_pricing_type'] ?? null;

        if ($pricingType === null || $pricingType === '') {
            throw MissingPlanTermsException::noSnapshot();
        }

        $pricingType = (string) $pricingType;
        $perUnit = $pricingType === Plan::PRICING_PER_UNIT;

        // ⚠️ Only the basis THIS shape bills on is required. A captured FLAT plan legitimately carries a
        //    null `terms_unit_rate_minor` (the backfill writes the plan's own null straight through), and
        //    demanding both would refuse a perfectly good snapshot.
        $basis = $perUnit ? ($captured['terms_unit_rate_minor'] ?? null) : ($captured['terms_price_minor'] ?? null);

        if ($basis === null) {
            throw MissingPlanTermsException::noBasis($pricingType);
        }

        return new self(
            pricingType: $pricingType,
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
     * ⚠️⚠️ `tax_known: true` CAN NO LONGER BE EMITTED WITHOUT REAL TERMS BEHIND IT. It means "this gross is
     *      final", and it used to be returned for a snapshot that held nothing at all. The private
     *      constructor is reachable only through `fromPlan()` (a real catalogue row) and `fromCaptured()`
     *      (which now refuses an empty or basis-less snapshot), so every `true` here now rests on an amount
     *      somebody actually agreed to.
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
                // ⚠️⚠️ A FLAT PRICE OF ZERO IS NOT A FINAL GROSS OF ZERO — IT IS A PRICE NOBODY HAS SET.
                //
                // `tax_known: true` asserts "this gross is FINAL". `fromCaptured([])` was made to REFUSE
                // because it answered from NOTHING; this case is different and harder — the plan really
                // does say zero, so the figure is not invented. What is invented is the CONFIDENCE.
                //
                // ⚠️ AND THE PRODUCT HAS ALREADY DECIDED THAT ZERO IS NOT A PRICE. `PlanIntegrity::
                //    blockedFromActivation()` refuses to sell a flat plan whose price is not above zero
                //    ("A flat plan needs a price above zero before it can be sold"). There is no free tier
                //    here, so declining to call €0 final forbids nothing this product supports — it just
                //    stops a DRAFT, whose price has not been filled in yet, from being presented on the
                //    billing page as a settled €0.00 with no caveat.
                //
                // ⚠️ THE ZERO IS STILL RETURNED, NOT REFUSED. Refusing would strand a plan mid-creation and
                //    would forbid a free tier outright if one were ever wanted. The gross stays €0; only
                //    the claim that it is FINAL goes away, and the surfaces already have an honest phrase
                //    for that case because the per-unit shape needs one.
                'tax_known' => $this->priceMinor > 0,
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
