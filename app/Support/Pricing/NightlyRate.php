<?php

declare(strict_types=1);

namespace App\Support\Pricing;

use App\Models\RateCalendar;
use App\Models\UnitRateOverride;
use Carbon\CarbonImmutable;

/**
 * D18-5 phase 1 — THE ONE place that answers "what does this unit cost on this night".
 *
 * ⚠️ THE PRECEDENCE IS THE WHOLE FEATURE, AND ALSO THE ZERO-MOVEMENT GUARANTEE:
 *
 *     a UNIT override for (plan, unit, date)   →   wins
 *     otherwise the TYPE price for (plan, date) →   exactly today's behaviour
 *
 * Because the fallback is total, a unit nobody has priced individually resolves to the identical figure it
 * resolved to before this feature existed. That is why introducing per-unit pricing moves no money: the
 * override table starts empty, so every resolution takes the second branch.
 *
 * ⚠️ AN ABSENT OVERRIDE IS "NO OVERRIDE", NEVER €0. A missing row means fall through; only a missing TYPE
 * price means genuinely unpriced, and that is returned as NULL rather than zero so a caller must decide what
 * an unpriced night means rather than silently selling it for nothing.
 *
 * ⚠️⚠️ THIS RESOLVER **IS** ON THE CHARGING PATH. Corrected 2026-08-31 — the line that stood here said the
 * opposite ("NOT YET WIRED INTO THE CHARGING PATH, AND THAT IS DELIBERATE"). That was true of D18-5 **phase 1**;
 * **phase 2 wired it**, and the comment was never updated. A reader trusting it would conclude that editing this
 * file cannot change what a guest is charged. It can. Editing anything here is a MONEY-PATH change.
 *
 * The callers, so this cannot go stale silently again:
 *   · {@see stayTotalForUnit()} → `BookingEngineService::priceOver()` callers at :107 and :197 — the quote and
 *     the booking, once a unit has been chosen;
 *   · `ReservationCreateController:141` — the operator's own create path;
 *   · `RatesGrid::unitRows()` and `OwnerUnitDetail` — display, per cell;
 *   · `RevenueOnHold` — the maintenance revenue estimate.
 *
 * ⚠️ WHAT REMAINS TRUE from the original note, and is the reason for `stayTotalForUnit()`'s NULL: with no unit
 * chosen, `BookingEngineService::priceOver()` still prices a stay from the RATE PLAN alone. The per-unit figure
 * is consulted only once a unit is known, so a type-level quote is byte-for-byte what it always was.
 */
final class NightlyRate
{
    /**
     * The price (minor units) for a unit on a date under a plan, or NULL when the night is genuinely unpriced.
     */
    public static function forUnit(string $ratePlanId, string $unitId, string $date): ?int
    {
        $day = CarbonImmutable::parse($date)->toDateString();

        $override = UnitRateOverride::query()
            ->where('rate_plan_id', $ratePlanId)
            ->where('unit_id', $unitId)
            ->whereDate('date', $day)
            ->value('price_minor');

        if ($override !== null) {
            return (int) $override;
        }

        return self::forType($ratePlanId, $day);
    }

    /**
     * The price AND ITS CURRENCY for a unit on a date — the same override→type precedence as {@see forUnit()},
     * so a caller can never display a real amount beside the wrong symbol.
     *
     * ⚠️ WHY THIS EXISTS RATHER THAN A CURRENCY LOOKUP IN THE CALLER. `RatesGrid::unitRows()` printed every
     * per-unit cell as EUR (a literal passed to a formatter whose currency argument is REQUIRED), so a GBP
     * property's grid showed "€175.00" over rows priced in pounds. Resolving the currency in the caller would
     * have put the precedence rule in two places — exactly what §N.8f/D4-1 forbid — so the ONE resolver
     * answers both halves of the question together.
     *
     * ⚠️ ADDITIVE. {@see forUnit()} and {@see forType()} are untouched, so nothing on the charging path
     * changes behaviour; this method is display-side only.
     *
     * @return array{minor: int, currency: string}|null NULL when the night is genuinely unpriced
     */
    public static function forUnitWithCurrency(string $ratePlanId, string $unitId, string $date): ?array
    {
        $day = CarbonImmutable::parse($date)->toDateString();

        $override = UnitRateOverride::query()
            ->where('rate_plan_id', $ratePlanId)
            ->where('unit_id', $unitId)
            ->whereDate('date', $day)
            ->first(['price_minor', 'currency']);

        if ($override !== null) {
            return ['minor' => (int) $override->price_minor, 'currency' => (string) $override->currency];
        }

        $type = RateCalendar::query()
            ->where('rate_plan_id', $ratePlanId)
            ->whereDate('date', $day)
            ->first(['price_minor', 'currency']);

        return $type === null
            ? null
            : ['minor' => (int) $type->price_minor, 'currency' => (string) $type->currency];
    }

    /** The room-type price for a plan on a date — today's behaviour, unchanged and untouched. */
    public static function forType(string $ratePlanId, string $date): ?int
    {
        $price = RateCalendar::query()
            ->where('rate_plan_id', $ratePlanId)
            ->whereDate('date', CarbonImmutable::parse($date)->toDateString())
            ->value('price_minor');

        return $price === null ? null : (int) $price;
    }

    /** True when this unit carries its own price for that night (so a UI can say "overridden", not "cheaper"). */
    public static function hasOverride(string $ratePlanId, string $unitId, string $date): bool
    {
        return UnitRateOverride::query()
            ->where('rate_plan_id', $ratePlanId)
            ->where('unit_id', $unitId)
            ->whereDate('date', CarbonImmutable::parse($date)->toDateString())
            ->exists();
    }

    /**
     * D18-5 phase 2 — the stay total for a CHOSEN unit, or NULL when the unit carries no override at all.
     *
     * ⚠️ NULL IS THE ZERO-MOVEMENT GUARANTEE, AND IT IS WHY THIS RETURNS NULL RATHER THAN A NUMBER. A rate
     * plan may be DERIVED from another (`rate_plans.derived_from_rate_plan_id` + `adjustment`), so the price
     * a caller already computed for the stay is not always the raw sum of `rate_calendar`. Re-deriving it
     * here would risk moving a price that nobody asked to change. So this answers only the question it can
     * answer safely: "does this unit have its own price for any night of this stay?" — NULL means no, and the
     * caller keeps the figure it already had, byte-for-byte.
     *
     * ⚠️ WHEN AN OVERRIDE DOES EXIST it is authoritative by definition — it is an explicit per-unit price —
     * and the nights around it fall back to the type price, so a partial override prices the stay honestly
     * rather than all-or-nothing.
     *
     * @param  string  $checkOut  exclusive — stay nights are half-open [check_in, check_out)
     */
    public static function stayTotalForUnit(string $ratePlanId, string $unitId, string $checkIn, string $checkOut): ?int
    {
        $start = CarbonImmutable::parse($checkIn)->startOfDay();
        $end = CarbonImmutable::parse($checkOut)->startOfDay();

        $hasAny = UnitRateOverride::query()
            ->where('rate_plan_id', $ratePlanId)
            ->where('unit_id', $unitId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $end->toDateString())
            ->exists();

        if (! $hasAny) {
            return null;   // ⚠️ no override anywhere in the stay → the caller's existing total stands, untouched
        }

        $total = 0;
        for ($date = $start; $date->lt($end); $date = $date->addDay()) {
            $night = self::forUnit($ratePlanId, $unitId, $date->toDateString());
            if ($night === null) {
                return null;   // an unpriced night is not a free night — refuse rather than undercharge
            }
            $total += $night;
        }

        return $total;
    }
}
