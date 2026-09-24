<?php

declare(strict_types=1);

namespace App\Support\Platform\Revenue;

use App\Models\Organization;
use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Support\Platform\PlatformQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * ⚠️⚠️ ADMIN 01 · PHASE 4 — REVENUE, TRACED TO THE CHARGE.
 *
 * 🔬 WHERE THE MONEY ACTUALLY GOES: a subscription is charged by Stripe; Stripe tells us with `invoice.paid`
 * (and `invoice.payment_failed`); `StripeWebhookController` stores each VERIFIED event in `webhook_events` with its
 * full payload and the organisation it belongs to. ⚠️ `BillingWebhookService::applyInvoice()` records NO AMOUNT
 * anywhere — it only moves the subscription's status and period. So the ONLY record of what a customer was charged
 * is the paid invoice itself, and that is the only thing this class sums.
 *
 * ⚠️⚠️ NEVER FROM THE PRICING TABLE: no `price_minor`, no `per_unit_minor` multiplied by anything, no `vat_pct`
 * applied — a plan's list price is what we ASK, a paid invoice is what was CHARGED.
 * ⚠️ Per currency, never summed across currencies. Internal organisations excluded unless asked for.
 * ⚠️ With no paid invoice from a customer, the answer is "no live billing yet" — never a confident zero.
 *
 * Must run inside {@see PlatformQuery::read()}: it reads every tenant's rows.
 *
 * @phpstan-type Invoice array{organization_id: string, subscription: ?string, currency: string, amount_minor: int, monthly_minor: int, at: CarbonImmutable}
 */
final class PlatformRevenue
{
    /** Seconds in an average month (365.25 × 86 400 ÷ 12) — to put a yearly or odd-period charge on a monthly footing. */
    private const MONTH_SECONDS = 2629800;

    /**
     * @return array<string, mixed>
     */
    public function compute(bool $includeInternal = false, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now('UTC');
        $orgIds = $this->orgIds($includeInternal);

        $paid = collect($this->invoices('invoice.paid', $orgIds));
        $failed = collect($this->invoices('invoice.payment_failed', $orgIds));

        $subscriptions = Subscription::query()->whereIn('organization_id', $orgIds)->get(['id', 'organization_id', 'status', 'stripe_subscription_id', 'trial_ends_at', 'updated_at']);

        $base = [
            'include_internal' => $includeInternal,
            'live' => $paid->isNotEmpty(),
            'paid_invoices' => $paid->count(),
            'live_billed_subscriptions' => $subscriptions->whereNotNull('stripe_subscription_id')->count(),
            'trialing_unbilled' => $subscriptions->where('status', 'trialing')->whereNull('stripe_subscription_id')->count(),
        ];

        if ($paid->isEmpty()) {
            return $base + ['failed_payments_30d' => $failed->where('at', '>=', $now->subDays(30))->count()];
        }

        return $base + [
            'mrr' => $this->mrr($paid->all(), $subscriptions),
            'movements' => $this->movements($paid->all(), $subscriptions, $now),
            'trial_to_paid' => $this->trialToPaid($paid->all(), $subscriptions, $now),
            'failed_payments_30d' => $failed->where('at', '>=', $now->subDays(30))->count(),
            'past_due_now' => $subscriptions->where('status', 'past_due')->count(),
        ];
    }

