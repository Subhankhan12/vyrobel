<?php

declare(strict_types=1);

namespace App\Services;

use App\Console\Commands\SetTaxRate;
use App\Models\TaxRate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * THE ONE reader/writer of configured tax rates (Wave D · tax engine · Phase 1). ⚠️ The engines NEVER hardcode a
 * rate — they ask this service, which returns a FOUNDER-CONFIGURED value or null (honest "not configured"). A rate
 * is only authoritative when the founder set it (`is_example = false`); a seeded EXAMPLE is returned but flagged so
 * the surface can label it "not for filing". Prefers a REAL rate over an example, then the latest effective one.
 */
class TaxRateService
{
    /**
     * The effective configured rate for a jurisdiction + type on a date, or NULL when none is configured (the
     * honest "not configured" state — never a fabricated rate). A real rate wins over an example.
     */
    public function rateFor(string $jurisdiction, string $rateType, ?CarbonInterface $onDate = null): ?TaxRate
    {
        $date = ($onDate ?? CarbonImmutable::now())->toDateString();

        $candidates = TaxRate::query()
            ->where('jurisdiction', $jurisdiction)
            ->where('rate_type', $rateType)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date))
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $real = $candidates->where('is_example', false)
            ->sortByDesc(fn (TaxRate $r): int => $r->effective_from->timestamp)->first();

        return $real ?? $candidates->sortByDesc(fn (TaxRate $r): int => $r->effective_from->timestamp)->first();
    }

    /**
     * Configure a rate. A REAL rate (`isExample = false`, the {@see SetTaxRate} default) is
     * authoritative; history is append-only (a new effective row, so past figures never move). Returns the row.
     */
    public function setRate(string $jurisdiction, string $rateType, float $percent, string $effectiveFrom, ?string $effectiveTo = null, bool $isExample = false, ?string $note = null): TaxRate
    {
        return TaxRate::create([
            'jurisdiction' => $jurisdiction,
            'rate_type' => $rateType,
            'percent' => number_format($percent, 3, '.', ''),
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'is_example' => $isExample,
            'note' => $note,
        ]);
    }
}
