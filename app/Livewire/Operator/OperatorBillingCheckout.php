<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Http\Controllers\Operator\BillingActionController;
use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Organization;
use App\Services\BillingService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator CHECKOUT (Phase 7b · SG2) — the chosen tier + billing period, the company/VAT details, and the
 * price summary. Tenant-scoped; `can:billing.manage`.
 *
 * ⚠️ THE PAY ACTION IS THE ACCOUNT-GATED SEAM. This screen captures everything EXCEPT the card/charge: the
 * summary is the CONFIGURABLE PLACEHOLDER price; the "Subscribe" button posts to the deferred Stripe-Billing
 * seam ({@see BillingActionController::subscribe}) which does NOT charge, does
 * NOT store card data, and does NOT flip the org to active. Live billing is honestly "coming soon".
 */
#[Layout('components.layouts.operator')]
class OperatorBillingCheckout extends Component
{
    use FormatsOperatorData;

    public function render(BillingService $billing): View
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());

        $tierCode = $this->resolveTier($billing);
        $interval = request()->query('interval') === 'yearly' ? 'yearly' : 'monthly';

        $tier = collect($billing->catalog())->firstWhere('code', $tierCode);
        $currency = (string) ($tier['currency'] ?? 'EUR');
        $priceMinor = $interval === 'yearly' ? (int) $tier['yearly_minor'] : (int) $tier['monthly_minor'];

        return view('livewire.operator.billing-checkout', [
            'viewer' => $this->viewer(),
            'tier' => [
                'code' => (string) $tier['code'],
                'name' => (string) $tier['name'],
                'blurb' => (string) $tier['blurb'],
            ],
            'interval' => $interval,
            'price' => $this->money($priceMinor, $currency),
            'period_label' => $interval === 'yearly' ? 'per year' : 'per month',
            'company' => (string) ($org->billing_company ?? $org->name),
            'vat_id' => (string) ($org->billing_vat_id ?? ''),
            'billing_email' => (string) ($org->billing_email ?? ''),
        ]);
    }

    private function resolveTier(BillingService $billing): string
    {
        $codes = array_column($billing->catalog(), 'code');
        $requested = (string) request()->query('plan', 'pro');

        return in_array($requested, $codes, true) ? $requested : 'pro';
    }
}
