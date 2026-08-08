<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use Carbon\CarbonImmutable;

/**
 * The ONE reading of the Maintenance-Costs period (auth/isolation pass, 2026-08-09).
 *
 * The report had NO period at all — it was all-time — and the export shares the service with the screen, so
 * the moment a period existed the two had to read it identically or the PDF/CSV would quietly cover a
 * different window than the page it claims to export (#17, the two-readers-drift class). Both surfaces parse
 * the same `?from=`/`?to=` params through here.
 *
 * A missing or unparseable value yields NULL — "no bound" — rather than a guessed default, so a malformed
 * query string widens the report to all-time instead of silently reporting a window nobody asked for.
 */
final class MaintenancePeriod
{
    /** The presets the screen offers; the value is a month count back from today, null = all time. */
    public const PRESETS = ['3m' => 3, '6m' => 6, '12m' => 12, 'all' => null];

    public static function from(mixed $value): ?string
    {
        return self::date($value);
    }

    public static function to(mixed $value): ?string
    {
        return self::date($value);
    }

    /** Resolve a preset key ("6m") to its `from` date, or null for all-time / an unknown key. */
    public static function presetFrom(mixed $preset): ?string
    {
        $key = is_string($preset) ? $preset : '';
        $months = self::PRESETS[$key] ?? null;

        return $months === null ? null : CarbonImmutable::today()->subMonths($months)->toDateString();
    }

    private static function date(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;   // unparseable → no bound, never a guessed window
        }
    }
}
