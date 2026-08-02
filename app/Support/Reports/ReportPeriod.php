<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Livewire\Operator\OperatorReports;
use Carbon\CarbonImmutable;

/**
 * GATE 10.1 — THE one resolver of a Reports reporting-period. The screen ({@see OperatorReports}),
 * the CSV export and the PDF export all resolve their range through here, so a period label / window can never
 * drift between the page and its exports (the two-paths principle — the range logic was previously duplicated in
 * the component AND the export controller).
 *
 * Periods: a specific calendar MONTH (`?month=YYYY-MM`, the prototype's "Reporting month"), or one of the roll-up
 * ranges `this-year` / `ytd` / `6m`. Windows are half-open `[start, end)` on the check-in date.
 */
final class ReportPeriod
{
    /**
     * Resolve the active period from the request's `range` + optional `month`. A valid `month` wins (a specific
     * calendar month); otherwise the named range; otherwise `this-year`.
     *
     * @return array{key: string, label: string, start: CarbonImmutable, end: CarbonImmutable}
     */
    public static function resolve(?string $rangeKey, ?string $month = null): array
    {
        $monthStart = self::parseMonth($month);
        if ($monthStart !== null) {
            return [
                'key' => 'month:'.$monthStart->format('Y-m'),
                'label' => $monthStart->format('F Y'),
                'start' => $monthStart,
                'end' => $monthStart->addMonth(),
            ];
        }

        $today = CarbonImmutable::today();

        return match ((string) $rangeKey) {
            'ytd' => ['key' => 'ytd', 'label' => 'Year to date', 'start' => $today->startOfYear(), 'end' => $today->addDay()],
            '6m' => ['key' => '6m', 'label' => 'Last 6 months', 'start' => $today->startOfMonth()->subMonths(5), 'end' => $today->addDay()],
            default => ['key' => 'this-year', 'label' => $today->format('Y'), 'start' => $today->startOfYear(), 'end' => $today->startOfYear()->addYear()],
        };
    }

    /**
     * The roll-up range pills.
     *
     * @return list<array{key: string, label: string, active: bool}>
     */
    public static function rangeOptions(string $activeKey): array
    {
        $year = CarbonImmutable::today()->format('Y');

        return [
            ['key' => 'this-year', 'label' => $year, 'active' => $activeKey === 'this-year'],
            ['key' => 'ytd', 'label' => 'Year to date', 'active' => $activeKey === 'ytd'],
            ['key' => '6m', 'label' => 'Last 6 months', 'active' => $activeKey === '6m'],
        ];
    }

    /**
     * The last 12 calendar months (newest first) for the "Reporting month" picker.
     *
     * @return list<array{value: string, label: string, active: bool}>
     */
    public static function monthOptions(string $activeKey): array
    {
        $active = str_starts_with($activeKey, 'month:') ? substr($activeKey, 6) : '';
        $cursor = CarbonImmutable::today()->startOfMonth();
        $out = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $cursor->subMonths($i);
            $value = $m->format('Y-m');
            $out[] = ['value' => $value, 'label' => $m->format('M Y'), 'active' => $value === $active];
        }

        return $out;
    }

    /** A validated first-of-month CarbonImmutable for a `YYYY-MM` string, or null when absent/malformed. */
    private static function parseMonth(?string $month): ?CarbonImmutable
    {
        if ($month === null || preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
