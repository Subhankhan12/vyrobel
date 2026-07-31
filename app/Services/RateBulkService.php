<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Availability;
use App\Models\RateCalendar;
use App\Models\Restriction;
use App\Models\RoomType;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Bulk rate edit (task #100) — apply an increase% / decrease% / set-exact adjustment across a set of room
 * types over a date range, restricted to a nights filter (whole range / weekends / midweek), SKIPPING booked
 * and closed nights, and optionally recomputing derived plans. Writes go through the certified
 * {@see RateAndRestrictionService::setRatePerNight}; derived recompute through {@see RateDerivationService}.
 *
 * %-modes adjust each night's EXISTING base price (a night with no base rate is skipped — nothing to adjust);
 * set-exact writes the given price to every matching, non-skipped night. Reconciling by construction (#17).
 */
class RateBulkService
{
    public const MODES = ['increase_pct', 'decrease_pct', 'set_exact'];

    public const NIGHTS = ['whole', 'weekends', 'midweek'];

    public function __construct(
        private RateAndRestrictionService $rates,
        private RateDerivationService $derivation,
    ) {}

    /**
     * @param  list<RoomType>  $roomTypes
     * @return array{applied: int, skipped: int, nights: int, room_types: int, derived: int, missing_plan: int}
     */
    public function apply(
        array $roomTypes,
        CarbonImmutable $start,
        CarbonImmutable $endExclusive,
        string $mode,
        float $pct,
        ?int $exactMinor,
        string $nightsFilter,
        bool $skipBooked,
        bool $updateDerived,
    ): array {
        $applied = 0;
        $skipped = 0;
        $derivedCount = 0;
        $missingPlan = 0;
        $roomTypeCount = 0;

        foreach ($roomTypes as $roomType) {
            $plan = $roomType->primaryRatePlan();
            if ($plan === null) {
                $missingPlan++;

                continue;
            }
            $currency = (string) $plan->currency;

            $existing = RateCalendar::query()
                ->where('rate_plan_id', $plan->getKey())
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<', $endExclusive->toDateString())
                ->get()
                ->keyBy(fn (RateCalendar $r): string => CarbonImmutable::parse($r->date)->toDateString());

            $blocked = $skipBooked ? $this->blockedDates((string) $roomType->getKey(), $start, $endExclusive) : [];

            $map = [];
            for ($date = $start; $date->lt($endExclusive); $date = $date->addDay()) {
                $key = $date->toDateString();
                if (! $this->matchesNights($date, $nightsFilter)) {
                    continue;
                }
                if (isset($blocked[$key])) {
                    $skipped++;

                    continue;
                }

                if ($mode === 'set_exact') {
                    $map[$key] = Money::of((int) $exactMinor, $currency);
                } else {
                    $row = $existing->get($key);
                    if ($row === null) {
                        continue;   // no base rate to adjust
                    }
                    $factor = $mode === 'increase_pct' ? (1 + $pct / 100) : (1 - $pct / 100);
                    $map[$key] = Money::of(max(0, (int) round((int) $row->price_minor * $factor)), $currency);
                }
            }

            if ($map !== []) {
                $this->rates->setRatePerNight($plan, $map);
                $applied += count($map);
                $roomTypeCount++;

                if ($updateDerived) {
                    $derivedCount += $this->derivation->recomputeDerived($plan, $start, $endExclusive);
                }
            }
        }

        return [
            'applied' => $applied,
            'skipped' => $skipped,
            'nights' => (int) $start->diffInDays($endExclusive),
            'room_types' => $roomTypeCount,
            'derived' => $derivedCount,
            'missing_plan' => $missingPlan,
        ];
    }

    private function matchesNights(CarbonImmutable $date, string $filter): bool
    {
        $isWeekend = in_array($date->dayOfWeekIso, [5, 6], true);   // Fri=5, Sat=6

        return match ($filter) {
            'weekends' => $isWeekend,
            'midweek' => ! $isWeekend,
            default => true,
        };
    }

    /**
     * Nights to skip: fully booked (available === 0) or closed (stop_sell). Keyed Y-m-d.
     *
     * @return array<string, true>
     */
    private function blockedDates(string $roomTypeId, CarbonImmutable $start, CarbonImmutable $endExclusive): array
    {
        $blocked = [];

        Availability::query()
            ->where('room_type_id', $roomTypeId)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $endExclusive->toDateString())
            ->get()
            ->each(function (Availability $a) use (&$blocked): void {
                if ($a->available() === 0) {
                    $blocked[CarbonImmutable::parse($a->date)->toDateString()] = true;
                }
            });

        Restriction::query()
            ->where('room_type_id', $roomTypeId)
            ->where('stop_sell', true)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<', $endExclusive->toDateString())
            ->get()
            ->each(function (Restriction $r) use (&$blocked): void {
                $blocked[CarbonImmutable::parse($r->date)->toDateString()] = true;
            });

        return $blocked;
    }
}
