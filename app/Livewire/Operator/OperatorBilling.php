<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Organization;
use App\Services\BillingService;
use App\Support\Billing\SubscriptionStatus;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator BILLING / Paywall (Phase 7b · SG2) — the org's real subscription status + the tier catalog, from
 * which the founder subscribes. Tenant-scoped (the org is resolved from the active tenant context); `/app`
 * behind #26; the route carries `can:billing.manage` (the founder / Property Owner — NOT Front Desk, #30).
 *
 * Every figure is REAL: the status label + trial days come from the derived {@see SubscriptionStatus};
 * the tier prices are the CONFIGURABLE PLACEHOLDERS from config/billing.php (marked as such — Subhan sets the
 * real ones), never fabricated market pricing. No charge is taken here — subscribing continues to the Checkout,
 * whose pay action is the account-gated Stripe seam.
 */
#[Layout('components.layouts.operator')]
class OperatorBilling extends Component
{
    use FormatsOperatorData;

    public function render(BillingService $billing): View
    {
        $org = Organization::query()->findOrFail(app(TenantContext::class)->id());
        $status = $org->billingStatus();
        $current = $org->currentSubscription();

        return view('livewire.operator.billing', [
            'viewer' => $this->viewer(),
            'status' => [
                'label' => $status->label(),
                'on_trial' => $status->onTrial(),
                'trial_expired' => $status->trialExpired(),
                'active' => $status->active(),
                'lapsed' => $status->isLapsed(),
                'days' => $status->trialDaysRemaining(),
                'plan_name' => $current?->plan?->name,
            ],
            'tiers' => $this->tierCards($billing),
        ]);
    }

    /**
     * @return list<array{code: string, name: string, blurb: string, monthly: string, yearly: string, yearly_monthly_equiv: string, features: list<array{label: string, on: bool, note: ?string}>}>
     */
    private function tierCards(BillingService $billing): array
    {
        return array_map(function (array $tier): array {
            $currency = $tier['currency'];
            $features = array_map(fn (array $f): array => [
                'label' => $f['label'],
                'on' => $f['enabled'],
                'note' => $f['limit'] !== null ? 'up to '.$f['limit'] : null,
            ], $tier['features']);

            return [
                'code' => $tier['code'],
                'name' => $tier['name'],
                'blurb' => $tier['blurb'],
                'monthly' => $this->money($tier['monthly_minor'], $currency),
                'yearly' => $this->money($tier['yearly_minor'], $currency),
                'yearly_monthly_equiv' => $this->money((int) round($tier['yearly_minor'] / 12), $currency),
                'features' => $features,
            ];
        }, $billing->catalog());
    }
}
