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
}
