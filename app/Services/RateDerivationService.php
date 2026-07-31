<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
use App\Models\RatePlan;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Derived rate plans (task #100, Decision R1) — a plan whose nightly price is DERIVED from its base plan:
 * `price = base × (1 + adjustment.pct/100)`, integer minor (#17), never a fabricated rate. A base rate change
 * (cell or bulk) recomputes each derived plan's `rate_calendar` for the same nights, so Non-refundable (−10%)
 * and Weekly (−15%) always track the base — reconciling by construction.
 *
 * The adjustment lives in `rate_plans.adjustment` (json) as `{"pct": -10}`. Writes go through the certified
 * {@see RateAndRestrictionService::setRatePerNight} — never a parallel writer.
 */
class RateDerivationService
{
    public function __construct(private RateAndRestrictionService $rates) {}

    /** The percentage adjustment for a derived plan (e.g. -10.0), or null if it isn't a derived plan. */
    public static function adjustmentPct(RatePlan $plan): ?float
    {
        if ($plan->derived_from_rate_plan_id === null) {
            return null;
        }
        $adj = is_array($plan->adjustment) ? $plan->adjustment : [];

        return isset($adj['pct']) ? (float) $adj['pct'] : null;
    }

    /**
     * Recompute every derived plan of $base across [start, end) from the base's current nightly prices.
     * Returns the number of derived plans updated.
     */
    public function recomputeDerived(RatePlan $base, CarbonImmutable $start, CarbonImmutable $endExclusive): int
    {
        /** @var Collection<int, RatePlan> $derived */
        $derived = RatePlan::query()
            ->where('derived_from_rate_plan_id', $base->getKey())
            ->where('is_active', true)
            ->get();
        if ($derived->isEmpty()) {
            return 0;
        }

        // The base's nightly prices over the window (only nights that actually have a base rate are derived).
        $baseByDate = RateCalendar::query()
            ->where('rate_plan_id', $base->getKey())
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $endExclusive->toDateString())
            ->get()
            ->keyBy(fn (RateCalendar $r): string => CarbonImmutable::parse($r->date)->toDateString());

        $updated = 0;
        foreach ($derived as $plan) {
            $pct = self::adjustmentPct($plan);
            if ($pct === null) {
                continue;
            }
            $currency = (string) $plan->currency;
            $map = [];
            foreach ($baseByDate as $date => $row) {
                $baseMinor = (int) $row->price_minor;
                $map[$date] = Money::of((int) round($baseMinor * (1 + $pct / 100)), $currency);
            }
            $this->rates->setRatePerNight($plan, $map);
            $updated++;
        }

        return $updated;
    }
}
