<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Channel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Unit;
use App\Services\BillingService;
use App\Support\Billing\SubscriptionPricing;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Operator BILLING (exact-match #85) — the operator's OWN subscription (paying Vyrobel). Tenant-scoped;
 * `can:billing.manage`. Two faces: the active-subscription MANAGEMENT view (plan · per-unit figure ·
 * what-you-pay · billing details · pause/cancel) for an org with a plan, and the tier catalog / paywall for
 * a lapsed / no-plan org.
 *
 * HONESTY: the STATE is real (status/trial/period, and the REAL read-only pause/cancel window). The
 * per-unit FIGURE is real — the org's REAL active-unit count × the PLACEHOLDER per-unit price × VAT
 * ({@see SubscriptionPricing}), reconciling (#17), flagged placeholder. The CHARGE, the stored CARD, and
 * PAID invoices are account-gated — NEVER fabricated (no fake "····4242" / "Paid €66.42"); the real trial
 * shows, future invoices are honestly gated.
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

        // The read-only lifecycle state (the REAL pause/cancel windows this page writes).
        $pausedUntil = $org->paused_until !== null ? CarbonImmutable::parse($org->paused_until) : null;
        $readonlyFrom = $org->readonly_from !== null ? CarbonImmutable::parse($org->readonly_from) : null;
        $isPaused = $pausedUntil !== null && $pausedUntil->isFuture();
        $isCanceling = $readonlyFrom !== null;

        $currency = (string) config('billing.default_currency', 'EUR');
        $pricing = SubscriptionPricing::current('monthly');
        $yearlySaving = SubscriptionPricing::yearlySavingMinor();

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
                'has_plan' => $current !== null && $current->plan !== null,
            ],
            // ── The active-subscription management view (exact-match) ───────────────────────────────
            'plan' => ($current === null || $current->plan === null) ? null : [
                'name' => (string) $current->plan->name,
                // ⚠️ The org's REAL TIER code (wireframe-parity WAVE 2 · S3). "Switch to yearly" used to link
                // to a hardcoded `plan=pro`, so an ESSENTIAL org clicking it landed on the PRO yearly checkout
                // — a silent upgrade to a tier they never chose. The interval is the only thing that switch
                // changes; the tier must come from the subscription.
                //
                // ⚠️ A `plans.code` is `{tier}_{interval}` ("essential_monthly"), NOT the tier code the
                // checkout takes. Passing it raw would fail OperatorBillingCheckout::resolveTier()'s
                // whitelist and fall back to… 'pro' — reproducing the very bug. Strip the interval suffix.
                'code' => Str::beforeLast((string) $current->plan->code, '_'),
                'interval' => (string) $current->plan->interval,
                'subscribed_on' => CarbonImmutable::parse($current->created_at)->format('j M Y'),
                'period_end' => $current->current_period_end !== null ? CarbonImmutable::parse($current->current_period_end)->format('j M Y') : null,
            ],
            'pricing' => [
                'units' => $pricing['units'],
                'per_unit' => $this->money($pricing['per_unit_minor'], $currency),
                'net' => $this->money($pricing['net_minor'], $currency),
                'vat' => $this->money($pricing['vat_minor'], $currency),
                'gross' => $this->money($pricing['gross_minor'], $currency),
                'vat_pct' => $pricing['vat_pct'],
                'yearly_saving' => $this->money($yearlySaving, $currency),
            ],
            'counts' => [
                'units' => $pricing['units'],
                'team' => Membership::query()->where('status', 'active')->count(),
                'channels' => Channel::query()->where('status', 'connected')->count(),
            ],
            'billingDetails' => [
                'company' => $org->billing_company !== null && $org->billing_company !== '' ? (string) $org->billing_company : null,
                'vat_id' => $org->billing_vat_id !== null && $org->billing_vat_id !== '' ? (string) $org->billing_vat_id : null,
                'email' => $org->billing_email !== null && $org->billing_email !== '' ? (string) $org->billing_email : null,
                'country' => $org->country !== null && $org->country !== '' ? (string) $org->country : null,
            ],
            'lifecycle' => [
                'is_paused' => $isPaused,
                'is_canceling' => $isCanceling,
                'paused_until' => $pausedUntil?->format('j M Y'),
                'readonly_from' => $readonlyFrom?->format('j M Y'),
            ],
            // The honest invoice history — the real trial only; paid invoices are account-gated (no fabrication).
            'invoices' => $this->invoices($org, $currency),
            'tiers' => $this->tierCards($billing),
        ]);
    }

    /**
     * The REAL, honest invoice history. Without live Stripe billing no charge is ever taken, so there is NO
     * paid invoice to show (fabricating one would be dishonest). The free trial IS real → shown as a €0.00
     * line; future invoices are honestly gated.
     *
     * @return list<array{ref: string, label: string, period: ?string, status: string, amount: string, gated: bool}>
     */
    private function invoices(Organization $org, string $currency): array
    {
        $rows = [];

        $sub = $org->currentSubscription();
        if ($sub !== null) {
            $trialDays = (int) config('billing.trial_days', 14);
            $start = CarbonImmutable::parse($sub->created_at);
            $rows[] = [
                'ref' => 'Trial',
                'label' => $trialDays.'-day trial',
                'period' => $start->format('j M').' – '.$start->addDays($trialDays)->format('j M Y'),
                'status' => 'Free',
                'amount' => $this->money(0, $currency),
                'gated' => false,
            ];
        }

        return $rows;
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
