<?php

/*
 * D3-4 — THE LAST-YEAR OCCUPANCY COVERAGE GUARD (2026-08-15).
 *
 * ⚠️ THE DEFECT. Operator occupancy divides room-nights-sold by `SUM(availability.total)` — the rows that
 * EXIST in the range. A prior-year month the tenant was not yet live for contributes only the days it has, so
 * three days of spine with half of them sold rendered **50%**, labelled as the month. Measured on the real
 * demo data before any code changed: Aug/Sep/Oct/Nov 2025 each rendered exactly 50%, computed over 2–3 days.
 *
 * ⚠️ THE OLD GUARD ASKED THE WRONG QUESTION. `roomNightsSold > 0` is a PRESENCE test — "did anything sell?" —
 * which a three-day spine answers YES to. The question that matters is "was this period actually MEASURED?"
 *
 * ⚠️ THE DISTINCTION THIS FILE EXISTS TO PIN, and it cuts both ways:
 *   • a THIN month suppresses REGARDLESS of its computed percentage — high or low, it means nothing;
 *   • a FULLY-COVERED month publishes its real figure HOWEVER LOW — 8% on a measured month is true data,
 *     and hiding it would be the mirror-image defect, suppressing reality instead of inventing it.
 *   The guard therefore tests COVERAGE, never the occupancy VALUE. Both directions are asserted below.
 *
 * ⚠️ THE THRESHOLD IS TUNABLE AND THE DISTRIBUTION IS BIMODAL. Measured across the real spine: prior-year
 * months sit at 6–13% coverage, live months at 100%, with NOTHING between 36% and 100%. Any threshold in that
 * gap separates the two populations identically, so the exact value is not delicate — 80% tolerates a few
 * genuinely missing days without ever accepting a partial period.
 *
 * ⚠️ THE OWNER PATH IS UNAFFECTED AND UNTOUCHED. `forOwnerUnits()` divides by `count(units) × days`, a
 * full-period denominator that cannot be inflated by a thin spine. It reports 100% coverage by construction.
 */

use App\Models\Property;
use App\Services\AssignmentService;
use App\Services\AvailabilityService;
use App\Support\Analytics\MetricSet;
use App\Support\Analytics\PerformanceMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** ⚠️ Prefixed — a top-level function in a Pest file is GLOBAL and has collided in this suite before. */
function d34Ids(array $s): array
{
    return [(string) $s['property']->id];
}

/** Book a real prior-year stay so the period has genuine sales to divide. */
function d34Book(array $s, string $checkIn, string $checkOut, int $unitIndex = 1): void
{
    app(AssignmentService::class)->createBooking(
        ['property_id' => $s['property']->id, 'currency' => 'EUR', 'check_in' => $checkIn, 'check_out' => $checkOut,
            'lead_guest_id' => $s['guest']->id, 'source' => 'direct'],
        [['room_type_id' => $s['roomType']->id, 'unit_id' => $s['units'][$unitIndex]->id,
            'rate_plan_id' => $s['ratePlan']->id, 'check_in' => $checkIn, 'check_out' => $checkOut, 'price_minor' => 10000]],
    );
}

function d34Metrics(array $s, string $from, string $to): MetricSet
{
    return PerformanceMetrics::forProperties(d34Ids($s), CarbonImmutable::parse($from), CarbonImmutable::parse($to), 'EUR');
}

// ── THE FOUR CASES ────────────────────────────────────────────────────────────────────────────────

it('⚠️ D3-4 — a THIN-coverage month is NOT comparable, however high its computed percentage', function () {
    $s = seedScenario(2);

    // Three days of spine in a 30-day month, with a stay across two of them — the exact shape the real demo
    // data carries for Sep 2025, and the shape of every new tenant's first year.
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-09-04');
    d34Book($s, '2025-09-01', '2025-09-03');

    $m = d34Metrics($s, '2025-09-01', '2025-10-01');

    // ⚠️ NON-VACUITY, AND IT IS THE POINT: the figure is genuinely HIGH. The guard is not suppressing an
    // empty month — it is refusing a confident-looking number computed over a tenth of the period.
    expect($m->roomNightsSold)->toBeGreaterThan(0)
        ->and($m->occupancyPct())->toBeGreaterThan(20)
        ->and($m->coveredDays)->toBe(3)
        ->and($m->periodDays)->toBe(30)
        ->and($m->coveragePct())->toBe(10)
        ->and($m->hasComparableCoverage())->toBeFalse();

    // ⚠️ AND THE OLD GUARD WOULD HAVE PASSED IT. Stated as an assertion so the regression is impossible to
    // reintroduce quietly: `roomNightsSold > 0` is true here, which is exactly why the defect shipped.
    expect($m->roomNightsSold > 0)->toBeTrue();
});

