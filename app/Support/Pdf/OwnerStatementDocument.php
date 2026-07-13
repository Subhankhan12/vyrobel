<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use App\Models\Organization;
use App\Models\OwnerStatement;
use App\Models\OwnerStatementLine;
use App\Support\Money\MoneyFormatter;

/**
 * Builds the OWNER STATEMENT PDF view data from the REAL, already-finalized statement + its
 * `owner_statement_lines`. Pure + read-only — it never touches the owner-accounting services/schema, and
 * renders ONLY the engine-computed lines (no invented "card-processing fee" line ever appears). Because
 * the component lines are the same rows the screen reads and they sum to the `net` line, the PDF
 * reconciles to the on-screen statement to the cent (and, via the shared MoneyFormatter, to the
 * character).
 */
final class OwnerStatementDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function data(OwnerStatement $statement): array
    {
        $currency = (string) $statement->currency;

        $lines = OwnerStatementLine::query()
            ->where('owner_statement_id', $statement->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (OwnerStatementLine $l): array => [
                'description' => (string) $l->description,
                'amount_display' => MoneyFormatter::display((int) $l->amount_minor, (string) $l->currency, signed: $l->line_type !== 'net'),
                'is_negative' => (int) $l->amount_minor < 0,
                'is_net' => $l->line_type === 'net',
                'unverified' => (bool) $l->unverified,
            ])
            ->all();

        return [
            'operator' => (string) (Organization::query()->whereKey($statement->organization_id)->value('name') ?? 'Vyrobel'),
            'owner_name' => (string) ($statement->owner->name ?? 'Owner'),
            'sequence_no' => $statement->sequence_no,
            'status' => (string) $statement->status,
            'period_label' => $statement->period_start->format('F Y'),
            'period_range' => $statement->period_start->format('j M Y').' – '.$statement->period_end->format('j M Y'),
            'currency' => $currency,
            'lines' => $lines,
            'net_display' => MoneyFormatter::display((int) $statement->net_payable_minor, $currency),
            'has_unverified' => (bool) $statement->has_unverified,
            // FX shown ONLY when the statement genuinely converts currencies (source ≠ payout currency;
            // operator-supplied rate, never a silent float) — same honesty as the screen.
            'fx' => ((string) $statement->source_currency !== $currency)
                ? ['source' => (string) $statement->source_currency, 'rate' => (string) $statement->fx_rate, 'src' => (string) $statement->fx_source]
                : null,
        ];
    }
}
