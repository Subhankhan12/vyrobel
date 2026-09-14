<?php

use App\Models\Organization;
use App\Models\OwnerStatement;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/*
 * DO-SEED-1 — THE DEMO SEED MUST COMPLETE, ON ANY DAY (2026-08-12).
 *
 * The seeder used to mix a today-RELATIVE forward window with an ABSOLUTE calendar. They were disjoint when
 * written; as real time advanced the relative window slid into the absolute one and `migrate:fresh --seed`
 * aborted on a UnitUnavailableException — mid-run, so the demo org was left HALF-SEEDED (one property instead
 * of two, and the demo staff user with no roles at all).
 *
 * ⚠️ WHAT THESE TESTS DEFEND IS THE ROOT, NOT THE COLLISION. A test that only asserted "the seed does not throw
 * today" would have passed on every day before it broke. The load-bearing one is the TIME-TRAVEL test: it seeds
 * at several distinct future dates, which is the only way to distinguish a re-anchored seed from a bumped
 * literal. Re-introduce an absolute date and it goes red on the day that date rots past.
 */

/** Seed the demo as if it were `$date`, and return the wall-clock-independent end state. */
function seedDemoAt(string $date): array
{
    // ⚠️⚠️ CAPTURE THE CLOCK THIS HELPER IS INTERRUPTING — do not assume there wasn't one.
    //
    // `tests/Pest.php` freezes every Feature test at VY_TEST_CLOCK (2026-08-01 09:00). The `finally` below
    // used to call the BARE `setTestNow()`, which does not restore that freeze — it CLEARS it, dropping the
    // rest of the test onto the real wall clock. So this helper seeded the demo on the FROZEN day and then
    // handed its caller the REAL day, and the caller asserted against the second while the database had been
    // built from the first.
    //
    // ⚠️ INVISIBLE FOR AS LONG AS THE TWO SHARED A MONTH, which is why it survived all of August and went red
    // on 1 September. ⚠️ AND IT IS NOT A MONTH-BOUNDARY BUG THAT COMES AND GOES — once the wall clock leaves
    // the frozen month it stays wrong every day, not one day in thirty.
    //
    // ⚠️ RESTORING THE PREVIOUS VALUE, NOT RE-APPLYING VY_TEST_CLOCK: a caller that set its own clock before
    // calling this gets its own clock back, and the constant is not duplicated here (§N.8f).
    $priorMutable = Carbon::getTestNow();
    $priorImmutable = CarbonImmutable::getTestNow();

    Carbon::setTestNow(CarbonImmutable::parse($date));
    CarbonImmutable::setTestNow(CarbonImmutable::parse($date));

    try {
        (new DemoSeeder)->run();

        $org = Organization::withoutGlobalScopes()->where('slug', 'vyrobel-demo')->firstOrFail();
        $staff = User::where('email', 'demo-staff@vyrobel-demo.test')->firstOrFail();
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->getKey());

        return [
            'properties' => Property::withoutGlobalScopes()->where('organization_id', $org->getKey())->count(),
            'staffRoles' => $staff->fresh()->getRoleNames()->all(),
            'reservations' => Reservation::withoutGlobalScopes()->count(),
            'statements' => OwnerStatement::withoutGlobalScopes()->count(),
        ];
    } finally {
        Carbon::setTestNow($priorMutable);
        CarbonImmutable::setTestNow($priorImmutable);
    }
}

it('completes the whole demo seed — both properties, and the staff user WITH its role', function () {
    $state = seedDemoAt(CarbonImmutable::today()->toDateString());

    // ⚠️ The half-seed is the real damage, so assert the END STATE rather than "it did not throw".
    // Before the fix these read: 1 property, and [] roles — which is what made /app unreachable.
    expect($state['properties'])->toBe(2)
        ->and($state['staffRoles'])->toContain('Hotel Manager')
        ->and($state['reservations'])->toBeGreaterThan(30)
        // Five completed months paid + the showcase month finalized — the shape the owner portal demos on.
        ->and($state['statements'])->toBe(6);
});

// ⚠️ One case PER DATE, not a loop inside one test: the seeder is not idempotent (it creates the demo property
// by slug), so each date needs its own fresh database — which is exactly what RefreshDatabase gives per test.
// The dates cross the boundaries a date-coupled seed trips over.
it('⚠️ seeds cleanly on ANY day — the proof this is an anchor and not a bumped literal', function (string $day) {
    $state = seedDemoAt($day);

    expect($state['properties'])->toBe(2, "the seed half-completed when run on {$day}")
        ->and($state['staffRoles'])->toContain('Hotel Manager')
        ->and($state['statements'])->toBe(6, "the statement history did not fully seed on {$day}");
})->with([
    'a month end (showcaseMonth rolls over)' => '2026-08-31',
    'a year boundary (completedMonth crosses back a year)' => '2026-12-31',
    'a leap day' => '2028-02-29',
    'years past every literal the seeder used to carry' => '2031-06-15',
]);

it('places the statement history in COMPLETED months and the showcase statement ahead of them', function () {
    seedDemoAt(CarbonImmutable::today()->toDateString());

    $statements = OwnerStatement::withoutGlobalScopes()->orderBy('period_start')->get();
    $thisMonth = CarbonImmutable::today()->startOfMonth();

    // ⚠️ This is the structural property that makes the collision impossible, so it is asserted directly rather
    // than inferred from "the seed passed": every PAID history month has already ENDED, so nothing in it can
    // ever meet a forward-dated booking.
    $paid = $statements->where('status', 'paid');
    expect($paid)->toHaveCount(5);
    foreach ($paid as $s) {
        expect(CarbonImmutable::parse($s->period_end)->lessThan($thisMonth))
            ->toBeTrue("a 'paid' statement covers a month that has not finished: {$s->period_start}");
    }

    // …and the showcase statement is the NEXT month, so its stays are always genuinely upcoming (#33).
    $finalized = $statements->firstWhere('status', 'finalized');
    expect($finalized)->not->toBeNull()
        ->and(CarbonImmutable::parse($finalized->period_start)->toDateString())
        ->toBe($thisMonth->addMonth()->toDateString());
});

it('⚠️ leaves the demo timeline free of hardcoded calendar dates — the regression this fix exists for', function () {
    $source = (string) file_get_contents(database_path('seeders/DemoSeeder.php'));

    // Strip comments: the file explains the old literals at length, and a guard that fired on its own
    // documentation would be measuring prose (the CSS-comment trap, in a PHP guise).
    $code = (string) preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], ' ', $source);

    // A bare 'YYYY-MM-DD' or 'YYYY-MM' literal is exactly what rots. Anything genuinely fixed (a currency, an
    // IBAN, a version string) is not date-shaped, so this is specific rather than a blanket ban on strings.
    preg_match_all("/'(\d{4}-\d{2}(?:-\d{2})?)'/", $code, $m);

    expect($m[1])->toBe([], 'a hardcoded calendar date is back in DemoSeeder: '.implode(', ', array_unique($m[1])));
});
