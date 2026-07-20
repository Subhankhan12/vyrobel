<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\Owner;
use App\Services\OwnerTaxService;
use App\Support\Money\MoneyFormatter;

/**
 * Data for the owner Annual Tax Summary PDF (Phase 2 · Sub-gate 4) — a self-only document for ONE owner + year,
 * built ENTIRELY from the owner's REAL committed statements (the aggregation done in {@see OwnerTaxService::annualSummary()}).
 * Reconciles to the cent: gross + deductions = net, because a statement's buckets sum to its net. Every money
 * string comes through {@see MoneyFormatter} so the PDF matches the on-screen figures character-for-character.
 * No fabricated totals, no OTA brand (channel commissions only), no withholding (we don't compute it).
 *
 * The owner's OWN tax id / legal name appear here — it is their own document, downloaded self-only.
 */
final class OwnerAnnualTaxDocument
{
    /**
     * @param  array<string, mixed>  $summary  a {@see OwnerTaxService::annualSummary()} result
     * @return array<string, mixed>
     */
    public static function data(Owner $owner, array $summary): array
    {
        $currency = (string) $summary['currency'];
        $fmt = fn (int $minor, bool $signed = false): string => MoneyFormatter::display($minor, $currency, $signed);

        // Per-column month totals (summed from the same rows the table shows, so the Total row reconciles).
        $commissionMinor = (int) array_sum(array_column($summary['months'], 'commission_minor'));
        $feesMinor = (int) array_sum(array_column($summary['months'], 'fees_minor'));

        return [
            'operator' => (string) (Organization::query()->whereKey($owner->organization_id)->value('name') ?? 'Vyrobel'),
            'owner_name' => (string) $owner->name,
            'legal_name' => (string) ($owner->legal_name ?? $owner->name),
            'tax_id' => (string) ($owner->tax_id ?? ''),
            'tax_country' => (string) ($owner->tax_country ?? ''),
            'year' => (int) $summary['year'],
            'period' => $summary['period_start'].' – '.$summary['period_end'],
            'statement_count' => (int) $summary['statement_count'],
            'currency' => $currency,
            'gross_display' => $fmt((int) $summary['gross_minor']),
            'deductions_display' => $fmt((int) $summary['deductions_minor'], true),
            'net_display' => $fmt((int) $summary['net_minor']),
            'total_commission' => $fmt($commissionMinor, true),
            'total_fees' => $fmt($feesMinor, true),
            'months' => array_map(fn (array $m): array => [
                'label' => (string) $m['label'],
                'gross' => $fmt((int) $m['gross_minor']),
                'commission' => $fmt((int) $m['commission_minor'], true),
                'fees' => $fmt((int) $m['fees_minor'], true),
                'net' => $fmt((int) $m['net_minor']),
            ], $summary['months']),
            // Only categories with a real, non-zero figure — never an invented €0.00 line.
            'categories' => array_values(array_filter(array_map(fn (array $c): array => [
                'label' => (string) $c['label'],
                'amount' => $fmt((int) $c['minor'], true),
                'minor' => (int) $c['minor'],
            ], $summary['categories']), fn (array $c): bool => $c['minor'] !== 0)),
        ];
    }
}
