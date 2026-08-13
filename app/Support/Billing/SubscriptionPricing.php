<?php

declare(strict_types=1);

namespace App\Support\Billing;

/**
 * The ONE derivation of an operator's subscription cost (Operator Billing exact-match #85) — REAL per-unit
 * pricing computed from the org's REAL active-unit count × the configured per-unit price × VAT.
 *
 * ⚠️ The per-unit price + VAT rate are CONFIGURABLE PLACEHOLDERS (`config/billing.php`), exactly like the
 * tier prices — flagged as such, never fabricated market pricing. The COUNT is real; the figure reconciles
 * (net + VAT == gross, #17). No charge is taken here — the live charge stays account-gated until go-live.
 *
 * Tenant-scoped: `Unit` is BelongsToOrganization, so the active-unit count is the acting org's own.
 */
final class SubscriptionPricing
{
    /**
     * @param  string  $interval  'monthly'|'yearly' — yearly applies the config yearly discount ratio.
     *
     * ⚠️ Every figure returned is PRE-TAX. Stripe Tax computes the customer's own tax at checkout.
     * @return array{units: int, per_unit_minor: int, net_minor: int, vat_minor: int, gross_minor: int, vat_pct: int, interval: string}
     */
    public static function current(string $interval = 'monthly'): array
    {
        // ⚠️ THE ONE source, shared with the charge path (Plan::quantityFor) — the figure DISPLAYED here and
        // the quantity CHARGED to Stripe must be the same expression, or one of them lies (§N.8d, with money).
        $units = BillableUnits::count();

        $perUnit = (int) config('billing.per_unit_minor', 900);

        // Yearly = 12 months at the yearly-vs-monthly ratio of the Pro tier (2 months free ≈ 10/12), so the
        // "switch to yearly · save €X" figure is derived from the real config, not invented.
        $monthlyNet = $units * $perUnit;
        $net = $interval === 'yearly' ? self::yearlyNet($monthlyNet) : $monthlyNet;

        // ⚠️ BILLING PASS 2b — NO VAT HERE EITHER. This added a hardcoded 23%, and after the per-unit engine
        // stopped doing so it became a SECOND, divergent total for one concept (§N.8f): 5535 here against 4500
        // there. Under Stripe Tax the rate belongs to the customer — €0 for an EU business under reverse charge
        // — so the honest figure is the pre-tax subtotal, and it now matches `Plan::chargeFor()` exactly.
        //
        // The keys stay (callers read them) but state plainly that no tax is known app-side.
        return [
            'units' => $units,
            'per_unit_minor' => $perUnit,
            'net_minor' => $net,
            'vat_minor' => 0,
            'gross_minor' => $net,
            'vat_pct' => 0,
            'interval' => $interval === 'yearly' ? 'yearly' : 'monthly',
        ];
    }

    /** The annual net = the monthly net × the Pro tier's yearly/monthly ratio (real config, e.g. 10×). */
    private static function yearlyNet(int $monthlyNet): int
    {
        $tiers = (array) config('billing.tiers', []);
        $pro = $tiers['pro']['prices_minor'] ?? ['monthly' => 9900, 'yearly' => 99000];
        $ratio = ($pro['monthly'] ?? 0) > 0 ? ($pro['yearly'] / $pro['monthly']) : 12;   // months-per-year billed

        return (int) round($monthlyNet * $ratio);
    }

    /** The whole-year saving of yearly vs 12× monthly (minor, net) — the real "save €X" figure. */
    public static function yearlySavingMinor(): int
    {
        $monthly = self::current('monthly');
        $yearly = self::current('yearly');

        return max(0, $monthly['net_minor'] * 12 - $yearly['net_minor']);
    }
}
