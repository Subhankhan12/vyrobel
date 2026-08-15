<?php

declare(strict_types=1);

namespace App\Support\Analytics;

/**
 * The result of a performance-metrics computation over a date range and a scope (Phase 6b · Analytics).
 * A pure value object of REAL, reconciling figures — every number is derived from real bookings/folios/
 * inventory by {@see PerformanceMetrics}, never fabricated. The rate metrics (occupancy/ADR/RevPAR) are
 * accessors so the identity **RevPAR = ADR × occupancy = roomRevenue ÷ availableRoomNights** holds by
 * construction from the same `roomNightsSold`.
 *
 * BASIS (stated, so every surface agrees): "stay-night" attribution — each metric is computed over the
 * NIGHTS that fall inside the range. `roomRevenueMinor` is the room-charge revenue for those in-range
 * nights (a stay straddling the range boundary contributes only its in-range nights, pro-rated — honest).
 * Money is integer minor units in `currency`; `multiCurrency` flags a scope spanning >1 currency (its
 * money figures then cover `currency` only — the UI must surface that, never silently sum across
 * currencies, invariant #17).
 */
final class MetricSet
{
    /**
     * @param  array<string, int>  $sourceNights  reservation `source` → room-nights sold in range (real channel mix)
     */
    public function __construct(
        public readonly string $currency,
        public readonly int $roomNightsSold,
        public readonly int $availableRoomNights,
        public readonly int $roomRevenueMinor,
        public readonly int $bookings,
        public readonly int $cancellations,
        public readonly int $stays,
        public readonly int $guestNights,
        public readonly array $sourceNights = [],
        public readonly bool $multiCurrency = false,
        /**
         * D3-4 — how much of the period the inventory spine actually covers.
         *
         * ⚠️ THE DENOMINATOR CAN BE PARTIAL, AND THAT IS WHAT MAKES OCCUPANCY LIE. For the property scope,
         * `availableRoomNights` is `SUM(availability.total)` over the rows that EXIST in the range — so a
         * month the tenant was not yet live for contributes only the days it has rows. Three days of data
         * with half of them sold renders "50%" labelled as the month, which is the shape of every new
         * tenant's first year.
         *
         * `coveredDays` counts the distinct dates that carry a spine row; `periodDays` the days in the
         * range. They are metadata ABOUT the denominator, never part of it — occupancy is unchanged.
         */
        public readonly int $coveredDays = 0,
        public readonly int $periodDays = 0,
    ) {}

    /** Occupancy % = room-nights sold ÷ available room-nights (0 when no inventory in range). */
    public function occupancyPct(): int
    {
        return $this->availableRoomNights > 0
            ? (int) round($this->roomNightsSold / $this->availableRoomNights * 100)
            : 0;
    }

    /** ADR (minor) = room revenue ÷ room-nights sold — the average nightly rate actually achieved. */
    public function adrMinor(): int
    {
        return $this->roomNightsSold > 0
            ? (int) round($this->roomRevenueMinor / $this->roomNightsSold)
            : 0;
    }

    /** RevPAR (minor) = room revenue ÷ available room-nights (≡ ADR × occupancy, exactly). */
    public function revparMinor(): int
    {
        return $this->availableRoomNights > 0
            ? (int) round($this->roomRevenueMinor / $this->availableRoomNights)
            : 0;
    }

    /** Cancellation rate % = cancelled/no-show arrivals ÷ (bookings + cancellations) in range. */
    public function cancellationRatePct(): int
    {
        $total = $this->bookings + $this->cancellations;

        return $total > 0 ? (int) round($this->cancellations / $total * 100) : 0;
    }

    /** Average length of stay (nights) over the stays counted — 0.0 when none. */
    public function averageLengthOfStay(): float
    {
        return $this->stays > 0 ? round($this->guestNights / $this->stays, 1) : 0.0;
    }

    /** True when the range genuinely has no activity — the honest "not enough data yet" signal. */
    public function isEmpty(): bool
    {
        return $this->roomNightsSold === 0 && $this->bookings === 0 && $this->availableRoomNights === 0;
    }

    /**
     * D3-4 — the minimum spine coverage a period needs before its occupancy may be compared against.
     *
     * ⚠️ TUNABLE, AND DELIBERATELY A NAMED CONSTANT RATHER THAN A LITERAL BURIED IN A GUARD. Measured on the
     * real data, the distribution is starkly bimodal: prior-year months sit at 6–13% coverage and live
     * months at 100%, with NOTHING between 36% and 100%. So the exact value is not delicate — anything in
     * that gap separates the two populations identically. 80% is chosen to tolerate a handful of genuinely
     * missing days in an otherwise-live month without ever accepting a partial period.
     */
    public const MIN_COMPARABLE_COVERAGE_PCT = 80;

    /** What share of the period the spine covers, 0–100. */
    public function coveragePct(): int
    {
        return $this->periodDays > 0
            ? (int) round(min($this->coveredDays, $this->periodDays) / $this->periodDays * 100)
            : 0;
    }

    /**
     * D3-4 — may this period's occupancy be shown as a comparison basis?
     *
     * ⚠️ THIS TESTS COVERAGE, NEVER THE OCCUPANCY VALUE, and the distinction is the whole point. A fully
     * covered month with 10% occupancy is a REAL low month and must show its real 10% — hiding it would be
     * the mirror-image defect, suppressing true data. A month with three days of spine is not a low month;
     * it is an unmeasured one, and its computed percentage means nothing regardless of how high or low it
     * happens to land.
     */
    public function hasComparableCoverage(): bool
    {
        return $this->coveragePct() >= self::MIN_COMPARABLE_COVERAGE_PCT;
    }
}
