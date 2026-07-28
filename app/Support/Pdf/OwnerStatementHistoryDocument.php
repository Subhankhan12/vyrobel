<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\OwnerStatement;
use App\Support\Money\MoneyFormatter;
use Illuminate\Support\Collection;

/**
 * Builds the OWNER STATEMENT-HISTORY PDF view data from the owner's REAL finalized statements. Pure + read-only.
 * Every figure is a real `owner_statements` row; the totals are Σ per PAYOUT currency (never a cross-currency sum —
 * a silent FX the backend never made, #17), so the document RECONCILES: the total net shown == Σ of the listed
 * rows' net. No guest data (statements carry none). A year filter only clips the set; it never invents a row.
 */
final class OwnerStatementHistoryDocument
{
    /**
     * @param  Collection<int, OwnerStatement>  $statements  owner-scoped, finalized, period DESC
     * @return array<string, mixed>
     */
    public static function data(Collection $statements, ?int $year): array
    {
        $first = $statements->first();
        $org = $first instanceof OwnerStatement
            ? Organization::query()->whereKey($first->organization_id)->first()
            : null;

        $rows = $statements->map(fn (OwnerStatement $s): array => [
            'month_label' => $s->period_start->format('F Y'),
            'period_range' => $s->period_start->format('j M Y').' – '.$s->period_end->format('j M Y'),
            'gross_display' => MoneyFormatter::display((int) $s->gross_minor, (string) $s->source_currency),
            'net_display' => MoneyFormatter::display((int) $s->net_payable_minor, (string) $s->currency),
            'status_label' => $s->status === 'paid' ? 'Paid' : 'Payout pending',
        ])->values()->all();

        // Σ per payout currency — reconciles to the listed rows; rendered per currency (never summed across).
        $totals = $statements
            ->groupBy(fn (OwnerStatement $s): string => (string) $s->currency)
            ->map(fn (Collection $g, string $currency): string => MoneyFormatter::display(
                (int) $g->sum(fn (OwnerStatement $s): int => (int) $s->net_payable_minor),
                $currency,
            ))
            ->values()
            ->all();

        $company = $org instanceof Organization ? (string) ($org->billing_company ?: $org->name) : 'Vyrobel';
        $ownerName = ($first instanceof OwnerStatement && $first->owner !== null) ? (string) $first->owner->name : 'Owner';

        return [
            'company' => $company,
            'owner_name' => $ownerName,
            'scope_label' => $year !== null ? (string) $year : 'All years',
            'count' => $statements->count(),
            'rows' => $rows,
            'net_totals' => $totals,
        ];
    }
}
