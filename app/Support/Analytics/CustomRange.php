<?php

declare(strict_types=1);

namespace App\Support\Analytics;

use Carbon\CarbonImmutable;

/**
 * THE custom Insights period — a validated `?from=`/`?to=` pair, resolved to the SAME `[start, end]` shape
 * the presets produce (D3-1, 2026-08-15).
 *
 * ⚠️ THIS RESOLVES A PERIOD; IT COMPUTES NOTHING. Everything downstream of `resolveRange()` — the metric
 * calls, the last-year comparison, the cadence reads — already takes only a start and an end, so a custom
 * range needs no metric path of its own. It flows through `PerformanceMetrics::forProperties()` exactly as a
 * preset does, which is the whole reason this item is small (§N.8f: presets and custom converge on one
 * pipeline rather than growing a second one).
 *
 * ⚠️ AND THAT CONVERGENCE IS WHAT MAKES IT CORRECT, NOT JUST TIDY. D3-4's coverage guard lives on the
 * {@see MetricSet} that `forProperties()` returns: a prior-year period with a thin availability spine is not
 * comparable and its figure is suppressed. A custom range that built its own metrics would have rendered the
 * exact fake-full-month number D3-4 had just removed. Inheriting the guard is not a bonus — it is the reason
 * to feed the existing path.
 *
 * ⚠️ THE LAST-YEAR COMPARISON NEEDED NOTHING EITHER: the caller already shifts BOTH ends back a year
 * (`$range['start']->subYear()` … `$range['end']->subYear()`), which is a span shift and therefore already
 * correct for an arbitrary window. Verified before building rather than assumed.
 */
final class CustomRange
{
    /**
     * ⚠️ THE CAP IS DICTATED BY THE COMPUTATION, NOT BY TASTE. `PerformanceMetrics::monthlyTrend*()` walks
     * months under a `$guard < 13`, so a longer window silently yields 13 bars under a label claiming more —
     * a chart that quietly contradicts its own heading (§N.8d). Every preset is inside the cap, so it has
     * never bitten; an arbitrary range is the first thing that could reach it. The bound is enforced HERE, at
     * the seam, rather than by loosening a guard in the metric layer this item must not touch.
     */
    public const MAX_MONTHS = 13;

    /** The query keys, named once so the resolver, the view and the guard cannot drift apart. */
    public const FROM = 'from';

    public const TO = 'to';

    /**
     * Resolve `?from=`/`?to=` into a period, or null when they are absent or unusable.
     *
     * ⚠️ NULL MEANS "FALL BACK TO A PRESET", never "show nothing". A half-typed or malformed range must not
     * empty the page — the caller keeps its preset default, so the worst outcome of a bad URL is the figures
     * the user would have seen anyway.
     *
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}|null
     */
    public static function resolve(?string $from, ?string $to): ?array
    {
        $start = self::parse($from);
        $end = self::parse($to);

        // Partial or unparseable → not a custom range at all.
        if ($start === null || $end === null) {
            return null;
        }

        // ⚠️ BACKWARDS IS REJECTED, NOT SILENTLY SWAPPED. Swapping would show figures for a period the user
        // did not ask for while the fields still read the other way round — quietly answering a different
        // question. Falling back to the preset at least matches what the page says it is showing.
        if ($start->greaterThan($end)) {
            return null;
        }

        if (self::monthsSpanned($start, $end) > self::MAX_MONTHS) {
            return null;
        }

        return [
            'key' => 'custom',
            'label' => $start->translatedFormat('j M Y').' – '.$end->translatedFormat('j M Y'),
            'start' => $start,
            // ⚠️ END-EXCLUSIVE, matching every preset. `?to=` is the last day the user means to INCLUDE, and
            // the metric layer's windows are half-open `[start, end)` — so a range typed 1–31 Aug that
            // stopped at the 31st would silently drop the 31st. The presets already do this (`$today->addDay()`,
            // `startOfYear()->addYear()`); custom must not be the one window that quietly omits a day.
            'end' => $end->addDay(),
        ];
    }

    /** Whether a real custom range is being requested at all (used to keep the fields filled after a submit). */
    public static function requested(?string $from, ?string $to): bool
    {
        return ($from !== null && $from !== '') || ($to !== null && $to !== '');
    }

    /** A strict Y-m-d parse — anything else is not a date the user picked. */
    private static function parse(?string $value): ?CarbonImmutable
    {
        $value = trim((string) $value);
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        // `createFromFormat` accepts overflow ("2026-02-31" → 3 March); a round-trip rejects what was never a
        // real calendar date.
        return ($date instanceof CarbonImmutable && $date->format('Y-m-d') === $value) ? $date : null;
    }

    /** Calendar months touched by the window, inclusive — the unit the trend builder actually walks. */
    private static function monthsSpanned(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (int) $start->startOfMonth()->diffInMonths($end->startOfMonth()) + 1;
    }
}
