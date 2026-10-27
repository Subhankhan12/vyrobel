<?php

/*
 * D3-1 — A CUSTOM DATE RANGE ON OPERATOR INSIGHTS (2026-08-15).
 *
 * ⚠️ THE ITEM IS SMALL BECAUSE THE PLUMBING WAS ALREADY RIGHT, and that was confirmed before building:
 *   • `resolveRange()` is ONE seam — it returns `['key','label','start','end']` and everything below it
 *     consumes only the two dates. A custom window therefore needs no metric path of its own.
 *   • The last-year comparison was ALREADY a span shift (`$range['start']->subYear()` …
 *     `$range['end']->subYear()`), so it is correct for an arbitrary window with no change at all.
 *   • D3-4's coverage guard lives on the `MetricSet` that `forProperties()` returns, so a custom range that
 *     goes through that call INHERITS it.
 *
 * ⚠️ THAT LAST POINT IS THE LOAD-BEARING ONE. A custom range that built its own metrics would have rendered
 * the exact fake-full-month LY figure D3-4 had just removed — a regression introduced by a *new feature*
 * rather than by editing the fixed code. The inheritance is asserted here, not assumed.
 *
 * ⚠️ AND THE MAX-SPAN BOUND IS NOT UX POLISH. `PerformanceMetrics::monthlyTrend*()` walks months under a
 * `$guard < 13`, so a longer window silently returns 13 bars under a heading claiming more — a chart that
 * contradicts its own label (§N.8d). Every PRESET is inside that cap, so it has never bitten; an arbitrary
 * range is the first thing that could reach it. The bound is enforced at the seam rather than by loosening a
 * guard in the metric layer this item must not touch.
 */

use App\Services\AvailabilityService;
use App\Support\Analytics\CustomRange;
use App\Support\Analytics\MetricSet;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function d31Operator(array $s)
{
    return operatorUser($s, ['name' => 'Rosa Manager'], 'Hotel Manager');
}

function d31Insights(array $s, string $query = '')
{
    return test()->actingAs(d31Operator($s))
        ->withSession(['current_property_id' => $s['property']->id])
        ->get('/app/insights'.$query);
}

// ── THE RESOLVER ──────────────────────────────────────────────────────────────────────────────────

it('⚠️ D3-1 — a valid window resolves to the SAME shape a preset produces, end-EXCLUSIVE', function () {
    $range = CustomRange::resolve('2026-03-03', '2026-04-17');

    expect($range)->not->toBeNull()
        ->and($range['key'])->toBe('custom')
        ->and($range['start']->toDateString())->toBe('2026-03-03')
        // ⚠️ THE END IS THE DAY AFTER what was typed. The metric windows are half-open `[start, end)` and every
        // preset already does this; a custom window that stopped ON the 17th would silently drop the 17th —
        // the one range in the product that quietly omits a day the user asked for.
        ->and($range['end']->toDateString())->toBe('2026-04-18')
        ->and($range['label'])->toBe('3 Mar 2026 – 17 Apr 2026');
});

it('⚠️ D3-1 — an unusable window resolves to NULL so the page falls back to a preset', function () {
    // ⚠️ NULL MEANS "use a preset", never "show nothing": a half-typed URL must not empty the page.
    expect(CustomRange::resolve('2026-04-17', '2026-03-03'))->toBeNull()   // backwards — rejected, not swapped
        ->and(CustomRange::resolve('2026-03-03', null))->toBeNull()        // partial
        ->and(CustomRange::resolve(null, null))->toBeNull()                // absent
        ->and(CustomRange::resolve('not-a-date', '2026-03-03'))->toBeNull()
        ->and(CustomRange::resolve('2026-02-31', '2026-03-03'))->toBeNull();  // overflow date, never on a calendar
});

