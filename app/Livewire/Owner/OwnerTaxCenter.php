<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerTaxController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Models\OwnerStatement;
use App\Models\TaxRate;
use App\Services\OwnerTaxService;
use App\Services\TaxRateService;
use App\Services\WithholdingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Owner Tax Center (Phase 2 · Sub-gate 4) — the owner's tax profile, the payouts HELD until it's complete, and
 * real per-year summaries. READ side; the write goes POST → {@see OwnerTaxController}
 * → {@see OwnerTaxService} (self-only). Every figure is the owner's own, aggregated from their real statements.
 *
 * SELF-ONLY: the owner is resolved by `user_id` (never a request id); a non-linked user resolves to nothing and
 * gets the fail-closed empty state. The held-payout amount is REAL — it comes from the owner's actual pending
 * payouts, which OwnerPayoutService::pay() genuinely refuses to disburse until a tax ID is on file.
 *
 * WITHHOLDING is now REAL (Wave D · tax engine): the card shows the owner's actual withheld total (Σ of the
 * `withholding_minor` on their committed statements), computed at finalize from a FOUNDER-CONFIGURED rate for their
 * residency ({@see WithholdingService}). Until a rate is configured it is a genuine "None" (nothing
 * withheld) — never a fabricated residency rate (#39). The OTA brand is still never rendered.
 */
#[Layout('components.layouts.owner')]
class OwnerTaxCenter extends Component
{
    use FormatsOwnerData;

    public function render(): View
    {
        $tax = app(OwnerTaxService::class);
        $user = auth()->user();

        try {
            $owner = $user === null ? throw new ModelNotFoundException : $tax->owner($user);
        } catch (ModelNotFoundException) {
            return view('livewire.owner.tax-center', [
                'viewer' => $this->viewer(),
                'linked' => false,
                'complete' => false,
                'profile' => [],
                'held' => [],
                'summaries' => [],
            ]);
        }

        $held = [];
        foreach ($tax->heldTotalMinor($owner) as $currency => $minor) {
            $held[] = ['display' => $this->money($minor, $currency)];
        }

        $currentYear = (int) Carbon::today()->format('Y');
        $summaries = [];
        foreach ($tax->availableYears($owner) as $year) {
            $summary = $tax->annualSummary($owner, $year);
            if ($summary === null) {
                continue;
            }
            $summaries[] = [
                'year' => $year,
                'in_progress' => $year === $currentYear,
                'gross_display' => $this->money((int) $summary['gross_minor'], (string) $summary['currency']),
                'net_display' => $this->money((int) $summary['net_minor'], (string) $summary['currency']),
            ];
        }

        return view('livewire.owner.tax-center', [
            'viewer' => $this->viewer(),
            'linked' => true,
            'complete' => $owner->hasTaxDetails(),
            'profile' => $this->profile($owner),
            'held' => $held,
            'summaries' => $summaries,
            // Wave D · tax engine — the REAL withheld total (Σ statement withholding), or an honest "None" when no
            // withholding rate is configured for the owner's residency. Never a fabricated rate (#39).
            'withholding' => $this->withholdingSummary($owner),
        ]);
    }

    /**
     * The owner's REAL withholding — Σ of the `withholding_minor` on their committed statements (per currency), and
     * whether a withholding rate is configured for their residency. €0/None until the founder configures a rate.
     *
     * @return array{has: bool, configured: bool, display: string|null}
     */
    private function withholdingSummary(Owner $owner): array
    {
        $byCurrency = [];
        foreach (OwnerStatement::query()->where('owner_id', $owner->getKey())->whereIn('status', ['finalized', 'paid'])->get() as $statement) {
            $minor = (int) $statement->withholding_minor;   // negative (a deduction)
            if ($minor === 0) {
                continue;
            }
            $currency = (string) $statement->source_currency;
            $byCurrency[$currency] = ($byCurrency[$currency] ?? 0) + abs($minor);
        }

        $configured = app(TaxRateService::class)
            ->rateFor((string) ($owner->tax_country ?? ''), TaxRate::TYPE_WITHHOLDING) !== null;

        return [
            'has' => $byCurrency !== [],
            'configured' => $configured,
            'display' => $byCurrency === [] ? null : implode(' · ', array_map(fn (string $c, int $m): string => $this->money($m, $c), array_keys($byCurrency), $byCurrency)),
        ];
    }

    /**
     * The owner's OWN tax profile, pre-filled into the self-edit form. Showing the owner their own (decrypted)
     * tax id / VAT number is correct — it is their own data on a self-only page, exactly like the email field
     * on the profile form.
     *
     * @return array<string, string|bool>
     */
    private function profile(Owner $owner): array
    {
        return [
            'legal_name' => (string) ($owner->legal_name ?? $owner->name),
            'type' => (string) ($owner->type ?? 'individual'),
            'tax_country' => (string) ($owner->tax_country ?? ''),
            'tax_id' => (string) ($owner->tax_id ?? ''),
            'vat_number' => (string) ($owner->vat_number ?? ''),
            'vat_registered' => (bool) $owner->vat_registered,
        ];
    }
}
