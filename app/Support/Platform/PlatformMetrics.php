<?php

declare(strict_types=1);

namespace App\Support\Platform;

use App\Http\Controllers\Admin\DashboardController;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Subscription;
use Illuminate\Support\Carbon;

/**
 * SUPER ADMIN · BUILD 1 · GATE 1 — REAL platform metrics across ALL organizations.
 *
 * Every figure is a REAL aggregate over real rows — no fabrication (#39); honest zero/empty states. This class
 * ONLY computes; it does NOT open the cross-tenant bypass itself. The caller ({@see DashboardController})
 * runs `compute()` INSIDE {@see PlatformQuery::read()}, so every query here executes under the audited,
 * admin-guard-gated, scope-restored `runAsSuperAdmin` context — never an ad-hoc bypass.
 */
class PlatformMetrics
{
    /**
     * @return array{
     *   organizations: int,
     *   by_status: array<string, int>,
     *   active_subscriptions: int,
     *   on_trial: int,
     *   properties: int,
     *   reservations: int,
     *   plan_distribution: list<array{name: string, code: string, count: int}>,
     *   signups: list<array{label: string, count: int}>,
     *   recurring_plan_value_minor: int,
     *   currency: string,
     * }
     */
    public function compute(?Carbon $now = null): array
    {
        $now ??= Carbon::now();

        // Org status is a real enum column: trialing | active | suspended | cancelled.
        $byStatusRaw = Organization::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [];
        foreach (['active', 'trialing', 'suspended', 'cancelled'] as $status) {
            $byStatus[$status] = (int) ($byStatusRaw[$status] ?? 0);
        }

        // Subscriptions — the real billing state. `active` is reached only via a real Stripe webhook; `trialing`
        // is the provisioned free trial. Both are honest counts of real rows.
        $activeSubscriptions = Subscription::query()->where('status', 'active')->count();
        $onTrial = Subscription::query()->where('status', 'trialing')->count();

        return [
            'organizations' => Organization::query()->count(),
            'by_status' => $byStatus,
            'active_subscriptions' => $activeSubscriptions,
            'on_trial' => $onTrial,
            'properties' => Property::query()->count(),
            'reservations' => Reservation::query()->count(),
            'plan_distribution' => $this->planDistribution(),
            'signups' => $this->signupsByMonth($now),
            'recurring_plan_value_minor' => $this->recurringPlanValueMinor(),
            'currency' => (string) config('billing.default_currency', 'EUR'),
        ];
    }

    /**
     * How many orgs sit on each plan (via their latest subscription). Real counts; a plan with none is omitted
     * (honest — no zero-filler).
     *
     * @return list<array{name: string, code: string, count: int}>
     */
    private function planDistribution(): array
    {
        $counts = Subscription::query()
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
     * Sign-ups (orgs created) per month for the last 6 months — a real time series over `organizations.created_at`.
     *
     * @return list<array{label: string, count: int}>
     */
    private function signupsByMonth(Carbon $now): array
    {
        $start = $now->copy()->startOfMonth()->subMonths(5);

        $rows = Organization::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at']);

        $series = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $series[$month->format('Y-m')] = ['label' => $month->format('M'), 'count' => 0];
        }

        foreach ($rows as $org) {
            $key = $org->created_at?->format('Y-m');
            if ($key !== null && isset($series[$key])) {
                $series[$key]['count']++;
            }
        }

        return array_values($series);
    }

    /**
     * The recurring monthly PLAN VALUE of active + trialing subscriptions — a REAL sum of real plan prices,
     * normalised to a monthly figure (a yearly plan ÷ 12). Labelled honestly as *plan value* on the dashboard:
     * live charging is account-gated (no money is actually collected here), so this is contracted/potential
     * recurring value, never "collected revenue".
     */
    private function recurringPlanValueMinor(): int
    {
        $subscriptions = Subscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->with('plan')
            ->get();

        $total = 0;
        foreach ($subscriptions as $subscription) {
            $plan = $subscription->plan;
            if ($plan === null) {
                continue;
            }

            $monthly = $plan->interval === 'yearly'
                ? (int) round($plan->price_minor / 12)
                : (int) $plan->price_minor;

            $total += $monthly;
        }

        return $total;
    }
}
