<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The frozen "now" every test sees — see the note below the test-case binding. */
const VY_TEST_CLOCK = '2026-08-01 09:00:00';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| ⚠️ THE TEST CLOCK — frozen, so the suite does not depend on the wall calendar
|--------------------------------------------------------------------------
|
| ⚠️ THE SUITE WAS BROADLY DATE-FRAGILE, and far beyond the two tests that first exposed it. Run with the
| machine clock at 2027-03-04 it produced 52 failures across 25 files; at the real date, 9. Nothing about the
| code had changed — only what day it was. That is non-deterministic by definition: it makes the baseline
| mean different things on different mornings, and it is a landmine the moment CI runs on a schedule.
|
| ⚠️ THE CAUSE IS THE PINNED-FIXTURE DIRECTION, NOT THE RELATIVE ONE. Fixtures across the suite pin real
| dates in September 2026 (`book()`'s 2026-09-01 and its many siblings) and then assert behaviour that
| depends on those stays being UPCOMING — arrival windows, trips, "this month" occupancy, analytics ranges.
| Those assertions are correct while "now" sits before the pinned dates and become wrong the moment the wall
| clock crosses them. The fixtures did not drift; the present moved past them.
|
| ⚠️ SO "NOW" IS PINNED HERE, ONCE, CENTRALLY — rather than re-anchoring hundreds of fixtures across 25
| files, which would be a far larger and far riskier diff for the same result. Individual tests that need a
| different moment still call `Carbon::setTestNow()` / `travelTo()` themselves; a local freeze simply
| overrides this one, and 18 files already do exactly that.
|
| ⚠️ THE ANCHOR IS DELIBERATE, NOT "THE DAY THIS WAS WRITTEN": it sits inside a window proven stable by
| measurement (2026-06-01 · 2026-08-01 · 2026-08-16 · 2026-08-31 all green across the previously fragile
| files), and before the September-2026 fixtures so their stays remain upcoming, which is what they assert.
|
| ⚠️ THIS CHANGES NO PRODUCTION CODE and makes nothing pass that was failing: frozen, the suite reproduces
| the SAME non-green set as the unfrozen run on the day it was introduced. It removes date-dependence, not
| coverage.
|
| ⚠️ TO RE-RUN THE PROOF: `VY_TEST_NOW=2027-03-04 ./vendor/bin/pest` simulates a date roll. Before this
| freeze that produced 52 failures; the override exists so the next person can verify the claim rather than
| trust it.
*/
pest()->beforeEach(function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse(getenv('VY_TEST_NOW') ?: VY_TEST_CLOCK));
    Carbon::setTestNow(Carbon::parse(getenv('VY_TEST_NOW') ?: VY_TEST_CLOCK));
})->afterEach(function (): void {
    CarbonImmutable::setTestNow();
    Carbon::setTestNow();
})->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

require_once __DIR__.'/Support/booking_helpers.php';
require_once __DIR__.'/Support/channel_helpers.php';
require_once __DIR__.'/Support/booking_engine_helpers.php';
require_once __DIR__.'/Support/owner_helpers.php';
require_once __DIR__.'/Support/auth_helpers.php';
require_once __DIR__.'/Support/marketing_helpers.php';
require_once __DIR__.'/Support/admin_helpers.php';
