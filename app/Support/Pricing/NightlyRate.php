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
 * ⚠️ THIS RESOLVER IS NOT YET WIRED INTO THE CHARGING PATH, AND THAT IS DELIBERATE — see the DECISIONS entry
 * for 2026-08-17. `BookingEngineService::priceOver()` prices a stay from a RATE PLAN before any unit is
 * assigned, so the guest storefront cannot consult a per-unit price without first deciding *when the unit is
 * chosen*, which is a product question the ruling did not settle. Wiring it blind would let the same unit
 * sell for one figure on the storefront and another on an operator screen.
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
}
