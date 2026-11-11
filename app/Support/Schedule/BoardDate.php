<?php

declare(strict_types=1);

namespace App\Support\Schedule;

use Carbon\CarbonImmutable;

/**
 * The daily-ops board's default day — the ONE rule shared by the operator Front Desk and the Calendar/Rack so
 * the two can't drift (F-OP-1). Both boards previously anchored on `->max('check_in')`, which lands on the
 * FURTHEST-FUTURE booking (a demo could open months ahead) — the opposite of what a "today's ops" board wants.
 *
 * The rule:
 *   1. An explicit, valid `?date` / `?start` (YYYY-MM-DD) always wins.
 *   2. Otherwise the board opens on TODAY whenever the property has any current or upcoming activity (a stay
 *      whose check-out is still ahead) — the board's entire purpose.
 *   3. Only a stale dataset with NOTHING on or after today falls back to the most recent PAST arrival
 *      (`$latestOnOrBefore`), so a demo/live board still lands on real occupancy — NEVER the furthest future.
 *
 * The two components run their own (differently-scoped) queries for `$hasCurrentOrFuture` + `$latestOnOrBefore`;
 * only the DECISION lives here, once.
 */
final class BoardDate
{
    /**
     * THE PROPERTY'S OWN "TODAY" (D-RACK-START-TZ, 2026-11-11, his "do all"): the board's day is the day it is AT THE PROPERTY, not in the
     * app's timezone (UTC). Far from UTC the two differ for hours every day — seen live, a Paris property at 00:15 Monday opened its
     * Calendar on Sunday — and the walk-in's "tonight" would have been the wrong night. With no property (or a blank timezone) it is the
     * app's day, exactly as before. A date, no time: `2026-10-05`.
     */
    public static function today(?string $timezone = null): CarbonImmutable
    {
        // no timezone → `now()` in PHP's default zone, which Laravel sets to config('app.timezone') at boot (this class stays free of the container: a pure unit test reads it)
        return CarbonImmutable::parse(CarbonImmutable::now(($timezone !== null && $timezone !== '') ? $timezone : null)->toDateString());
    }

    public static function resolve(mixed $param, bool $hasCurrentOrFuture, ?string $latestOnOrBefore, ?string $timezone = null): CarbonImmutable
    {
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            return CarbonImmutable::parse($param)->startOfDay();
        }

        if ($hasCurrentOrFuture || $latestOnOrBefore === null) {
            return self::today($timezone);
        }

        return CarbonImmutable::parse($latestOnOrBefore)->startOfDay();
    }
}
