<?php

declare(strict_types=1);

namespace App\Support\Reconciliation;

use App\Models\Owner;
use App\Models\OwnerLedgerEntry;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Payments\Stripe\StripeClientInterface;
use App\Support\Billing\BillingEventOrganization;
use App\Support\Billing\PurchasedPlan;
use App\Support\Marketing\MarketingPricing;
use App\Support\Tenancy\TenantContext;
use Throwable;

/**
 * ⚠️⚠️ THE ANSWER TO "HOW WOULD ANYONE KNOW?" — asked and left unanswered fourteen passes running.
 *
 * EVERY defect this sequence produced was SILENT. Not one announced itself; each was found because somebody
 * happened to look, and the absence of a check decided three separate design arguments. These checks are
 * not invented — **each is derived from a defect that actually happened here**, and each names the pass
 * that found it. A check nobody can trace to a real defect is speculation.
 *
 * ⚠️⚠️ IT REPORTS; IT NEVER REPAIRS. A job that silently corrects drift removes the evidence that drift
 * happened — and every one of these was found BECAUSE A TRACE SURVIVED. Nothing here writes to a
 * subscription, an owner, a plan, a payment, a ledger row or a webhook row.
 *
 * ⚠️ TENANT SCOPES DROPPED, SOFT-DELETE KEPT — the precedent {@see BillingEventOrganization}
 * set. A reconciliation spans every tenant and has no request context, so `runAsSuperAdmin` IS the tenant
 * resolution rather than a bypass; but a soft-deleted row must not resurrect into a finding.
 */
