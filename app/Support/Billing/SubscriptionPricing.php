<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Unit;

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
     * @return array{units: int, per_unit_minor: int, net_minor: int, vat_minor: int, gross_minor: int, vat_pct: int, interval: string}
     */
    public static function current(string $interval = 'monthly'): array
    {
        $units = Unit::query()->where('is_active', true)->count();

        $perUnit = (int) config('billing.per_unit_minor', 900);
        $vatPct = (int) config('billing.vat_pct', 23);

        // Yearly = 12 months at the yearly-vs-monthly ratio of the Pro tier (2 months free ≈ 10/12), so the
        // "switch to yearly · save €X" figure is derived from the real config, not invented.
        $monthlyNet = $units * $perUnit;
        $net = $interval === 'yearly' ? self::yearlyNet($monthlyNet) : $monthlyNet;

        $vat = (int) round($net * $vatPct / 100);

        return [
            'units' => $units,
            'per_unit_minor' => $perUnit,
            'net_minor' => $net,
            'vat_minor' => $vat,
            'gross_minor' => $net + $vat,
            'vat_pct' => $vatPct,
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
