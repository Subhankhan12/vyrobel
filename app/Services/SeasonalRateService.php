<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
use App\Models\RateSeason;
use App\Models\Unit;
use App\Support\Money\Money;
use App\Support\Pricing\NightlyRate;
use Carbon\CarbonImmutable;

/**
 * OWNER FIX 4b — applying a seasonal ("peak") price by MATERIALIZING it into `rate_calendar`.
 *
 * ⚠️⚠️ THIS SERVICE OWNS NO WRITE OF ITS OWN. Every night is written by the CERTIFIED
 * {@see RateAndRestrictionService::setRate()} — the same path {@see RateAndRestrictionService::setRateWeekendAware()}
 * uses to materialize the weekend uplift. A second write path here would be a second price source, which is
 * exactly the hazard this design exists to avoid (D4-1). A test asserts that making the certified writer
 * unresolvable stops a season from being applied at all.
 *
 * ⚠️ WHY MATERIALIZE RATHER THAN RESOLVE AT READ TIME. The default storefront charge is
 * `BookingEngineService::priceOver()` → `rate_calendar` (`NightlyRate::stayTotalForUnit()` returns NULL for a
 * unit with no override), and 22 files read `rate_calendar` directly against 7 that use the resolver. A
 * read-time season would therefore have been INERT for most bookings and would have contradicted the landing
 * page. Written into the calendar, it is visible to every reader at once and the storefront needs no change.
 *
 * ⚠️ APPLYING OVERWRITES REAL PRICES for the season's range — that IS what setting a seasonal rate means, so
 * it is a MONEY MUTATION. {@see preview()} exists so the operator sees the blast radius (how many nights, how
 * many units, and how many of those nights currently carry a DIFFERENT price) before any of it happens.
 *
 * ⚠️ SEASON-OVER-WEEKEND IS AUTHORING ORDER, NOT PRECEDENCE. Both are materialized, so whichever is applied
 * last stands. {@see preview()} reports the weekend nights a season would replace so that is legible rather
 * than surprising.
 *
 * ⚠️ THE PER-UNIT OVERRIDE STILL WINS, with no new logic: an override lives in `unit_rate_overrides`, which
 * already beats `rate_calendar` in {@see NightlyRate}. A season never touches it.
 */
class SeasonalRateService
{
    public function __construct(private RateAndRestrictionService $rates) {}

    /**
     * What applying this season would do — computed, never guessed, and read-only.
     *
     * @return array{nights: int, units: int, changing: int, weekend_nights: int, currency: string, price_minor: int, from_minor: int|null, to_minor: int}
     */
    public function preview(RateSeason $season): array
    {
        $plan = $season->ratePlan;
        $dates = $this->datesOf($season);

        $existing = RateCalendar::query()
            ->where('rate_plan_id', $season->rate_plan_id)
            ->whereDate('date', '>=', $season->starts_on->toDateString())
            ->whereDate('date', '<=', $season->ends_on->toDateString())
            ->get()
            ->keyBy(fn (RateCalendar $r): string => CarbonImmutable::parse($r->date)->toDateString());

        $changing = 0;
        $weekendNights = 0;
        foreach ($dates as $date) {
            $current = $existing->get($date);
            if ($current === null || (int) $current->price_minor !== (int) $season->price_minor) {
                $changing++;
            }
            // Fri/Sat are the nights setRateWeekendAware() prices differently — naming them lets the warning
            // say WHICH nights a season would overwrite rather than a vague "some".
            if (in_array(CarbonImmutable::parse($date)->dayOfWeekIso, [5, 6], true)) {
                $weekendNights++;
            }
        }

        // ⚠️ COUNTED IN PHP over plucked ids, deliberately — `->distinct()->count()` returns 0 in this
        // codebase and would report "0 units affected" on a warning whose entire job is to state the blast
        // radius (§N.27.7).
        $unitCount = $plan === null ? 0 : count(array_unique(
            Unit::query()->where('room_type_id', (string) $plan->room_type_id)->pluck('id')->all()
        ));

        $fromMinor = $existing->count() > 0 ? (int) $existing->first()->price_minor : null;

        return [
            'nights' => count($dates),
            'units' => $unitCount,
            'changing' => $changing,
            'weekend_nights' => $weekendNights,
            'currency' => (string) $season->currency,
            'price_minor' => (int) $season->price_minor,
            'from_minor' => $fromMinor,
            'to_minor' => (int) $season->price_minor,
        ];
    }

    /**
     * Materialize the season into `rate_calendar` through the CERTIFIED writer.
     *
     * ⚠️ THE RANGE CONVERSION HAPPENS ONCE, HERE. A season's `ends_on` is INCLUSIVE (it reads "to 31 August"
     * to a person) while the certified writer's end is EXCLUSIVE, like every stay window in this codebase.
     * Converting in one place keeps the off-by-one from being re-derived — and re-derived differently — by
     * each caller.
     */
    public function apply(RateSeason $season): RateSeason
    {
        $plan = $season->ratePlan;
        if ($plan === null) {
            return $season;   // a season whose plan has gone — write nothing rather than guess a target
        }

        $this->rates->setRate(
            $plan,
            $season->starts_on->toDateString(),
            $season->ends_on->copy()->addDay()->toDateString(),   // inclusive → exclusive, once
            Money::of((int) $season->price_minor, (string) $season->currency),
        );

        $season->forceFill(['applied_at' => now()])->save();

        return $season;
    }

    /**
     * The season covering a date for a plan, or NULL — the PEAK column's only source.
     *
     * ⚠️ DISPLAY ONLY. This is never consulted to price a stay; the charge reads `rate_calendar`. A column
     * that read this at charge time would be the second price source all over again.
     */
    public function covering(string $ratePlanId, string $date): ?RateSeason
    {
        return RateSeason::query()
            ->where('rate_plan_id', $ratePlanId)
            ->whereDate('starts_on', '<=', $date)
            ->whereDate('ends_on', '>=', $date)
            ->orderBy('starts_on')
            ->first();
    }

    /**
     * Every Y-m-d the season covers, inclusive of both bounds.
     *
     * @return list<string>
     */
    private function datesOf(RateSeason $season): array
    {
        $out = [];
        $end = CarbonImmutable::parse($season->ends_on->toDateString());
        for ($date = CarbonImmutable::parse($season->starts_on->toDateString()); $date->lte($end); $date = $date->addDay()) {
            $out[] = $date->toDateString();
        }

        return $out;
    }
}
