<?php

declare(strict_types=1);

namespace App\Livewire\Operator;

use App\Livewire\Operator\Concerns\FormatsOperatorData;
use App\Models\Channel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\Unit;
use App\Services\BillingService;
use App\Services\OperatorConnectService;
use App\Support\Billing\SubscriptionPricing;
use App\Support\I18n\LocalDate;
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

    /**
     * The card-acceptance panel: what Stripe says about this organisation's connected account, right now.
     *
     * ⚠️ `charges_enabled` IS THE FACT. `details_submitted` is NOT readiness — an operator can finish the
     * form and still be blocked pending review, and collapsing the two into "done" is how someone concludes
     * the product is broken when a card is refused.
     *
     * ⚠️ NOTHING HERE IS CACHED, and no account link is minted on render: links EXPIRE and are SINGLE-USE,
     * so one is minted only when the operator actually acts (the POST route), never held for a page that
     * might be left open.
     *
     * @return array<string, mixed>
     */
    private function connectPanel(Organization $org): array
    {
        $readiness = app(OperatorConnectService::class)->readiness($org);

        return [
            'connected' => $readiness['connected'],
            'details_submitted' => $readiness['details_submitted'],
            'charges_enabled' => $readiness['charges_enabled'],
            'payouts_enabled' => $readiness['payouts_enabled'],
            'ready' => $readiness['ready'],
            // ⚠️ THE COUNTRY IS SHOWN BEFORE THE ACCOUNT EXISTS, because a Connect account's country CANNOT
            //    be changed afterwards. An irreversible choice needs its warning at the point of choosing —
            //    not in a document, and not after a customer discovers it while expanding.
            'country' => $org->country,
            'started' => $readiness['connected'],
        ];
    }

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

        // ⚠️ BILLING PASS 3 — the billing summary is now DERIVED FROM THE ORG'S PLAN SHAPE, reading pass 1's
        // engine ({@see Plan::chargeFor()}), never recomputed here. Before this, the page rendered a per-unit
        // view unconditionally — so an org on a FLAT tier was told "8 units × €9" and "adding a unit +€9/mo ·
        // prorated", none of which is true of a flat plan. That was the live §N.8d.
        //
        // ⚠️ DISPLAY MUST EQUAL CHARGE. Every figure below comes from the same expression the CHARGE uses, so
        // the page cannot quote one number while Stripe is sent another (pass 1 made charge read one source;
        // this makes display read the same one). A local rate × units here would reopen exactly that trap.
        // ⚠️ NOT `$billing` — that name is already the injected BillingService in this method's signature, and
        // shadowing it made a later tierCards($billing) receive an array (a 500). The repo's Livewire trap #2
        // in a plain-PHP guise: a new local silently taking an existing name.
        // ⚠️ Priced from the SUBSCRIPTION's captured terms, not the live catalogue row — so a plan edited in
        // the admin panel cannot change what an existing subscriber is shown (the founder's ruling).
        $billingSummary = $this->billingSummary($current);

        return view('livewire.operator.billing', [
            'viewer' => $this->viewer(),
            // ⚠️⚠️ CARD ACCEPTANCE — read LIVE from Stripe on every render, never from a stored flag.
            //    Stripe can suspend an account or ask for more information between one booking and the next,
            //    so a local "onboarded" boolean is an assertion about a moment that has passed.
            'connect' => $this->connectPanel($org),
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
            // The shape-keyed summary (§N.16.3): the view branches on `is_per_unit`, so the per-unit lines are
            // not something a flat org "happens not to render" — they are unreachable for it.
            'billing' => $billingSummary,
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
                // The interval as people read it, keyed by the plan's CODE (never compared as text).
                'interval_label' => match ((string) $current->plan->interval) {
                    'monthly' => __('php/livewire/operator/operator_billing.interval_monthly'),
                    'yearly' => __('php/livewire/operator/operator_billing.interval_yearly'),
                    default => ucfirst((string) $current->plan->interval),
                },
                'subscribed_on' => CarbonImmutable::parse($current->created_at)->translatedFormat(LocalDate::pattern('j M Y')),
                'period_end' => $current->current_period_end !== null ? CarbonImmutable::parse($current->current_period_end)->translatedFormat(LocalDate::pattern('j M Y')) : null,
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
                // ⚠️ The plan's REAL team limit (wireframe-parity WAVE 2 · #10). The page printed
                // "· unlimited" unconditionally, so an ESSENTIAL org — whose plan sets a limit of 5 — was
                // told it had no limit at all. null genuinely means unlimited (Pro).
                //
                // ⚠️ DISPLAY ONLY. Nothing enforces this limit today, and whether it SHOULD be enforced is
                // part of the founder's open billing decision — deliberately not built here.
                'team_limit' => $this->teamLimit($current),
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
                'paused_until' => $pausedUntil?->translatedFormat(LocalDate::pattern('j M Y')),
                'readonly_from' => $readonlyFrom?->translatedFormat(LocalDate::pattern('j M Y')),
            ],
            // The honest invoice history — the real trial only; paid invoices are account-gated (no fabrication).
            // ⚠️ THE PLATFORM'S BILLING CURRENCY, NOT THE TENANT'S. What Vyrobel charges an operator is
            // `config('billing.default_currency')` — a deliberate, separate domain from the currency that
            // operator charges its own guests. The retention card's "Pause instead · €0" was the one string on
            // this page that hardcoded it instead of reading it, so it would have gone stale silently had the
            // platform currency ever changed.
            'pauseFree' => $this->money(0, $currency),
            'invoices' => $this->invoices($org, $currency),
            'tiers' => $this->tierCards($billing),
        ]);
    }

    /**
     * The org's plan limit on team members — null when the plan genuinely sets none (Pro), and null when
     * there is no plan to read. `plan_features` is authoritative (a platform admin edits it), matching
     * {@see BillingService::catalog()}.
     */
    private function teamLimit(?Subscription $current): ?int
    {
        if ($current === null) {
            return null;
        }

        $feature = PlanFeature::query()
            ->where('plan_id', $current->plan_id)
            ->where('key', 'team')
            ->first();

        return $feature?->limit;
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
                'label' => __('php/livewire/operator/operator_billing.trial_label', ['days' => $trialDays]),
                'period' => $start->translatedFormat(LocalDate::pattern('j M')).' – '.$start->addDays($trialDays)->translatedFormat(LocalDate::pattern('j M Y')),
                'status' => __('php/livewire/operator/operator_billing.invoice_free'),
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
                'note' => $f['limit'] !== null ? __('php/livewire/operator/operator_billing.feature_up_to', ['limit' => $f['limit']]) : null,
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

    /**
     * The billing summary for the org's CURRENT plan, keyed by the plan's SHAPE (BILLING PASS 3).
     *
     * ⚠️ It reads {@see Plan::chargeFor()} — the engine the CHARGE uses — so the figure on screen is the figure
     * that would be billed. It deliberately performs no arithmetic of its own: a second calculation is a second
     * source, and two sources for one number is how a page ends up contradicting the invoice (§N.8d).
     *
     * A null plan (no subscription yet) yields null — the page's trial/paywall states own that case.
     *
     * @return array{is_per_unit: bool, units: int, gross: string, net: string, vat: string, vat_pct: int, unit_rate: string}|null
     */
    private function billingSummary(?Subscription $subscription): ?array
    {
        $plan = $subscription?->plan;

        if ($subscription === null || $plan === null) {
            return null;
        }

        // The CAPTURED terms — what this customer agreed to. `vat_pct`/`unit_rate` below come from the same
        // frozen set, so every figure on the card belongs to one agreement rather than a mix of old and new.
        $terms = $subscription->terms();
        $charge = $terms->charge();
        $currency = $terms->currency;

        return [
            'is_per_unit' => $terms->isPerUnit(),
            'units' => $charge['units'],
            'gross' => $this->money($charge['gross_minor'], $currency),
            'net' => $this->money($charge['net_minor'], $currency),
            'vat' => $this->money($charge['vat_minor'], $currency),
            'vat_pct' => $terms->vatPct,
            // ⚠️ BILLING PASS 2b — WHETHER THE APP KNOWS THE TAX AT ALL, keyed off the plan (§N.16.3), never
            // typed into the view. On a flat tier it does: €49 is VAT-inclusive and final. On a per-unit tier it
            // does NOT: Stripe Tax computes it from the customer's country and status at checkout, and an EU
            // business under reverse charge pays €0 — so any fixed percentage the page printed would be false
            // for them. The view therefore shows the subtotal it can stand behind and defers the rest.
            'tax_known' => (bool) $charge['tax_known'],
            // ⚠️ ONE keyed phrase, chosen by the shape, never typed into the view (§N.16.3). Three places on the
            // page state the tax; if each wrote its own wording they would drift, and one of them would end up
            // asserting something the shape does not support — which is precisely how the 23% survived pass 3.
            //
            // ⚠️ A FLAT TIER SAYS NOTHING, deliberately. €49 is VAT-inclusive and final, but the app does not
            // know the RATE baked into it (`vat_pct` is null on a flat plan), so "incl. 0% VAT" would be a fresh
            // falsehood. Silence is the honest rendering, and it is what pass 3 shipped.
            'tax_note' => match (true) {
                ! $charge['tax_known'] => __('php/livewire/operator/operator_billing.tax_at_checkout'),
                $terms->vatPct > 0 => __('php/livewire/operator/operator_billing.tax_incl_vat', ['pct' => $terms->vatPct]),
                default => '',
            },
            'unit_rate' => $this->money($terms->unitRateMinor, $currency),
        ];
    }
}
