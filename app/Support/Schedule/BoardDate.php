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
    public static function resolve(mixed $param, bool $hasCurrentOrFuture, ?string $latestOnOrBefore): CarbonImmutable
    {
        if (is_string($param) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $param) === 1) {
            return CarbonImmutable::parse($param)->startOfDay();
        }

        if ($hasCurrentOrFuture || $latestOnOrBefore === null) {
            return CarbonImmutable::today();
        }

        return CarbonImmutable::parse($latestOnOrBefore)->startOfDay();
    }
}