it('⚠️ D3-1 — the max span is the one the TREND BUILDER can actually render', function () {
    // ⚠️ READS THE CAP FROM THE SOURCE, not a pinned number, and pins WHY it exists: the metric layer walks
    // months under `$guard < 13`. If that guard ever changes, this fails and the two are re-reconciled
    // deliberately instead of drifting into a silently-truncated chart.
    $metrics = (string) file_get_contents(app_path('Support/Analytics/PerformanceMetrics.php'));
    expect($metrics)->toContain('$guard < 13');
    expect(CustomRange::MAX_MONTHS)->toBe(13);

    $start = '2026-01-01';
    expect(CustomRange::resolve($start, '2027-01-31'))->not->toBeNull()   // 13 calendar months — allowed
        ->and(CustomRange::resolve($start, '2027-02-01'))->toBeNull();    // 14 — refused rather than truncated
});

// ── THE SAME PATH — METRICS, LY, AND D3-4'S GUARD ─────────────────────────────────────────────────

it('⚠️ D3-1 — a custom window produces the SAME metrics as calling the layer directly', function () {
    $s = seedScenario(1);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-10-01');
    book($s, ['unit_id' => $s['units'][0]->id]);   // a real Sep 2026 stay

    $range = CustomRange::resolve('2026-09-01', '2026-09-30');
    $viaCustom = PerformanceMetrics::forProperties(
        [(string) $s['property']->id], $range['start'], $range['end'], 'EUR',
    );

    // The same window, computed by the layer the presets use — identical, because it IS the same call.
    $direct = PerformanceMetrics::forProperties(
        [(string) $s['property']->id], CarbonImmutable::parse('2026-09-01'), CarbonImmutable::parse('2026-10-01'), 'EUR',
    );

    expect($viaCustom->roomNightsSold)->toBe($direct->roomNightsSold)
        ->and($viaCustom->availableRoomNights)->toBe($direct->availableRoomNights)
        ->and($viaCustom->occupancyPct())->toBe($direct->occupancyPct())
        ->and($viaCustom->roomNightsSold)->toBeGreaterThan(0);   // non-vacuity: real figures, not two zeros
});

it('⚠️ D3-1 — the custom window INHERITS D3-4’s coverage guard (the load-bearing check)', function () {
    /*
     * ⚠️ THE REGRESSION THIS PREVENTS. D3-4 stopped Insights publishing a prior-year occupancy computed over
     * a handful of spine days. A custom range with its own metric path would have brought that straight back
     * — a new feature re-opening a just-closed defect. Here the prior year is deliberately THIN.
     */
    $s = seedScenario(1);

    // The window's own year: fully covered.
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-10-01');
    // Its prior year: three days of spine, with a stay on two of them — the shape that used to read ~50%.
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-09-04');

    $range = CustomRange::resolve('2026-09-01', '2026-09-30');
    $ids = [(string) $s['property']->id];

    // The SAME span-shifted last-year call the page makes — unchanged by this item.
    $ly = PerformanceMetrics::forProperties($ids, $range['start']->subYear(), $range['end']->subYear(), 'EUR');

    // ⚠️ NON-VACUITY: the prior-year window really is thin, and really does span the right dates.
    expect($range['start']->subYear()->toDateString())->toBe('2025-09-01')
        ->and($range['end']->subYear()->toDateString())->toBe('2025-10-01')
        ->and($ly->coveredDays)->toBe(3)
        ->and($ly->periodDays)->toBe(30);

    // …and D3-4's guard fires for the custom window exactly as it does for a preset.
    expect($ly->coveragePct())->toBeLessThan(MetricSet::MIN_COMPARABLE_COVERAGE_PCT)
        ->and($ly->hasComparableCoverage())->toBeFalse();
});

it('⚠️ D3-1 — a custom window over a FULLY-covered prior year is still comparable', function () {
    // The other direction: the guard must not suppress a custom range simply for being custom.
    $s = seedScenario(1);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-10-01');

    $range = CustomRange::resolve('2026-09-01', '2026-09-30');
    $ly = PerformanceMetrics::forProperties(
        [(string) $s['property']->id], $range['start']->subYear(), $range['end']->subYear(), 'EUR',
    );

    expect($ly->coveragePct())->toBe(100)
        ->and($ly->hasComparableCoverage())->toBeTrue();
});

// ── THE RENDERED PAGE ─────────────────────────────────────────────────────────────────────────────

