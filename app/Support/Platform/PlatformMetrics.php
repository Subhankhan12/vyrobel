<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Controllers\Admin\OverviewController;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Subscription;
use App\Support\I18n\LocalDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * REAL platform business metrics — computed inside {@see PlatformQuery::read()} by {@see OverviewController}.
 *
 * ⚠️⚠️ ADMIN 01 · PHASE 1 — TWO CORRECTIONS, BOTH TO NUMBERS ON THE FIRST SCREEN THE FOUNDER SEES:
 *
 *  1. ⚠️⚠️ INTERNAL ORGANISATIONS ARE EXCLUDED BY DEFAULT. 🔬 Production held three organisations and all three
 *     were Vyrobel's own (a test workspace, the internal role-account tenant, a probe). Every count here was
 *     counting them as customers. `$includeInternal` puts them back, visibly, on request.
 *
 *  2. ⚠️⚠️ THE "RECURRING PLAN VALUE" IS GONE — NOT RELABELLED, GONE. 🔬 It read €297/month on production: the
 *     list price of three FREE TRIALS belonging to those three internal orgs, none of them billed. It summed
 *     `plans.price_minor` over active AND trialing subscriptions — a price table multiplied by intent, which is
 *     not revenue and was never going to be. Revenue arrives in Phase 4, computed from what is actually
 *     charged. Until then this reports only whether any LIVE billing exists (a subscription with a Stripe id
 *     that is active), and the screen says "No live billing yet" when none does.
 *
 * ⚠️ `per_unit_minor` and `vat_pct` are read nowhere here (both are display-only / inert — measured).
 */
class PlatformMetrics
{
    /**
     * @return array{
     *   include_internal: bool,
     *   internal_orgs: int,
     *   organizations: int,
     *   by_status: array<string, int>,
     *   active_subscriptions: int,
     *   on_trial: int,
     *   live_billed_subscriptions: int,
     *   properties: int,
     *   reservations: int,
     *   plan_distribution: list<array{name: string, code: string, count: int}>,
     *   signups: list<array{label: string, count: int}>,
     * }
     */
    public function compute(bool $includeInternal = false, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        // ⚠️ ONE org set drives every figure. `Organization::query()` already excludes archived (soft-deleted)
        //    orgs, and every related count is filtered through the SAME set — so including internal orgs can
        //    never also drag archived ones back in through a subscription or a reservation.
        $orgs = fn (): Builder => $includeInternal ? Organization::query() : Organization::query()->customers();
        $inScope = fn (Builder $q): Builder => $q->whereIn('organization_id', $orgs()->select('id'));

        // Org status is a real enum column: trialing | active | suspended | cancelled.
        $byStatusRaw = $orgs()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [];
        foreach (['active', 'trialing', 'suspended', 'cancelled'] as $status) {
            $byStatus[$status] = (int) ($byStatusRaw[$status] ?? 0);
        }

        return [
            'include_internal' => $includeInternal,
            'internal_orgs' => Organization::query()->internal()->count(),
            'organizations' => $orgs()->count(),
            'by_status' => $byStatus,
            // `active` is reached only via a real Stripe webhook; `trialing` is the provisioned free trial.
            'active_subscriptions' => $inScope(Subscription::query())->where('status', 'active')->count(),
            // ⚠️ DEC-7 — a COMPLIMENTARY workspace is a `trialing` row with a mark: it is a free workspace, not a trial
            //    waiting to convert, so it is not counted here (the directory labels it "complimentary").
            'on_trial' => $inScope(Subscription::query())->where('status', 'trialing')->whereNull('complimentary_until')->count(),
            // ⚠️ LIVE = active AND linked to a real Stripe subscription. A status alone is not money.
            'live_billed_subscriptions' => $inScope(Subscription::query())
                ->where('status', 'active')
                ->whereNotNull('stripe_subscription_id')
                ->count(),
            'properties' => $inScope(Property::query())->count(),
            'reservations' => $inScope(Reservation::query())->count(),
            'plan_distribution' => $this->planDistribution($inScope),
            'signups' => $this->signupsByMonth($orgs(), $now),
        ];
    }

    /**
     * How many organisations sit on each plan (active OR trialing) — a COUNT of subscriptions, never a sum of
     * prices. A plan with none is omitted (honest — no zero-filler).
     *
     * @param  \Closure(Builder): Builder  $inScope
     * @return list<array{name: string, code: string, count: int}>
     */
    private function planDistribution(\Closure $inScope): array
    {
        $counts = $inScope(Subscription::query())
            ->selectRaw('plan_id, COUNT(*) as aggregate')
            ->whereIn('status', ['active', 'trialing'])
            ->groupBy('plan_id')
            ->pluck('aggregate', 'plan_id');

        if ($counts->isEmpty()) {
            return [];
        }

        return Plan::query()
            ->whereIn('id', $counts->keys())
            ->orderBy('price_minor')
            ->get()
            ->map(fn (Plan $plan): array => [
                'name' => (string) $plan->name,
                'code' => (string) $plan->code,
                'count' => (int) ($counts[$plan->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * Sign-ups (organisations created) per month for the last 6 months — a real series over `created_at`.
     *
     * @param  Builder<Organization>  $orgs
     * @return list<array{label: string, count: int}>
     */
    private function signupsByMonth(Builder $orgs, Carbon $now): array
    {
        $start = $now->copy()->startOfMonth()->subMonths(5);

        $rows = $orgs->where('created_at', '>=', $start)->get(['created_at']);

        $series = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $series[$month->format('Y-m')] = ['label' => $month->translatedFormat(LocalDate::pattern('M')), 'count' => 0];
        }

        foreach ($rows as $org) {
            $key = $org->created_at?->format('Y-m');
            if ($key !== null && isset($series[$key])) {
                $series[$key]['count']++;
            }
        }

        return array_values($series);
    }
}