class BillingReconciler
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly StripeClientInterface $stripe,
    ) {}

    /** @return list<CheckResult> */
    public function run(): array
    {
        return $this->tenant->runAsSuperAdmin(fn (): array => [
            $this->unprocessedWebhookEvents(),
            $this->settlementsWithoutAuditRows(),
            $this->refundsWithoutOwnerAdjustment(),
            $this->collidingPlanTiers(),
            $this->subscriptionPlanAgreesWithStripe(),
            $this->subscriptionStatusAgreesWithStripe(),
            $this->ownerPayoutFlagAgreesWithStripe(),
        ]);
    }

    // ══ LOCAL CHECKS — no network, so they keep working when Stripe does not ═══════════════════════

    /**
     * ⚠️ WEBHOOK-EVENT-LOSS + BILLING-EVENT-RESOLUTION. A delivery that failed leaves a `failed` row and is
     * re-claimable; one that could not be placed at all is now written `failed` with its reason instead of
     * vanishing. Either way nobody looks. This is that look.
     *
     * ⚠️ A REPAIR HERE WOULD BE RE-CLAIMING THE ROW — the one arguable case, and it is refused: re-running
     * a webhook is re-running whatever it does, and the claim already admits Stripe's own retry. Deciding
     * for the founder that a stuck delivery should be replayed is exactly the silent correction this exists
     * to prevent.
     */
    private function unprocessedWebhookEvents(): CheckResult
    {
        $rows = WebhookEvent::query()
            ->whereIn('status', [WebhookEvent::STATUS_FAILED, WebhookEvent::STATUS_RECEIVED])
            ->orderByDesc('id')->limit(self::SAMPLE)->get();

        $total = WebhookEvent::query()->count();

        return new CheckResult(
            key: 'webhook_events_unprocessed',
            title: 'Webhook deliveries that never reached `processed`',
            source: 'WEBHOOK-EVENT-LOSS · BILLING-EVENT-RESOLUTION',
            needsStripe: false,
            examined: $total,
            disagreements: $rows->map(fn (WebhookEvent $e): array => [
                'subject' => (string) $e->external_event_id,
                'detail' => $e->event_type.' is `'.$e->status.'`: '.($e->error ?? 'no reason recorded'),
            ])->all(),
        );
    }

    /**
     * ⚠️ POST-COMMIT-TRAIL. The settlement commits inside a transaction and the trail is written after it;
     * a post-commit throw left the money right and the record missing, permanently, because the retry hit
     * the handler's own status guard. The resume added there recovers it on a redelivery — this finds the
     * ones no redelivery ever came for.
     *
     * ⚠️ A REPAIR WOULD BE WRITING THE AUDIT ROW NOW, and it is refused: an audit row asserts who did what
     * and when, and a reconciliation knows none of those. A fabricated actor on an evidence table is worse
     * than an absence that can be investigated.
     */
    private function settlementsWithoutAuditRows(): CheckResult
    {
        $settled = Payment::query()->whereIn('status', ['succeeded', 'failed']);

        $offenders = (clone $settled)
            ->whereNotExists(function ($q): void {
                $q->selectRaw('1')->from('audit_events as a')
                    ->whereColumn('a.subject_id', 'payments.id')
                    ->whereIn('a.action', [
                        'payment.settled', 'payment.refund_settled', 'payment.recorded', 'payment.refunded',
                    ]);
            })
            ->orderByDesc('processed_at')->limit(self::SAMPLE)->get();

        return new CheckResult(
            key: 'settlement_without_audit',
            title: 'Settled payments and refunds with no settlement audit row',
            source: 'POST-COMMIT-TRAIL',
            needsStripe: false,
            examined: (clone $settled)->count(),
            disagreements: $offenders->map(fn (Payment $p): array => [
                'subject' => (string) $p->getKey(),
                'detail' => $p->type.' settled `'.$p->status.'` with no audit row',
            ])->all(),
        );
    }

    /**
     * ⚠️ POST-COMMIT-TRAIL — the ACCOUNTING half, and the reason that pass outranked its own brief. A
     * succeeded refund on an owner-attributed reservation posts the owner's debit through a synchronous
     * listener; when a listener ahead of it threw, the guest was refunded and the owner was never debited.
     * The ordering fix stops that shape recurring; this finds any that already happened.
     *
     * ⚠️ A REPAIR WOULD BE POSTING THE LEDGER ROW — refused outright. It is real money on an owner's
     * statement, `attributeRefund` weighs shares across owners, and a reconciliation guessing at that is
     * precisely the silent correction this project has spent months arguing against.
     */
    private function refundsWithoutOwnerAdjustment(): CheckResult
    {
        $refunds = Payment::query()->where('type', 'refund')->where('status', 'succeeded');

        $offenders = (clone $refunds)->orderByDesc('processed_at')->limit(self::SAMPLE)->get()
            ->filter(function (Payment $refund): bool {
                // Only reservations that are actually owner-attributed can owe an adjustment.
                $attributed = OwnerLedgerEntry::query()
                    ->where('reservation_id', $refund->reservation_id)->exists();

                if (! $attributed) {
                    return false;
                }

                return ! OwnerLedgerEntry::query()
                    ->where('idempotency_key', 'like', 'refund:'.$refund->getKey().':%')->exists();
            });

        return new CheckResult(
            key: 'refund_without_owner_adjustment',
            title: 'Succeeded refunds on owner-attributed stays with no owner-ledger adjustment',
            source: 'POST-COMMIT-TRAIL',
            needsStripe: false,
            examined: (clone $refunds)->count(),
            disagreements: $offenders->map(fn (Payment $p): array => [
                'subject' => (string) $p->getKey(),
                'detail' => 'refund settled but no `refund:'.$p->getKey().':*` ledger adjustment exists',
            ])->values()->all(),
        );
    }

    /**
     * ⚠️ PLAN-CATALOGUE-RESIDUALS. Two codes deriving one `(tierKey, interval)` made the pricing page render
     * the collider's price under the real tier's name — measured. A validator now refuses that at both
     * admin doors, but a validator cannot reach a seeder, a migration or a direct write, and that limit was
     * stated rather than hidden. This is the half that sees those.
     *
     * ⚠️ A REPAIR WOULD BE RENAMING A PLAN — refused. A plan code is an identity a founder chose.
     */
    private function collidingPlanTiers(): CheckResult
    {
        $plans = Plan::query()->get(['id', 'code', 'interval']);
        $seen = [];
        $clashes = [];

        foreach ($plans as $plan) {
            $key = MarketingPricing::tierKey((string) $plan->code).'|'.$plan->interval;

            if (isset($seen[$key])) {
                $clashes[] = [
                    'subject' => (string) $plan->code,
                    'detail' => 'resolves to the same tier and cycle as `'.$seen[$key].'` — one will not render on /pricing',
                ];

                continue;
            }

            $seen[$key] = (string) $plan->code;
        }

        return new CheckResult(
            key: 'plan_tier_collision',
            title: 'Plans resolving to the same (tier, interval)',
            source: 'PLAN-CATALOGUE-RESIDUALS',
            needsStripe: false,
            examined: $plans->count(),
            disagreements: $clashes,
        );
    }

    // ══ STRIPE CHECKS — three outcomes, and unreachable is UNCHECKED ═══════════════════════════════

    /**
     * ⚠️ BILLING-PURCHASED-PLAN. A purchase never wrote the subscription's plan: an org bought Essential,
     * Stripe billed €49, and `plan_id` still said Pro — so the app billed from Pro's captured terms.
     * Measured end to end. This is the standing version of that measurement.
     *
     * ⚠️ It reuses {@see PurchasedPlan} rather than re-deriving the price→plan map: a second copy is how
     * the two would drift, and the drift would be invisible because both would look right alone.
     *
     * ⚠️ A REPAIR WOULD BE WRITING `plan_id` FROM A RECONCILIATION — never. What a customer is on is
     * decided by what they bought and what the webhook recorded, not by a nightly job's opinion.
     */
    private function subscriptionPlanAgreesWithStripe(): CheckResult
    {
        return $this->overSubscriptions(
            key: 'subscription_plan_vs_stripe',
            title: 'Subscription plan against the Stripe Price actually being billed',
            source: 'BILLING-PURCHASED-PLAN',
            compare: function (Subscription $local, $live): ?string {
                $purchased = PurchasedPlan::fromSubscription($live);

                if ($purchased->problem !== null) {
                    return null;   // an unresolvable price is the purchased-plan pass's own refusal, not drift
                }

                $charged = $purchased->plan;

                if ($charged === null || (string) $charged->getKey() === (string) $local->plan_id) {
                    return null;
                }

                return 'Stripe is billing `'.$charged->code.'` but the subscription records plan ['.$local->plan_id.']';
            },
        );
    }

    /**
     * ⚠️ BILLING-WEBHOOK-ORDERING + BILLING-INVOICE-ORDERING + BILLING-EVENT-RESOLUTION. Out-of-order events
     * once wrote older state over newer; a late `invoice.paid` restored service to a CANCELLED account; and
     * both invoice handlers were inert in production, so a failed payment never marked an org `past_due` and
     * a customer whose card stopped working kept full access indefinitely. Every one of those shows up here
     * as a status this product holds that Stripe does not.
     *
     * ⚠️ A REPAIR WOULD BE FLIPPING `status` — refused. That is the access gate; a job silently restoring or
     * revoking an operator's whole back office is not something this should decide at 03:00.
     */
    private function subscriptionStatusAgreesWithStripe(): CheckResult
    {
        return $this->overSubscriptions(
            key: 'subscription_status_vs_stripe',
            title: 'Subscription status against Stripe',
            source: 'BILLING-WEBHOOK-ORDERING · BILLING-INVOICE-ORDERING · BILLING-EVENT-RESOLUTION',
            compare: function (Subscription $local, $live): ?string {
                $mapped = match ($live->status) {
                    'active', 'trialing' => 'active',
                    'past_due', 'unpaid' => 'past_due',
                    'canceled', 'incomplete_expired' => 'canceled',
                    default => null,
                };

                if ($mapped === null) {
                    return null;   // a status this product deliberately maps to "leave alone"
                }

                return $mapped !== (string) $local->status
                    ? 'Stripe says `'.$live->status.'` (→ '.$mapped.') but this product holds `'.$local->status.'`'
                    : null;
            },
        );
    }

    /**
     * ⚠️ CONNECT-ACCOUNT-ORDERING. A late `account.updated` re-enabled payouts Stripe had disabled — the
     * pass that established the asymmetry between enabling and disabling. A flag this product holds `true`
     * while Stripe holds `false` means the payout rail believes it may send money it may not.
     *
     * ⚠️ A REPAIR WOULD BE FLIPPING THE FLAG — refused for the same reason that pass threw rather than
     * degraded on an unverifiable enable: this one is about real money leaving.
     */
    private function ownerPayoutFlagAgreesWithStripe(): CheckResult
    {
        $owners = Owner::query()
            ->whereNotNull('stripe_connect_account_id')
            ->orderBy('id')->limit(self::SAMPLE)->get();

        $disagreements = [];
        $unchecked = [];

        foreach ($owners as $owner) {
            try {
                $account = $this->stripe->retrieveConnectAccount((string) $owner->stripe_connect_account_id);
            } catch (Throwable $e) {
                // ⚠️ UNCHECKED, NOT CLEAR.
                $unchecked[] = [
                    'subject' => (string) $owner->getKey(),
                    'detail' => 'could not read the Connect account back: '.$e->getMessage(),
                ];

                continue;
            }

            if ($account->payoutsEnabled !== (bool) $owner->stripe_connect_payouts_enabled) {
                $disagreements[] = [
                    'subject' => (string) $owner->getKey(),
                    'detail' => 'Stripe payouts_enabled='.($account->payoutsEnabled ? 'true' : 'false')
                        .' but this product holds '.($owner->stripe_connect_payouts_enabled ? 'true' : 'false'),
                ];
            }
        }

        return new CheckResult(
            key: 'owner_payouts_flag_vs_stripe',
            title: 'Owner payout capability against the Stripe Connect account',
            source: 'CONNECT-ACCOUNT-ORDERING',
            needsStripe: true,
            examined: $owners->count(),
            disagreements: $disagreements,
            unchecked: $unchecked,
        );
    }

    // ══ shared plumbing ═══════════════════════════════════════════════════════════════════════════

    /** How many objects one check reads at most — a bound on both runtime and Stripe rate limits. */
    private const SAMPLE = 200;

    /**
     * Walk the subscriptions that have a Stripe id, reading each back ONCE.
     *
     * ⚠️ THE FAKE FAILS CLOSED and so does this: `retrieveSubscription()` returns null for an object Stripe
     * does not know, and null becomes UNCHECKED — never "agrees". An unregistered object reading as
     * agreement would be the confident-zero shape one layer down.
     */
    private function overSubscriptions(string $key, string $title, string $source, callable $compare): CheckResult
    {
        $subscriptions = Subscription::query()
            ->whereNotNull('stripe_subscription_id')
            ->orderBy('id')->limit(self::SAMPLE)->get();

        $disagreements = [];
        $unchecked = [];

        foreach ($subscriptions as $subscription) {
            try {
                $live = $this->stripe->retrieveSubscription((string) $subscription->stripe_subscription_id);
            } catch (Throwable $e) {
                $unchecked[] = [
                    'subject' => (string) $subscription->getKey(),
                    'detail' => 'Stripe could not be reached: '.$e->getMessage(),
                ];

                continue;
            }

            if ($live === null) {
                $unchecked[] = [
                    'subject' => (string) $subscription->getKey(),
                    'detail' => 'Stripe does not know subscription ['.$subscription->stripe_subscription_id.']',
                ];

                continue;
            }

            $problem = $compare($subscription, $live);

            if (is_string($problem)) {
                $disagreements[] = ['subject' => (string) $subscription->getKey(), 'detail' => $problem];
            }
        }

        return new CheckResult(
            key: $key, title: $title, source: $source, needsStripe: true,
            examined: $subscriptions->count(),
            disagreements: $disagreements,
            unchecked: $unchecked,
        );
    }
}
