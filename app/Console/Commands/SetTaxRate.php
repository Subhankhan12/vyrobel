<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TaxRate;
use App\Services\TaxRateService;
use Illuminate\Console\Command;

/**
 * Platform-admin mechanism to configure a REAL tax rate (Wave D · tax engine · Phase 1). ⚠️ This is how a genuine,
 * authoritative rate enters the system — run on the server by the founder/platform admin per jurisdiction. Sets
 * `is_example = false` (authoritative). The session NEVER invents rates; the founder supplies the real ones here.
 *
 *   php artisan tax:set-rate PT accommodation_vat 6 --from=2026-01-01
 */
class SetTaxRate extends Command
{
    protected $signature = 'tax:set-rate
        {jurisdiction : The country / tax jurisdiction (matches owners.tax_country, e.g. PT)}
        {type : accommodation_vat | service_vat | withholding}
        {percent : The rate as a percentage (e.g. 6, 23, 25)}
        {--from= : Effective-from date (YYYY-MM-DD); defaults to today}
        {--to= : Effective-to date (YYYY-MM-DD); open-ended if omitted}
        {--example : Mark as an EXAMPLE (not authoritative) instead of a real rate}';

    protected $description = 'Configure a REAL tax rate for a jurisdiction (authoritative — the founder supplies real rates).';

    public function handle(TaxRateService $rates): int
    {
        $type = (string) $this->argument('type');
        if (! in_array($type, TaxRate::TYPES, true)) {
            $this->error('Invalid type. Use one of: '.implode(', ', TaxRate::TYPES));

            return self::FAILURE;
        }

        if (! is_numeric($this->argument('percent'))) {
            $this->error('Percent must be numeric (e.g. 6, 23, 25).');

            return self::FAILURE;
        }

        $rate = $rates->setRate(
            jurisdiction: (string) $this->argument('jurisdiction'),
            rateType: $type,
            percent: (float) $this->argument('percent'),
            effectiveFrom: (string) ($this->option('from') ?: now()->toDateString()),
            effectiveTo: $this->option('to') ? (string) $this->option('to') : null,
            isExample: (bool) $this->option('example'),
            note: $this->option('example') ? 'EXAMPLE — replace with the real rate.' : null,
        );

        $this->info(sprintf(
            '%s tax rate set: %s / %s = %s%% from %s%s.',
            $rate->is_example ? 'EXAMPLE' : 'REAL',
            $rate->jurisdiction, $rate->rate_type, $rate->percent, $rate->effective_from->toDateString(),
            $rate->effective_to ? ' to '.$rate->effective_to->toDateString() : '',
        ));

        return self::SUCCESS;
    }
}
