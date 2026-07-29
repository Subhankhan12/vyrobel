<?php

declare(strict_types=1);

namespace App\Payouts;

use App\Models\OwnerPayoutPreference;
use Carbon\CarbonImmutable;

/**
 * The owner payout CADENCE rule (payout engine · Phase 2) — the ONE pure, deterministic answer to "is a
 * payout due today?" and "when is the next one?", derived from an owner's {@see OwnerPayoutPreference}
 * (frequency + business-day). Pure + side-effect-free (no DB, no clock), so the schedule is trivially unit-tested
 * and never fabricates a date (#39). `payout_day` is the Nth BUSINESS day (1–5), NOT a calendar day; for `weekly`
 * it is the weekday (1=Mon … 5=Fri, ISO).
 *
 * The paused flag + the minimum threshold are applied by the engine's owner SELECTION, not here — this class
 * answers cadence only, so the two concerns stay independently testable.
 */
final class PayoutSchedule
{
    /**
     * Is a payout due on `$today` for these preferences? (Cadence only — paused/minimum handled by the engine.)
     *
     * @param  array{frequency: string, payout_day: int, minimum_minor?: int, paused?: bool}  $prefs
     */
    public static function isDue(array $prefs, CarbonImmutable $today): bool
    {
        $day = max(1, min(5, (int) $prefs['payout_day']));
        $ymd = $today->toDateString();

        return match ($prefs['frequency']) {
            // Weekly: `payout_day` is the ISO weekday (1=Mon … 5=Fri).
            'weekly' => $today->dayOfWeekIso === $day,
            // Twice monthly: the Nth business day counting from the 1st, and again from the 16th.
            'twice_monthly' => $ymd === self::nthBusinessDay($today, $day, 1)->toDateString()
                || $ymd === self::nthBusinessDay($today, $day, 16)->toDateString(),
            // Monthly (default): the Nth business day of the month.
            default => $ymd === self::nthBusinessDay($today, $day, 1)->toDateString(),
        };
    }

    /**
     * The next date (on or after `$today`) the cadence fires — the owner-facing "next scheduled payout" date.
     * A bounded forward scan over the pure {@see isDue} rule; never a guessed or fabricated date.
     *
     * @param  array{frequency: string, payout_day: int, minimum_minor?: int, paused?: bool}  $prefs
     */
    public static function nextRunDate(array $prefs, CarbonImmutable $today): CarbonImmutable
    {
        $date = $today;
        for ($i = 0; $i < 400; $i++) {
            if (self::isDue($prefs, $date)) {
                return $date;
            }
            $date = $date->addDay();
        }

        return $today;   // unreachable for the real frequencies — a total fallback, never fabricated
    }

    /**
     * The Nth business day (Mon–Fri) of `$anchor`'s month, counting from the `$fromDay`-th of the month.
     * Bounded so a short month / high N can never loop unbounded.
     */
    private static function nthBusinessDay(CarbonImmutable $anchor, int $n, int $fromDay): CarbonImmutable
    {
        $date = $anchor->startOfMonth()->addDays(max(0, $fromDay - 1));
        $count = 0;

        for ($i = 0; $i < 40; $i++) {
            if ($date->isWeekday()) {
                $count++;
                if ($count >= $n) {
                    return $date;
                }
            }
            $date = $date->addDay();
        }

        return $date;
    }
}
