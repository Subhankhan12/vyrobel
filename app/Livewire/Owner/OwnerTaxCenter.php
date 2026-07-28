<?php

declare(strict_types=1);

namespace App\Livewire\Owner;

use App\Http\Controllers\Owner\OwnerTaxController;
use App\Livewire\Owner\Concerns\FormatsOwnerData;
use App\Models\Owner;
use App\Services\OwnerTaxService;
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
 * WITHHOLDING is NOT modelled (Vyrobel withholds nothing from payouts — the only deductions are the real
 * commission/fees). The wireframe's residency-driven "15%/20% withheld" is fabricated, so the Tax-center renders
 * an HONEST "no withholding" card (the truthful universal state) instead of a fake rate (#39). The OTA brand is
 * likewise never rendered.
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
        ]);
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
