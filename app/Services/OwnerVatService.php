<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\TaxRate;
use App\Support\Money\Money;
use App\Support\Money\MoneyFormatter;
use Carbon\CarbonImmutable;

/**
 * The owner VAT ENGINE (Wave D · tax engine · Phase 2) — INFORMATIONAL. ⚠️ Computes the owner's VAT position from
 * FOUNDER-CONFIGURED rates ({@see TaxRateService}) × REAL statement amounts; it NEVER invents a rate and NEVER
 * touches a payout (VAT is the owner's own liability to file). Per quarter + annual:
 *
 *   • Output VAT   = accommodation-VAT rate × the owner's RENTAL income (gross_minor).
 *   • Input VAT    = service-VAT rate × the AGENCY SERVICE FEE the owner is charged (|commission_minor|), deductible.
 *   • Net VAT      = output − input (payable if positive, reclaimable if negative).
 *
 * Integer money throughout (Money::percentage, half-up), reconciling to gross/commission to the cent (#17). Owner-
 * scoped (#28). HONEST STATES, never a fabricated figure: not VAT-registered → 'not_registered'; no configured rate
 * → 'not_configured'; a computed figure resting on an EXAMPLE rate → 'example' (flagged "not for filing"); a real
 * configured rate → 'ok'. Every populated result is labelled "computed from your configured rates — verify with
 * your accountant; not tax advice" by the surface.
 */
class OwnerVatService
{
    public function __construct(private TaxRateService $rates) {}

    /**
     * The owner's VAT summary for a year, by quarter. Returns a status + (when available) the computed figures.
     *
     * @return array<string, mixed>
     */
    public function summary(Owner $owner, int $year): array
    {
        $jurisdiction = (string) ($owner->tax_country ?? '');

        if (! (bool) $owner->vat_registered) {
            return $this->unavailable('not_registered', $jurisdiction);
        }

        // The rate is resolved for a date in the year (mid-year — a stable point within the period).
        $onDate = CarbonImmutable::create($year, 6, 30);
        $accRate = $jurisdiction === '' ? null : $this->rates->rateFor($jurisdiction, TaxRate::TYPE_ACCOMMODATION_VAT, $onDate);
        $serviceRate = $jurisdiction === '' ? null : $this->rates->rateFor($jurisdiction, TaxRate::TYPE_SERVICE_VAT, $onDate);

        if ($accRate === null) {
            return $this->unavailable('not_configured', $jurisdiction);
        }

        $statements = OwnerStatement::query()
            ->where('owner_id', $owner->getKey())
            ->whereIn('status', ['finalized', 'paid'])
            ->get()
            ->filter(fn (OwnerStatement $s): bool => (int) $s->period_start->format('Y') === $year);

        if ($statements->isEmpty()) {
            return $this->unavailable('no_statements', $jurisdiction);
        }

        $currency = (string) $statements->first()->source_currency;
        $isExample = $accRate->is_example || ($serviceRate !== null && $serviceRate->is_example);

        // Per-quarter buckets.
        $quarters = [];
        foreach ([1, 2, 3, 4] as $q) {
            $inQuarter = $statements->filter(fn (OwnerStatement $s): bool => intdiv((int) $s->period_start->format('n') - 1, 3) + 1 === $q);
            if ($inQuarter->isEmpty()) {
                continue;
            }

            $gross = (int) $inQuarter->sum(fn (OwnerStatement $s): int => (int) $s->gross_minor);
            $serviceFee = (int) $inQuarter->sum(fn (OwnerStatement $s): int => abs((int) $s->commission_minor));

            $output = Money::of($gross, $currency)->percentage($accRate->percentFloat())->amountMinor();
            $input = $serviceRate === null ? 0 : Money::of($serviceFee, $currency)->percentage($serviceRate->percentFloat())->amountMinor();

            $quarters[] = [
                'label' => 'Q'.$q,
                'gross_minor' => $gross,
                'service_fee_minor' => $serviceFee,
                'output_vat_minor' => $output,
                'input_vat_minor' => $input,
                'net_vat_minor' => $output - $input,
                'gross_display' => MoneyFormatter::display($gross, $currency),
                'output_display' => MoneyFormatter::display($output, $currency),
                'input_display' => MoneyFormatter::display($input, $currency),
                'net_display' => MoneyFormatter::display($output - $input, $currency),
            ];
        }

        $annualOutput = (int) array_sum(array_column($quarters, 'output_vat_minor'));
        $annualInput = (int) array_sum(array_column($quarters, 'input_vat_minor'));

        return [
            'available' => true,
            'status' => $isExample ? 'example' : 'ok',
            'jurisdiction' => $jurisdiction,
            'year' => $year,
            'currency' => $currency,
            'accommodation_rate' => $accRate->percentFloat(),
            'service_rate' => $serviceRate?->percentFloat(),
            'is_example' => $isExample,
            'service_rate_configured' => $serviceRate !== null,
            'quarters' => $quarters,
            'annual' => [
                'output_vat_minor' => $annualOutput,
                'input_vat_minor' => $annualInput,
                'net_vat_minor' => $annualOutput - $annualInput,
                'output_display' => MoneyFormatter::display($annualOutput, $currency),
                'input_display' => MoneyFormatter::display($annualInput, $currency),
                'net_display' => MoneyFormatter::display($annualOutput - $annualInput, $currency),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailable(string $status, string $jurisdiction): array
    {
        return ['available' => false, 'status' => $status, 'jurisdiction' => $jurisdiction, 'quarters' => [], 'is_example' => false];
    }
}