it('⚠️ D3-4 — a FULLY-COVERED month IS comparable', function () {
    $s = seedScenario(2);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-10-01');
    d34Book($s, '2025-09-01', '2025-09-03');

    $m = d34Metrics($s, '2025-09-01', '2025-10-01');

    expect($m->coveredDays)->toBe(30)
        ->and($m->coveragePct())->toBe(100)
        ->and($m->hasComparableCoverage())->toBeTrue();
});

it('⚠️ D3-4 — a LEGITIMATELY LOW fully-covered month STILL SHOWS its real low figure', function () {
    /*
     * ⚠️ THE MIRROR-IMAGE DEFECT THIS PREVENTS. It would be easy to "fix" the inflated figures by suppressing
     * whenever occupancy looks unreliable — and that would hide REAL low months, inventing an optimistic
     * picture instead of a false one. A month with every day measured and few sales is not unmeasured; it is
     * a bad month, and the operator needs to see it.
     */
    $s = seedScenario(2);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-10-01');
    d34Book($s, '2025-09-01', '2025-09-03');   // 2 nights sold across a fully-measured 30-day month

    $m = d34Metrics($s, '2025-09-01', '2025-10-01');

    // A genuinely LOW figure…
    expect($m->occupancyPct())->toBeLessThan(20)
        ->and($m->occupancyPct())->toBeGreaterThan(0)
        // …that is fully measured, and therefore MUST be publishable.
        ->and($m->coveragePct())->toBe(100)
        ->and($m->hasComparableCoverage())->toBeTrue();
});

it('⚠️ D3-4 — the guard reads COVERAGE, not the occupancy value (both directions, side by side)', function () {
    // The two cases that would be indistinguishable to a value-based guard: a HIGH thin month and a LOW full
    // month. Coverage separates them correctly; the percentage never could.
    $s = seedScenario(2);

    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-08-01', '2025-08-04');   // thin
    d34Book($s, '2025-08-01', '2025-08-03');
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-10-01');   // full
    d34Book($s, '2025-09-01', '2025-09-03');

    $thin = d34Metrics($s, '2025-08-01', '2025-09-01');
    $full = d34Metrics($s, '2025-09-01', '2025-10-01');

    expect($thin->occupancyPct())->toBeGreaterThan($full->occupancyPct())   // the thin month LOOKS better…
        ->and($thin->hasComparableCoverage())->toBeFalse()                  // …and is the one suppressed
        ->and($full->hasComparableCoverage())->toBeTrue();                  // the lower, measured one shows
});

// ── THE THRESHOLD ─────────────────────────────────────────────────────────────────────────────────

it('⚠️ D3-4 — the threshold is a NAMED, TUNABLE constant, and the guard reads it (not a literal)', function () {
    // ⚠️ READS THE SOURCE, not a pinned number (DO-RESTORE-2's trap): the assertion follows the constant, so
    // the founder can retune it without this test asserting the old value back into place.
    $src = (string) file_get_contents(app_path('Support/Analytics/MetricSet.php'));
    expect($src)->toContain('MIN_COMPARABLE_COVERAGE_PCT')
        ->and($src)->toContain('self::MIN_COMPARABLE_COVERAGE_PCT');

    $threshold = MetricSet::MIN_COMPARABLE_COVERAGE_PCT;
    expect($threshold)->toBeGreaterThan(36)      // above the measured thin cluster's ceiling
        ->and($threshold)->toBeLessThanOrEqual(100);

    // The boundary behaves as declared, computed from the constant rather than hardcoded.
    $at = new MetricSet('EUR', 1, 1, 0, 0, 0, 0, 0, [], false, $threshold, 100);
    $below = new MetricSet('EUR', 1, 1, 0, 0, 0, 0, 0, [], false, max(0, $threshold - 1), 100);
    expect($at->hasComparableCoverage())->toBeTrue()
        ->and($below->hasComparableCoverage())->toBeFalse();
});

// ── PROPAGATION — EVERY CONSUMER OF THE LY FIGURE ─────────────────────────────────────────────────

