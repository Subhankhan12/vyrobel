<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RateCalendar;
use App\Models\RateSeason;
use App\Models\Unit;
use App\Support\Money\Money;
use App\Support\Pricing\AgreedRateDisclosure;
use App\Support\Pricing\NightlyRate;
use App\Support\Pricing\SeasonPrecedence;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

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
     * @return array{nights: int, units: int, changing: int, weekend_nights: int, currency: string, price_minor: int, from_minor: int|null, to_minor: int, overlaps: int, losing_nights: int, losing_to: string|null, rewritten_nights: int, agreed_nights: int, agreed_owners: int, agreed_from: string|null, agreed_to: string|null}
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

        // ⚠️ THE CONFLICT, DERIVED — never a count typed into a page. For each night this season covers,
        // ask the ONE rule which season governs it. `losing` is the number of this season's own nights that
        // another season outranks, and `rewritten` is the extra nights that applying will touch because an
        // already-applied overlapping season is re-applied alongside it (see applyInPrecedenceOrder()).
        $others = $this->overlapping($season);
        // ⚠️ BUILT ONCE, OUTSIDE THE LOOP. `$others->push($season)` inside the loop MUTATES the collection,
        // so the set grew by one on every date and the `$rewritten` tally below then counted this season too.
        $contenders = $others->concat([$season]);
        $losing = 0;
        $rival = null;
        foreach ($dates as $date) {
            $winner = SeasonPrecedence::winnerOn($contenders, $date);
            if ($winner !== null && (string) $winner->getKey() !== (string) $season->getKey()) {
                $losing++;
                $rival ??= (string) $winner->name;
            }
        }

        $rewritten = 0;
        foreach ($others as $other) {
            if ($other->applied_at !== null) {
                $rewritten += SeasonPrecedence::nights($other);
            }
        }

        // ⚠️⚠️ WHAT THIS SEASON WOULD OVERWRITE THAT SOMEBODY NEGOTIATED — the DISCLOSURE, not a policy.
        //    Nothing below refuses anything; `SeasonPrecedence` is untouched and the write is unchanged.
        //
        // ⚠️ IT IS HANDED THE SAME `$dates` AND THE SAME `$existing` COLLECTION THIS METHOD ALREADY LOADED,
        //    deliberately: a disclosure that re-read the current prices would be a SECOND SOURCE for one
        //    fact and could disagree with the blast radius printed beside it (§N.8f).
        //
        // ⚠️ It covers NEGOTIATED rates only — see AgreedRateDisclosure for the false negative it cannot
        //    close (an owner's self-set rate leaves no record at all) and why the wording says so on screen.
        $agreed = AgreedRateDisclosure::forSeason($season, $dates, $existing);

        return [
            'nights' => count($dates),
            'units' => $unitCount,
            'changing' => $changing,
            'weekend_nights' => $weekendNights,
            'currency' => (string) $season->currency,
            'price_minor' => (int) $season->price_minor,
            'from_minor' => $fromMinor,
            'to_minor' => (int) $season->price_minor,
            // ⚠️ ADDITIVE — every key above is unchanged, so nothing that reads this preview moves.
            'overlaps' => $others->count(),
            'losing_nights' => $losing,
            'losing_to' => $rival,
            'rewritten_nights' => $rewritten,
            // ⚠️ ADDITIVE, like the block above it — existing readers are untouched.
            'agreed_nights' => $agreed['nights'],
            'agreed_owners' => $agreed['owners'],
            'agreed_from' => $agreed['from'],
            'agreed_to' => $agreed['to'],
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
        // ⚠️⚠️ THE ONE RULE, NOT A SECOND OPINION (2026-09-01). This was `orderBy('starts_on')->first()` —
        // earliest start wins — while the PRICE came from whichever season had been applied last. The column
        // could therefore name one season while the guest paid another's. Both sides now ask
        // {@see SeasonPrecedence}, and `applyInPrecedenceOrder()` makes the write agree with this answer.
        return SeasonPrecedence::winnerOn(
            RateSeason::query()
                ->where('rate_plan_id', $ratePlanId)
                ->whereDate('starts_on', '<=', $date)
                ->whereDate('ends_on', '>=', $date)
                ->get(),
            $date,
        );
    }

    /**
     * Every season on the same plan whose range touches `$season`'s — the set precedence has to resolve.
     *
     * @return Collection<int, RateSeason>
     */
    public function overlapping(RateSeason $season): Collection
    {
        return RateSeason::query()
            ->where('rate_plan_id', $season->rate_plan_id)
            ->whereKeyNot($season->getKey())
            // Two inclusive ranges touch unless one ends before the other starts.
            ->whereDate('starts_on', '<=', $season->ends_on->toDateString())
            ->whereDate('ends_on', '>=', $season->starts_on->toDateString())
            ->get();
    }

    /**
     * ⚠️⚠️ THE PRECEDENCE-HONOURING WRITE — and the reason the rule can govern MONEY at all.
     *
     * ⚠️ THE PROBLEM THIS SOLVES. Precedence is normally a read-time idea, but a season is MATERIALISED into
     * `rate_calendar`, so once written, no rule changes those nights — only another write does. Whichever
     * season was applied last had already won, whatever any rule said.
     *
     * ⚠️ THE SOLUTION IS WRITE ORDER, NOT A NEW WRITER. The set is applied WEAKEST FIRST through the
     * UNCHANGED, certified {@see apply()} — so the strongest season is written last and stands. Nothing here
     * writes a price itself; `apply()` and `RateAndRestrictionService::setRate()` are untouched.
     *
     * ⚠️ THE RESULT IS ORDER-INDEPENDENT. Applying the broad season or the narrow one produces the SAME
     * calendar, because the whole covering set is re-applied in rule order either way. That is the entire
     * point: the price stops depending on which button somebody pressed first.
     *
     * ⚠️⚠️ THE TWO-STEP IS PRESERVED — AND THIS IS THE SUBTLE PART. Only seasons the operator has ALREADY
     * APPLIED are re-applied alongside this one. A season that has merely been SAVED is never written by
     * someone else's apply: that would write prices nobody approved and would destroy the deliberate second
     * step the whole feature is built around.
     *
     * ⚠️ THE COST, STATED: re-applying a neighbour rewrites ITS nights too, so a manual price edit made
     * inside an already-applied overlapping season's range is overwritten. {@see preview()} reports those
     * nights so the confirmation can state the real blast radius rather than this season's alone.
     *
     * @return list<RateSeason> every season written, weakest first
     */
    public function applyInPrecedenceOrder(RateSeason $season): array
    {
        $set = $this->overlapping($season)
            ->filter(fn (RateSeason $other): bool => $other->applied_at !== null)
            ->all();
        $set[] = $season;

        $written = [];
        foreach (SeasonPrecedence::weakestFirst($set) as $each) {
            $written[] = $this->apply($each);
        }

        return $written;
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