    /**
     * MRR per currency: every LIVE-billed subscription that is active or past due, valued at its MOST RECENT paid
     * invoice, put on a monthly footing by that invoice's own billing period. ARR = MRR × 12.
     *
     * @param  list<Invoice>  $paid
     * @param  Collection<int, Subscription>  $subscriptions
     * @return array<string, array{mrr_minor: int, arr_minor: int, subscriptions: int}>
     */
    private function mrr(array $paid, Collection $subscriptions): array
    {
        $paid = collect($paid);
        $out = [];
        foreach ($subscriptions->whereIn('status', ['active', 'past_due'])->whereNotNull('stripe_subscription_id') as $sub) {
            $latest = $paid->where('subscription', $sub->stripe_subscription_id)->sortByDesc('at')->first();
            if ($latest === null) {
                continue;   // live, but not charged yet — not valued, never guessed
            }
            $c = $latest['currency'];
            $out[$c] ??= ['mrr_minor' => 0, 'arr_minor' => 0, 'subscriptions' => 0];
            $out[$c]['mrr_minor'] += $latest['monthly_minor'];
            $out[$c]['subscriptions']++;
        }
        foreach ($out as $c => $row) {
            $out[$c]['arr_minor'] = $row['mrr_minor'] * 12;
        }
        ksort($out);

        return $out;
    }

    /**
     * This month against last month, per organisation, from what was CHARGED in each (monthly footing):
     *   new — nothing last month, charged this month · expansion — charged more · contraction — charged less but
     *   still charged · churned — charged last month, subscription now cancelled.
     *
     * @param  list<Invoice>  $paid
     * @param  Collection<int, Subscription>  $subscriptions
     * @return array<string, array<string, int>>
     */
    private function movements(array $paid, Collection $subscriptions, CarbonImmutable $now): array
    {
        $paid = collect($paid);
        $thisStart = $now->startOfMonth();
        $lastStart = $thisStart->subMonth();
        $sum = fn (CarbonImmutable $from, CarbonImmutable $to) => $paid->filter(fn ($i) => $i['at'] >= $from && $i['at'] < $to)
            ->groupBy(fn ($i) => $i['organization_id'].'|'.$i['currency'])->map(fn ($g) => (int) $g->sum('monthly_minor'));

        $last = $sum($lastStart, $thisStart);
        $this_ = $sum($thisStart, $thisStart->addMonth());
        $cancelled = $subscriptions->where('status', 'canceled')->pluck('organization_id')->flip();

        $out = [];
        foreach ($last->keys()->merge($this_->keys())->unique() as $key) {
            [$org, $currency] = explode('|', (string) $key, 2);
            $was = (int) ($last[$key] ?? 0);
            $is = (int) ($this_[$key] ?? 0);
            $out[$currency] ??= ['new' => 0, 'expansion' => 0, 'contraction' => 0, 'churned' => 0];

            if ($was === 0 && $is > 0) {
                $out[$currency]['new'] += $is;
            } elseif ($was > 0 && $is > $was) {
                $out[$currency]['expansion'] += $is - $was;
            } elseif ($was > 0 && $is > 0 && $is < $was) {
                $out[$currency]['contraction'] += $was - $is;
            } elseif ($was > 0 && $is === 0 && $cancelled->has($org)) {
                $out[$currency]['churned'] += $was;
            }
        }
        ksort($out);

        return $out;
    }

    /**
     * Of the customer trials that have ENDED, how many organisations have ever been charged.
     *
     * @param  list<Invoice>  $paid
     * @param  Collection<int, Subscription>  $subscriptions
     * @return array{ended: int, converted: int}
     */
    private function trialToPaid(array $paid, Collection $subscriptions, CarbonImmutable $now): array
    {
        $paid = collect($paid);
        $endedOrgs = $subscriptions->filter(fn (Subscription $s) => $s->trial_ends_at !== null && $s->trial_ends_at->lessThanOrEqualTo($now))
            ->pluck('organization_id')->unique();
        $charged = $paid->pluck('organization_id')->unique()->flip();

        return ['ended' => $endedOrgs->count(), 'converted' => $endedOrgs->filter(fn ($id) => $charged->has($id))->count()];
    }