it('⚠️ D3-4 — ALL THREE LY consumers test coverage (a suppressed figure with a live delta is the same lie)', function () {
    // ⚠️ THE LEDGER NAMED ONE CONSUMER (the occupancy-by-month guard). There are three: the KPI deltas, the
    // by-month series, and the per-property pace row — the last of which would otherwise have kept badging a
    // property "behind last year" against the very figure the page had just suppressed.
    $src = (string) file_get_contents(app_path('Livewire/Operator/OperatorInsights.php'));

    expect(substr_count($src, 'hasComparableCoverage()'))->toBe(3);

    // ⚠️ THE PRESENCE GUARD MUST BE GONE FROM THE **LY** LOOP — and only from there. A first draft banned
    // the string outright and failed on CORRECT code: `roomNightsSold > 0` legitimately survives on the
    // CURRENT-year series, where it drives the honest "not enough data yet" chart state and has nothing to do
    // with comparability. The same over-broad-literal trap the register already records. Scoped to the loop.
    $lyLoop = (string) (preg_match('/\$lyByMonthOfYear = \[\];(.*?)\}\s*\n\s*\$months = \[\];/s', $src, $m) ? $m[1] : '');
    expect($lyLoop)->not->toBe('')                              // non-vacuity: the loop was really found
        ->and($lyLoop)->toContain('hasComparableCoverage()')
        ->and($lyLoop)->not->toContain('roomNightsSold > 0');
});

it('⚠️ D3-4 — the rendered Insights page suppresses a thin LY comparison (§N.21.6, not just the metric layer)', function () {
    $s = seedScenario(2);
    book($s, ['unit_id' => $s['units'][0]->id]);                                     // a real THIS-year stay

    // A THIN prior-year month — the new-tenant shape.
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-09-04');
    d34Book($s, '2025-09-01', '2025-09-03');

    $res = $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/insights?range=this-year')->assertOk();

    // ⚠️ NO "Last year" series and NO delta pill against three days of data.
    $res->assertDontSee('Last year');
    assertNoRawBlade($res);
});

it('⚠️ D3-4 — a NEW TENANT’S FIRST YEAR reads honestly across the whole page (the headline case)', function () {
    // The case the audit named: a tenant with no prior-year history at all. Every LY consumer must be silent
    // rather than comparing against nothing.
    $s = seedScenario(2);
    book($s, ['unit_id' => $s['units'][0]->id]);   // this year only — no 2025 spine, no 2025 stays

    $res = $this->actingAs(operatorUser($s, [], 'Hotel Manager'))->get('/app/insights?range=this-year')->assertOk();

    $res->assertDontSee('Last year')
        ->assertSee('Occupancy');   // the page itself still works — this-year figures render normally

    // And the prior-year metric set is genuinely uncomparable, not merely empty-looking.
    $ly = d34Metrics($s, '2025-01-01', '2026-01-01');
    expect($ly->hasComparableCoverage())->toBeFalse()
        ->and($ly->coveredDays)->toBe(0);
});

// ── THE OWNER PATH — UNAFFECTED, UNTOUCHED ────────────────────────────────────────────────────────

it('⚠️ D3-4 — the OWNER path reports FULL coverage by construction and is unchanged', function () {
    /*
     * ⚠️ THE LINE THE AUDIT DREW AND THE REGISTER DID NOT. `forOwnerUnits()` divides by `count(units) × days`
     * — a full-period denominator — so a thin spine cannot inflate it and the guard must never suppress an
     * owner figure. Asserted rather than assumed, because the two scopes share `MetricSet`.
     */
    $s = seedScenario(2);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2025-09-01', '2025-09-04');   // deliberately THIN
    d34Book($s, '2025-09-01', '2025-09-03');

    $unitIds = [(string) $s['units'][1]->id];
    $owner = PerformanceMetrics::forOwnerUnits($unitIds, CarbonImmutable::parse('2025-09-01'), CarbonImmutable::parse('2025-10-01'), 'EUR');

    // Same thin spine, but the owner denominator is the whole month — so coverage is full and the figure is
    // comparable, exactly as before D3-4.
    expect($owner->coveredDays)->toBe(30)
        ->and($owner->periodDays)->toBe(30)
        ->and($owner->coveragePct())->toBe(100)
        ->and($owner->hasComparableCoverage())->toBeTrue()
        // The occupancy itself is the honest low figure over the full period — NOT the property scope's 50%.
        ->and($owner->occupancyPct())->toBeLessThan(20);
});

it('⚠️ D3-4 — occupancy for a FULLY-COVERED period is byte-identical to before (no definition change)', function () {
    // ⚠️ The fix adds METADATA about the denominator; it must not move a single figure that was already
    // trustworthy. Computed here from the raw spine, independently of the metric layer (§N.18.1).
    $s = seedScenario(2);
    app(AvailabilityService::class)->recompute($s['roomType']->id, '2026-09-01', '2026-10-01');
    d34Book($s, '2026-09-01', '2026-09-03', 1);

    $m = d34Metrics($s, '2026-09-01', '2026-10-01');

    $spineTotal = (int) DB::table('availability')
        ->where('room_type_id', $s['roomType']->id)
        ->whereBetween('date', ['2026-09-01', '2026-09-30'])
        ->sum('total');

    expect($m->availableRoomNights)->toBe($spineTotal)
        ->and($m->occupancyPct())->toBe((int) round($m->roomNightsSold / $spineTotal * 100));
});