it('⚠️ D3-1 — the picker renders and a custom window drives the page (§N.21.6)', function () {
    $s = seedScenario(1);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-10-01');
    book($s, ['unit_id' => $s['units'][0]->id]);

    // The picker is on the page, wired to the names the resolver reads.
    $html = (string) d31Insights($s)->assertOk()->getContent();
    expect($html)->toContain('name="from"')->toContain('name="to"')->toContain('Apply');

    // A real custom window drives it, and the page names the window it is showing.
    $custom = (string) d31Insights($s, '?from=2026-09-01&to=2026-09-30')->assertOk()->getContent();
    expect($custom)->toContain('1 Sep 2026 – 30 Sep 2026')
        ->and($custom)->toContain('Occupancy');
});

it('⚠️ D3-1 — a REJECTED window says so, and shows the preset it fell back to', function () {
    // ⚠️ Silently rendering a preset would answer a different question than the fields still show.
    $s = seedScenario(1);

    $html = (string) d31Insights($s, '?from=2026-09-30&to=2026-09-01')->assertOk()->getContent();

    expect($html)->toContain(e("That date range couldn't be used"))   // I18N-01: printed through {{ }} (&#039;)
        ->and($html)->toContain('13 months')          // the real reason, from the real constant
        // the typed values survive so the user can correct them
        ->and($html)->toContain('value="2026-09-30"');
});

it('⚠️ D3-1 — the PRESETS are unchanged (the custom range is additive)', function () {
    $s = seedScenario(1);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-10-01');

    foreach (['ytd', '6m', 'summer', 'q4', 'this-year'] as $preset) {
        d31Insights($s, '?range='.$preset)->assertOk()->assertSee('Occupancy');
    }

    // And the resolver's preset arm is untouched — a custom window takes precedence, it does not replace.
    $src = (string) file_get_contents(app_path('Livewire/Operator/OperatorInsights.php'));
    expect($src)->toContain("'ytd' => ['key' => 'ytd'")
        ->and($src)->toContain("'summer' => ['key' => 'summer'");
});

it('⚠️ D3-1 — ONE path: the custom range adds no metric call of its own (§N.8f)', function () {
    /*
     * ⚠️ THE STRUCTURAL GUARD. The whole item rests on custom and preset converging before any computation.
     * A `PerformanceMetrics::` call inside the resolver would mean a second pipeline — the thing that would
     * let a custom range drift away from the preset behaviour (and away from D3-4's guard).
     */
    // ⚠️ TOKENISED — the resolver's docblock NAMES `PerformanceMetrics::forProperties()` to explain what it
    // deliberately does NOT call, and a raw grep cannot tell an explanation from an invocation. Third time
    // this shape has bitten in this session; scanning executable tokens is the fix each time.
    $code = '';
    foreach (token_get_all((string) file_get_contents(app_path('Support/Analytics/CustomRange.php'))) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        $code .= is_array($token) ? $token[1] : $token;
    }

    expect($code)->not->toContain('PerformanceMetrics')
        ->and($code)->not->toContain('MetricSet')
        ->and($code)->not->toContain('DB::');

    // And the component reaches the resolver exactly once, inside the one seam.
    $component = (string) file_get_contents(app_path('Livewire/Operator/OperatorInsights.php'));
    expect(substr_count($component, 'CustomRange::resolve('))->toBe(1);
});

it('⚠️ D3-1 — the metric layer and D3-4’s guard were NOT modified', function () {
    // This item feeds dates; it changes no computation. If either of these fails, the scope slipped.
    $metrics = (string) file_get_contents(app_path('Support/Analytics/PerformanceMetrics.php'));
    $metricSet = (string) file_get_contents(app_path('Support/Analytics/MetricSet.php'));

    expect($metrics)->toContain('$coveredDays = (int) (clone $spine)->count(DB::raw(\'DISTINCT `date`\'));')
        ->and($metricSet)->toContain('public const MIN_COMPARABLE_COVERAGE_PCT = 80;')
        ->and($metricSet)->toContain('return $this->coveragePct() >= self::MIN_COMPARABLE_COVERAGE_PCT;');
});