    /**
     * ⚠️ A CHARGE ON A MONTHLY FOOTING, BY ITS BILLING INTERVAL — not by counting seconds. 🔬 Dividing by an average
     * month made a 30-day monthly invoice count as 101.46% of itself (and February as ~108.7%): a monthly plan's MRR
     * would wobble with the calendar. So: the interval Stripe states on the invoice line; failing that, the period
     * bucketed to a week, month, quarter, half-year or year; only an odd period falls back to its exact length.
     *
     * @param  array<string, mixed>  $invoice
     */
    private static function monthly(int $amount, array $invoice, int $periodSeconds): int
    {
        $interval = data_get($invoice, 'lines.data.0.price.recurring.interval') ?? data_get($invoice, 'lines.data.0.plan.interval');
        $count = max(1, (int) (data_get($invoice, 'lines.data.0.price.recurring.interval_count') ?? data_get($invoice, 'lines.data.0.plan.interval_count') ?? 1));

        return match ($interval) {
            'month' => intdiv($amount, $count),
            'year' => intdiv($amount, 12 * $count),
            'week' => intdiv($amount * 52, 12 * $count),
            'day' => intdiv($amount * 365, 12 * $count),
            default => self::byPeriod($amount, $periodSeconds),
        };
    }

    /** No interval on the invoice: bucket its period; only an odd period is divided by its exact length. */
    private static function byPeriod(int $amount, int $periodSeconds): int
    {
        $days = $periodSeconds / 86400;

        return match (true) {
            $periodSeconds === 0 => $amount,   // no period at all: a single charge, taken as one month
            $days >= 6 && $days <= 8 => intdiv($amount * 52, 12),
            $days >= 27 && $days <= 32 => $amount,
            $days >= 88 && $days <= 93 => intdiv($amount, 3),
            $days >= 180 && $days <= 186 => intdiv($amount, 6),
            $days >= 360 && $days <= 370 => intdiv($amount, 12),
            default => intdiv($amount * self::MONTH_SECONDS, $periodSeconds),
        };
    }

    /**
     * The organisations in scope — customers only unless internal ones are asked for; archived ones are included
     * (money charged before an archive was still charged).
     *
     * @return list<string>
     */
    private function orgIds(bool $includeInternal): array
    {
        $q = Organization::withTrashed();
        if (! $includeInternal) {
            $q->customers();
        }

        return $q->pluck('id')->map(fn ($id): string => (string) $id)->all();
    }

    /**
     * The PROCESSED Stripe events of one type for these organisations, reduced to what was charged.
     *
     * @param  list<string>  $orgIds
     * @return list<Invoice>
     */
    private function invoices(string $type, array $orgIds): array
    {
        return WebhookEvent::query()
            ->where('source', 'stripe')
            ->where('event_type', $type)
            ->where('status', WebhookEvent::STATUS_PROCESSED)
            ->whereIn('organization_id', $orgIds)
            ->get(['organization_id', 'payload', 'received_at'])
            ->map(function (WebhookEvent $e): ?array {
                $invoice = (array) data_get($e->payload, 'data.object', []);
                $amountKey = $e->event_type === 'invoice.payment_failed' ? 'amount_due' : 'amount_paid';
                $amount = (int) ($invoice[$amountKey] ?? 0);
                $currency = strtoupper((string) ($invoice['currency'] ?? ''));
                if ($currency === '') {
                    return null;
                }
                $start = (int) data_get($invoice, 'lines.data.0.period.start', 0);
                $end = (int) data_get($invoice, 'lines.data.0.period.end', 0);
                $paidAt = (int) (data_get($invoice, 'status_transitions.paid_at') ?? data_get($invoice, 'created') ?? 0);
                // Both payload shapes (older API versions put it at the top; 2025-era ones under `parent`).
                $sub = $invoice['subscription'] ?? data_get($invoice, 'parent.subscription_details.subscription');
                $sub = is_string($sub) && $sub !== '' ? $sub : null;

                return [
                    'organization_id' => (string) $e->organization_id,
                    'subscription' => $sub,
                    'currency' => $currency,
                    'amount_minor' => $amount,
                    'monthly_minor' => self::monthly($amount, $invoice, $end > $start ? $end - $start : 0),
                    'at' => $paidAt > 0 ? CarbonImmutable::createFromTimestamp($paidAt, 'UTC') : CarbonImmutable::parse($e->received_at, 'UTC'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
